<?php

namespace App\Listeners;

use App\Events\BlockedWebsiteAccessed;
use App\Mail\ImmediateBlockedWebsiteAlertMail;
use App\Models\Device;
use App\Models\ReportDispatchLog;
use App\Services\AccessAttemptAlertGrouping;
use App\Models\ReportingPreference;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Email side of {@see \App\Events\BlockedWebsiteAccessed}: respects {@see \App\Models\ReportingPreference::immediate_alerts_enabled}
 * and enabled {@see \App\Models\ReportingRecipient} rows, then sends via the Mail facade and writes {@see ReportDispatchLog}.
 *
 * Registration: Laravel event discovery (see {@see \App\Providers\AppServiceProvider::boot}) — do not duplicate with manual `Event::listen`.
 */
class SendImmediateBlockedWebsiteAlert
{
    /**
     * Laravel invokes this when {@see BlockedWebsiteAccessed} is dispatched (same request or queued listener).
     * The `$event` carries primitive data (ids, device name, url/domain) set by whoever fired the event.
     */
    public function handle(BlockedWebsiteAccessed $event): void
    {
        // Load parent account; `$event->userId` is the owning parent, not the child device user.
        $user = User::with(['reportingPreference', 'reportingRecipients'])->find($event->userId);
        if (! $user) {
            return;
        }

        // Same supervised set as digest emails (null/legacy empty role counts as child; not parent/guest/whitelisted).
        $device = Device::query()
            ->whereKey($event->deviceId)
            ->where('user_id', $user->id)
            ->forReportingEmails()
            ->first();

        if (! $device) {
            $this->logSkipped(
                $user->id,
                'immediate_blocked_website',
                'Device not eligible for immediate alerts (missing, parent/guest, or whitelisted).'
            );

            return;
        }

        $preference = $this->resolvePreference($user);
        if (! $preference->immediate_alerts_enabled) {
            $this->logSkipped($user->id, 'immediate_blocked_website', 'Immediate alerts are disabled.');

            return;
        }

        $recipients = $user->reportingRecipients()->enabled()->pluck('email');
        if ($recipients->isEmpty()) {
            $this->logSkipped($user->id, 'immediate_blocked_website', 'No enabled reporting recipients.');

            return;
        }

        $timezone = $preference->timezone ?: config('reporting.default_timezone');
        $groupDomain = AccessAttemptAlertGrouping::normalizeHost((string) ($event->domain ?? ''));
        if ($groupDomain === '') {
            $groupDomain = AccessAttemptAlertGrouping::normalizeHost(
                (string) (parse_url((string) ($event->url ?? ''), PHP_URL_HOST) ?: '')
            );
        }
        if ($groupDomain === '') {
            $groupDomain = 'unknown-site';
        }
        $siteLabel = AccessAttemptAlertGrouping::subjectSiteLabel($groupDomain);
        $subject = sprintf('[Parental WiFi][Alert][Blocked] %s attempted %s', $event->deviceName, $siteLabel);
        $detailLine = AccessAttemptAlertGrouping::detailHostFromEvent($event->url, $event->domain);
        $payload = [
            'preheader' => sprintf('Blocked access detected for %s at %s.', $event->deviceName, now()->setTimezone($timezone)->format('M d, Y H:i:s')),
            'child_or_device_label' => $event->deviceName,
            'url_or_domain' => $detailLine,
            'event_local_datetime' => now()->setTimezone($timezone)->format('M d, Y H:i:s'),
            'device_name' => $event->deviceName,
            'ip_address' => null,
            'timezone' => $timezone,
            'dashboard_url' => config('reporting.email_dashboard_url'),
        ];

        // One SMTP send per recipient — same content, independent success/failure logging.
        foreach ($recipients as $email) {
            try {
                Mail::to($email)->send(new ImmediateBlockedWebsiteAlertMail($payload, $subject));
                ReportDispatchLog::create([
                    'user_id' => $user->id,
                    'report_type' => 'immediate_blocked_website',
                    'recipient_email' => $email,
                    'subject' => $subject,
                    'status' => 'sent',
                    'meta' => ['device_id' => $event->deviceId, 'domain' => $event->domain, 'url' => $event->url],
                    'sent_at' => now(),
                ]);
            } catch (Throwable $e) {
                ReportDispatchLog::create([
                    'user_id' => $user->id,
                    'report_type' => 'immediate_blocked_website',
                    'recipient_email' => $email,
                    'subject' => $subject,
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'meta' => ['device_id' => $event->deviceId, 'domain' => $event->domain, 'url' => $event->url],
                ]);
            }
        }
    }

    /** Create defaults if the parent never opened the Reports page (same pattern as digest job). */
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

    private function logSkipped(int $userId, string $reportType, string $reason): void
    {
        ReportDispatchLog::create([
            'user_id' => $userId,
            'report_type' => $reportType,
            'status' => 'skipped',
            'error_message' => $reason,
        ]);
    }
}

