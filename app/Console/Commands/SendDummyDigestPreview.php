<?php

namespace App\Console\Commands;

use App\Mail\DailyDigestReportMail;
use App\Mail\MonthlyDigestReportMail;
use App\Mail\WeeklyDigestReportMail;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;

/**
 * Sends a synthetic digest email so designers/QA can verify all template fields and bar charts.
 * Does not use real DB metrics; labeled clearly in the subject line.
 * Does not write {@see \App\Models\ReportDispatchLog} (preview only).
 */
class SendDummyDigestPreview extends Command
{
    protected $signature = 'reporting:send-dummy-digest
                            {user_id : Parent user ID whose enabled recipients receive the preview}
                            {frequency=daily : daily|weekly|monthly}';

    protected $description = 'Email a sample digest with fake numbers to preview the digest template (charts, per-device blocks, domains).';

    public function handle(): int
    {
        $userId = (int) $this->argument('user_id');
        $frequency = strtolower((string) $this->argument('frequency'));

        if (! in_array($frequency, ['daily', 'weekly', 'monthly'], true)) {
            throw new InvalidArgumentException('Frequency must be one of: daily, weekly, monthly.');
        }

        $user = User::with(['reportingRecipients'])->find($userId);
        if (! $user) {
            $this->error("User {$userId} not found.");

            return self::FAILURE;
        }

        $recipients = $user->reportingRecipients()->enabled()->pluck('email');
        if ($recipients->isEmpty()) {
            $this->error('No enabled reporting recipients for this user.');

            return self::FAILURE;
        }

        $timezone = 'UTC';
        [$periodStart, $periodEnd] = $this->resolvePeriodWindow($timezone, $frequency);

        $payload = $this->buildDummyPayload($periodStart, $periodEnd, $timezone, $frequency);

        $subject = match ($frequency) {
            'daily' => '[Parental WiFi][Preview][Daily] Sample data — not real activity',
            'weekly' => '[Parental WiFi][Preview][Weekly] Sample data — not real activity',
            'monthly' => '[Parental WiFi][Preview][Monthly] Sample data — not real activity',
            default => '[Parental WiFi][Preview] Sample data — not real activity',
        };

        foreach ($recipients as $email) {
            $mailable = match ($frequency) {
                'daily' => new DailyDigestReportMail($payload, $subject),
                'weekly' => new WeeklyDigestReportMail($payload, $subject),
                'monthly' => new MonthlyDigestReportMail($payload, $subject),
                default => throw new InvalidArgumentException('Unsupported frequency.'),
            };
            Mail::to($email)->send($mailable);
        }

        $this->info(sprintf(
            'Sent %s dummy digest preview to %d recipient(s).',
            $frequency,
            $recipients->count()
        ));

        return self::SUCCESS;
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function resolvePeriodWindow(string $timezone, string $frequency): array
    {
        $now = CarbonImmutable::now($timezone);

        return match ($frequency) {
            'daily' => [$now->subDay()->startOfDay(), $now->subDay()->endOfDay()],
            'weekly' => [$now->subWeek()->startOfWeek(), $now->subWeek()->endOfWeek()],
            'monthly' => [$now->subMonth()->startOfMonth(), $now->subMonth()->endOfMonth()],
            default => throw new InvalidArgumentException('Unsupported digest frequency.'),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function buildDummyPayload(
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
        string $timezone,
        string $frequency
    ): array {
        // Two fake devices: strong mix of blocked (denied) vs flagged (logged) so digest "Violations" reads clearly.
        $devices = [
            [
                'id' => 101,
                'name' => "Alex's tablet (sample)",
                'violations_summary' => ['blocked_count' => 5, 'flagged_count' => 2],
                'top_visited_domains' => [
                    ['domain' => 'youtube.com', 'visits' => 48],
                    ['domain' => 'roblox.com', 'visits' => 15],
                    ['domain' => 'wikipedia.org', 'visits' => 9],
                ],
                'time_usage_and_grants' => [
                    'total_usage_minutes' => 125,
                    'grants_count' => 3,
                    'total_granted_minutes' => 45,
                ],
            ],
            [
                'id' => 102,
                'name' => "Sam's laptop (sample)",
                'violations_summary' => ['blocked_count' => 2, 'flagged_count' => 8],
                'top_visited_domains' => [
                    ['domain' => 'tiktok.com', 'visits' => 32],
                    ['domain' => 'google.com', 'visits' => 24],
                    ['domain' => 'github.com', 'visits' => 11],
                    ['domain' => 'stackoverflow.com', 'visits' => 7],
                ],
                'time_usage_and_grants' => [
                    'total_usage_minutes' => 88,
                    'grants_count' => 2,
                    'total_granted_minutes' => 30,
                ],
            ],
        ];

        $maxUsage = max(1, ...array_map(fn (array $d) => $d['time_usage_and_grants']['total_usage_minutes'], $devices));
        $maxGranted = max(1, ...array_map(fn (array $d) => $d['time_usage_and_grants']['total_granted_minutes'], $devices));

        foreach ($devices as $i => $d) {
            $u = $d['time_usage_and_grants']['total_usage_minutes'];
            $g = $d['time_usage_and_grants']['total_granted_minutes'];
            $devices[$i]['usage_bar_percent'] = $u > 0 ? (int) round(($u / $maxUsage) * 100) : 0;
            $devices[$i]['grants_bar_percent'] = $g > 0 ? (int) round(($g / $maxGranted) * 100) : 0;
        }

        $blockedTotal = (int) collect($devices)->sum(fn (array $d) => $d['violations_summary']['blocked_count']);
        $flaggedTotal = (int) collect($devices)->sum(fn (array $d) => $d['violations_summary']['flagged_count']);
        $usageTotal = (int) collect($devices)->sum(fn (array $d) => $d['time_usage_and_grants']['total_usage_minutes']);
        $grantsCountTotal = (int) collect($devices)->sum(fn (array $d) => $d['time_usage_and_grants']['grants_count']);
        $grantedMinTotal = (int) collect($devices)->sum(fn (array $d) => $d['time_usage_and_grants']['total_granted_minutes']);

        $topDomains = [
            ['domain' => 'youtube.com', 'visits' => 48],
            ['domain' => 'tiktok.com', 'visits' => 32],
            ['domain' => 'google.com', 'visits' => 24],
            ['domain' => 'roblox.com', 'visits' => 15],
            ['domain' => 'wikipedia.org', 'visits' => 9],
        ];

        $title = match ($frequency) {
            'daily' => 'Daily Report',
            'weekly' => 'Weekly Report',
            'monthly' => 'Monthly Report',
            default => 'Report',
        };

        $preheader = match ($frequency) {
            'daily' => 'Preview: sample daily activity with blocked + flagged counts per device (illustration only).',
            'weekly' => 'Preview: sample weekly summary with violations, domains, and usage (illustration only).',
            'monthly' => 'Preview: sample monthly family report (illustration only).',
            default => 'Preview digest.',
        };

        $previewBanner = 'Sample digest: <strong>Blocked</strong> = denied access attempts; <strong>Flagged</strong> = allowed visits logged for review. All numbers are fake.';

        return [
            'timezone' => $timezone,
            'period_start_local' => $periodStart,
            'period_end_local' => $periodEnd,
            'violations_summary' => [
                'blocked_count' => $blockedTotal,
                'flagged_count' => $flaggedTotal,
            ],
            'top_visited_domains' => $topDomains,
            'time_usage_and_grants' => [
                'total_usage_minutes' => $usageTotal,
                'grants_count' => $grantsCountTotal,
                'total_granted_minutes' => $grantedMinTotal,
            ],
            'active_devices_count' => 2,
            'registered_devices_count' => 2,
            'devices' => $devices,
            'has_activity' => true,
            'dashboard_url' => route('dashboard'),
            'title' => $title,
            'preheader' => $preheader,
            'preview_banner' => $previewBanner,
        ];
    }
}
