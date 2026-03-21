<?php

namespace App\Jobs;

use App\Mail\DailyDigestReportMail;
use App\Mail\MonthlyDigestReportMail;
use App\Mail\WeeklyDigestReportMail;
use App\Models\ReportDispatchLog;
use App\Models\ReportingPreference;
use App\Models\User;
use App\Services\ReportingDigestService;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use Throwable;

class DispatchDigestReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $userId,
        public readonly string $frequency
    ) {
    }

    public function handle(ReportingDigestService $digestService): void
    {
        $user = User::with(['reportingPreference', 'reportingRecipients'])->find($this->userId);
        if (!$user) {
            return;
        }

        $preference = $this->resolvePreference($user);
        if (!$this->isFrequencyEnabled($preference)) {
            $this->logSkipped($user->id, 'Digest frequency disabled.');
            return;
        }

        $recipients = $user->reportingRecipients()->enabled()->pluck('email');
        if ($recipients->isEmpty()) {
            $this->logSkipped($user->id, 'No enabled reporting recipients.');
            return;
        }

        $timezone = $preference->timezone ?: config('reporting.default_timezone');
        [$periodStart, $periodEnd] = $this->resolvePeriodWindow($timezone);
        $payload = $digestService->buildDigestPayload($user, $periodStart, $periodEnd, $timezone);
        $payload['dashboard_url'] = route('dashboard');
        $payload['title'] = ucfirst($this->frequency) . ' Report';
        $payload['preheader'] = $this->resolvePreheader($payload);

        if ($preference->skip_empty_digests && !$payload['has_activity']) {
            $this->logSkipped($user->id, 'No activity in digest period.', $periodStart, $periodEnd);
            return;
        }

        $subject = $this->buildSubject($periodStart, $periodEnd, $timezone);

        foreach ($recipients as $email) {
            try {
                Mail::to($email)->send($this->resolveMailable($payload, $subject));

                ReportDispatchLog::create([
                    'user_id' => $user->id,
                    'report_type' => 'digest',
                    'frequency' => $this->frequency,
                    'recipient_email' => $email,
                    'subject' => $subject,
                    'period_start' => $periodStart->setTimezone('UTC'),
                    'period_end' => $periodEnd->setTimezone('UTC'),
                    'status' => 'sent',
                    'meta' => [
                        'blocked_count' => $payload['violations_summary']['blocked_count'],
                        'flagged_count' => $payload['violations_summary']['flagged_count'],
                        'active_devices_count' => $payload['active_devices_count'],
                    ],
                    'sent_at' => now(),
                ]);
            } catch (Throwable $e) {
                ReportDispatchLog::create([
                    'user_id' => $user->id,
                    'report_type' => 'digest',
                    'frequency' => $this->frequency,
                    'recipient_email' => $email,
                    'subject' => $subject,
                    'period_start' => $periodStart->setTimezone('UTC'),
                    'period_end' => $periodEnd->setTimezone('UTC'),
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            }
        }
    }

    private function resolvePreference(User $user): ReportingPreference
    {
        if ($user->reportingPreference) {
            return $user->reportingPreference;
        }

        return ReportingPreference::create([
            'user_id' => $user->id,
            'immediate_alerts_enabled' => true,
            'daily_digest_enabled' => true,
            'weekly_digest_enabled' => true,
            'monthly_digest_enabled' => true,
            'timezone' => config('reporting.default_timezone'),
            'skip_empty_digests' => true,
        ]);
    }

    private function isFrequencyEnabled(ReportingPreference $preference): bool
    {
        return match ($this->frequency) {
            'daily' => $preference->daily_digest_enabled,
            'weekly' => $preference->weekly_digest_enabled,
            'monthly' => $preference->monthly_digest_enabled,
            default => false,
        };
    }

    private function resolvePeriodWindow(string $timezone): array
    {
        $now = CarbonImmutable::now($timezone);

        return match ($this->frequency) {
            'daily' => [$now->subDay()->startOfDay(), $now->subDay()->endOfDay()],
            'weekly' => [$now->subWeek()->startOfWeek(), $now->subWeek()->endOfWeek()],
            'monthly' => [$now->subMonth()->startOfMonth(), $now->subMonth()->endOfMonth()],
            default => throw new InvalidArgumentException('Unsupported digest frequency.'),
        };
    }

    private function resolveMailable(array $payload, string $subject): object
    {
        return match ($this->frequency) {
            'daily' => new DailyDigestReportMail($payload, $subject),
            'weekly' => new WeeklyDigestReportMail($payload, $subject),
            'monthly' => new MonthlyDigestReportMail($payload, $subject),
            default => throw new InvalidArgumentException('Unsupported digest frequency.'),
        };
    }

    private function resolvePreheader(array $payload): string
    {
        return match ($this->frequency) {
            'daily' => sprintf(
                'Your daily activity summary for %d active device(s).',
                $payload['active_devices_count']
            ),
            'weekly' => 'Your weekly summary is ready with violations, top domains, and usage trends.',
            'monthly' => 'Your monthly family internet activity report is ready.',
            default => 'Your digest is ready.',
        };
    }

    private function buildSubject(CarbonImmutable $periodStart, CarbonImmutable $periodEnd, string $timezone): string
    {
        return match ($this->frequency) {
            'daily' => sprintf(
                '[Parental WiFi][Daily Digest] %s - %s (%s)',
                $periodStart->format('M d, Y'),
                $periodEnd->format('M d, Y'),
                $timezone
            ),
            'weekly' => sprintf(
                '[Parental WiFi][Weekly Digest] Week of %s (%s)',
                $periodStart->format('M d, Y'),
                $timezone
            ),
            'monthly' => sprintf(
                '[Parental WiFi][Monthly Digest] %s (%s)',
                $periodStart->format('F Y'),
                $timezone
            ),
            default => throw new InvalidArgumentException('Unsupported digest frequency.'),
        };
    }

    private function logSkipped(
        int $userId,
        string $reason,
        ?CarbonImmutable $periodStart = null,
        ?CarbonImmutable $periodEnd = null
    ): void {
        ReportDispatchLog::create([
            'user_id' => $userId,
            'report_type' => 'digest',
            'frequency' => $this->frequency,
            'status' => 'skipped',
            'period_start' => $periodStart?->setTimezone('UTC'),
            'period_end' => $periodEnd?->setTimezone('UTC'),
            'error_message' => $reason,
        ]);
    }
}

