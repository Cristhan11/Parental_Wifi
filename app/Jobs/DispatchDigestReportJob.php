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

/**
 * QUEUED JOB — runs asynchronously when a worker processes the queue.
 *
 * Implements `ShouldQueue` so Laravel knows this class is meant for the queue system.
 * If no worker is running, jobs stay pending in the `jobs` table (driver=database) forever.
 *
 * Constructor receives `$userId`, `$frequency`, and optional `$isManualTest` — serialized into the queue payload
 * so the worker can rebuild this job later (even after a server restart).
 * When `$isManualTest` is true (UI “Send test digest” / `reporting:send-test`), the email subject gets a
 * unique `[Test …]` suffix so Gmail and similar clients do not collapse multiple sends into one thread.
 *
 * Flow summary:
 * 1. Load parent User + preferences + recipients.
 * 2. Bail early with a "skipped" log if disabled, no recipients, or empty digest when skip_empty is on.
 * 3. Decide the date range in the parent’s timezone: scheduled runs use the previous completed day/week/month;
 *    manual test daily digests use the current calendar day (for demos with same-day activity).
 * 4. Call ReportingDigestService to build a big `$payload` array for Blade.
 * 5. For each recipient email: send mailable, then insert ReportDispatchLog (sent or failed).
 */
class DispatchDigestReportJob implements ShouldQueue
{
    /**
     * Traits mixed into the job:
     * - Dispatchable: allows `DispatchDigestReportJob::dispatch(...)`.
     * - InteractsWithQueue: `$this->release()`, retries, etc.
     * - Queueable: connection / queue name customization.
     * - SerializesModels: when job is queued, Eloquent models in constructor are re-fetched by id on the worker.
     */
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Constructor property promotion (PHP 8): creates public properties automatically.
     * `readonly` means they cannot change after construction — good for queue safety.
     */
    public function __construct(
        public readonly int $userId,
        public readonly string $frequency,
        public readonly bool $isManualTest = false,
    ) {
    }

    /**
     * Laravel calls `handle()` on the worker. Dependencies are resolved from the service container:
     * `ReportingDigestService $digestService` is auto-injected.
     */
    public function handle(ReportingDigestService $digestService): void
    {
        // Eager-load related models to avoid N+1 queries when we access them below.
        $user = User::with(['reportingPreference', 'reportingRecipients'])->find($this->userId);
        if (! $user) {
            // Parent deleted — nothing to do.
            return;
        }

        $preference = $this->resolvePreference($user);
        if (! $this->isFrequencyEnabled($preference)) {
            $this->logSkipped($user->id, 'Digest frequency disabled.');

            return;
        }

        // Only enabled recipient rows; `pluck` gives a Collection of email strings.
        $recipients = $user->reportingRecipients()->enabled()->pluck('email');
        if ($recipients->isEmpty()) {
            $this->logSkipped($user->id, 'No enabled reporting recipients.');

            return;
        }

        // Fallback timezone if DB column is empty — see config/reporting.php.
        $timezone = $preference->timezone ?: config('reporting.default_timezone');

        // Tuple destructuring: `resolvePeriodWindow` returns [start, end] as CarbonImmutable instances.
        [$periodStart, $periodEnd] = $this->resolvePeriodWindow($timezone);

        // All heavy SQL aggregation lives in the service — keeps this job readable.
        $payload = $digestService->buildDigestPayload($user, $periodStart, $periodEnd, $timezone);

        // Extra keys the Blade layout expects (service focuses on metrics; job adds presentation metadata).
        $payload['dashboard_url'] = route('dashboard');
        $payload['title'] = ucfirst($this->frequency).' Report';
        $payload['preheader'] = $this->resolvePreheader($payload);

        // If parent chose to skip empty digests and the service found no meaningful activity, stop here.
        if ($preference->skip_empty_digests && ! $payload['has_activity']) {
            $this->logSkipped($user->id, 'No activity in digest period.', $periodStart, $periodEnd);

            return;
        }

        $subject = $this->buildSubject($periodStart, $periodEnd, $timezone);

        foreach ($recipients as $email) {
            try {
                // `send()` is synchronous inside the job — the job itself is async; SMTP happens here.
                Mail::to($email)->send($this->resolveMailable($payload, $subject));

                // Store UTC timestamps in the DB for consistent sorting across timezones.
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
                // Catch mail transport errors (wrong password, network) so one bad address does not crash the whole job.
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

    /**
     * Ensure we always have a preference row — jobs may run before the parent opened the Reports UI.
     */
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

    /**
     * Map the job’s frequency string to the correct boolean column on reporting_preferences.
     */
    private function isFrequencyEnabled(ReportingPreference $preference): bool
    {
        return match ($this->frequency) {
            'daily' => $preference->daily_digest_enabled,
            'weekly' => $preference->weekly_digest_enabled,
            'monthly' => $preference->monthly_digest_enabled,
            default => false,
        };
    }

    /**
     * Compute [start, end] of the reporting window in the parent’s timezone.
     * Scheduled runs use the previous day/week/month so an early-morning cron summarizes completed periods.
     * Manual daily tests (UI / `reporting:send-test`) use today so parents can preview same-day activity.
     */
    private function resolvePeriodWindow(string $timezone): array
    {
        $now = CarbonImmutable::now($timezone);

        return match ($this->frequency) {
            'daily' => $this->isManualTest
                ? [$now->startOfDay(), $now->endOfDay()]
                : [$now->subDay()->startOfDay(), $now->subDay()->endOfDay()],
            'weekly' => [$now->subWeek()->startOfWeek(), $now->subWeek()->endOfWeek()],
            'monthly' => [$now->subMonth()->startOfMonth(), $now->subMonth()->endOfMonth()],
            default => throw new InvalidArgumentException('Unsupported digest frequency.'),
        };
    }

    /**
     * Pick the correct Mailable class — each uses a thin Blade wrapper but shares `_digest-body`.
     */
    private function resolveMailable(array $payload, string $subject): object
    {
        return match ($this->frequency) {
            'daily' => new DailyDigestReportMail($payload, $subject),
            'weekly' => new WeeklyDigestReportMail($payload, $subject),
            'monthly' => new MonthlyDigestReportMail($payload, $subject),
            default => throw new InvalidArgumentException('Unsupported digest frequency.'),
        };
    }

    /** Short summary line some email clients show under the subject (preview text). */
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

    /**
     * Human-readable subject line; includes frequency and date range for inbox scanning.
     * Manual/test sends append a timestamp so each message is a distinct thread in Gmail.
     */
    private function buildSubject(CarbonImmutable $periodStart, CarbonImmutable $periodEnd, string $timezone): string
    {
        $subject = match ($this->frequency) {
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

        if ($this->isManualTest) {
            $subject .= sprintf(
                ' [Test %s]',
                CarbonImmutable::now($timezone)->format('Y-m-d H:i:s.u')
            );
        }

        return $subject;
    }

    /**
     * Record why a digest was not emailed — parents can see "skipped" rows in the Reports UI for transparency.
     */
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
