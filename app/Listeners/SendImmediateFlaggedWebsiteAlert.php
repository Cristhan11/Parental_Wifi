<?php

namespace App\Listeners;

use App\Events\FlaggedWebsiteVisited;
use App\Mail\ImmediateFlaggedWebsiteAlertMail;
use App\Models\Device;
use App\Models\ReportDispatchLog;
use App\Services\AccessAttemptAlertGrouping;
use App\Models\ReportingPreference;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Parallel to {@see SendImmediateBlockedWebsiteAlert} for {@see \App\Events\FlaggedWebsiteVisited} / {@see \App\Mail\ImmediateFlaggedWebsiteAlertMail}.
 */
class SendImmediateFlaggedWebsiteAlert
{
    /**
     * Same structure as {@see SendImmediateBlockedWebsiteAlert::handle} but for {@see FlaggedWebsiteVisited}
     * and {@see ImmediateFlaggedWebsiteAlertMail} (different copy + styling in Blade).
     */
    public function handle(FlaggedWebsiteVisited $event): void
    {
        $user = User::with(['reportingPreference', 'reportingRecipients'])->find($event->userId);
        if (! $user) {
            return;
        }

        $device = Device::query()->find($event->deviceId);
        if (! $device || $device->user_id !== $user->id || ($device->role ?? 'child') !== 'child') {
            return;
        }

        $preference = $this->resolvePreference($user);
        if (! $preference->immediate_alerts_enabled) {
            $this->logSkipped($user->id, 'immediate_flagged_website', 'Immediate alerts are disabled.');

            return;
        }

        $recipients = $user->reportingRecipients()->enabled()->pluck('email');
        if ($recipients->isEmpty()) {
            $this->logSkipped($user->id, 'immediate_flagged_website', 'No enabled reporting recipients.');

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
        $subject = sprintf('[Parental WiFi][Alert][Flagged] %s visited %s', $event->deviceName, $siteLabel);
        $detailLine = AccessAttemptAlertGrouping::detailHostFromEvent($event->url, $event->domain);
        $payload = [
            'preheader' => sprintf('Flagged activity detected for %s at %s.', $event->deviceName, now()->setTimezone($timezone)->format('M d, Y H:i:s')),
            'child_or_device_label' => $event->deviceName,
            'url_or_domain' => $detailLine,
            'event_local_datetime' => now()->setTimezone($timezone)->format('M d, Y H:i:s'),
            'device_name' => $event->deviceName,
            'ip_address' => null,
            'timezone' => $timezone,
            'dashboard_url' => config('reporting.email_dashboard_url'),
        ];

        foreach ($recipients as $email) {
            // Per-recipient try/catch so one bad mailbox does not block others.
            try {
                Mail::to($email)->send(new ImmediateFlaggedWebsiteAlertMail($payload, $subject));
                ReportDispatchLog::create([
                    'user_id' => $user->id,
                    'report_type' => 'immediate_flagged_website',
                    'recipient_email' => $email,
                    'subject' => $subject,
                    'status' => 'sent',
                    'meta' => ['device_id' => $event->deviceId, 'domain' => $event->domain, 'url' => $event->url],
                    'sent_at' => now(),
                ]);
            } catch (Throwable $e) {
                ReportDispatchLog::create([
                    'user_id' => $user->id,
                    'report_type' => 'immediate_flagged_website',
                    'recipient_email' => $email,
                    'subject' => $subject,
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'meta' => ['device_id' => $event->deviceId, 'domain' => $event->domain, 'url' => $event->url],
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

