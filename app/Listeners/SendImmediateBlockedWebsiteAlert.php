<?php

namespace App\Listeners;

use App\Events\BlockedWebsiteAccessed;
use App\Mail\ImmediateBlockedWebsiteAlertMail;
use App\Models\ReportDispatchLog;
use App\Models\ReportingPreference;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendImmediateBlockedWebsiteAlert
{
    public function handle(BlockedWebsiteAccessed $event): void
    {
        $user = User::with(['reportingPreference', 'reportingRecipients'])->find($event->userId);
        if (!$user) {
            return;
        }

        $preference = $this->resolvePreference($user);
        if (!$preference->immediate_alerts_enabled) {
            $this->logSkipped($user->id, 'immediate_blocked_website', 'Immediate alerts are disabled.');
            return;
        }

        $recipients = $user->reportingRecipients()->enabled()->pluck('email');
        if ($recipients->isEmpty()) {
            $this->logSkipped($user->id, 'immediate_blocked_website', 'No enabled reporting recipients.');
            return;
        }

        $timezone = $preference->timezone ?: config('reporting.default_timezone');
        $domain = $event->domain ?: ($event->url ?: 'unknown-domain');
        $subject = sprintf('[Parental WiFi][Alert][Blocked] %s attempted %s', $event->deviceName, $domain);
        $payload = [
            'preheader' => sprintf('Blocked access detected for %s at %s.', $event->deviceName, now()->setTimezone($timezone)->format('M d, Y H:i:s')),
            'child_or_device_label' => $event->deviceName,
            'url_or_domain' => $event->domain ?: ($event->url ?: 'N/A'),
            'event_local_datetime' => now()->setTimezone($timezone)->format('M d, Y H:i:s'),
            'device_name' => $event->deviceName,
            'ip_address' => null,
            'timezone' => $timezone,
            'dashboard_url' => route('dashboard'),
        ];

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

