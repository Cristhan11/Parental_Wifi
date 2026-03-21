<?php

namespace App\Console\Commands;

use App\Mail\ImmediateBlockedWebsiteAlertMail;
use App\Mail\ImmediateFlaggedWebsiteAlertMail;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Sends sample immediate alert emails (blocked + flagged) for template preview.
 * Does not write to report_dispatch_logs.
 */
class SendDummyImmediateAlerts extends Command
{
    protected $signature = 'reporting:send-dummy-immediate-alerts
                            {user_id : Parent user ID whose enabled recipients receive the previews}';

    protected $description = 'Send two sample immediate alert emails (blocked attempt + flagged visit) for layout preview.';

    public function handle(): int
    {
        $userId = (int) $this->argument('user_id');

        $user = User::query()->find($userId);
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
        $now = CarbonImmutable::now($timezone)->format('M d, Y H:i:s');

        $blockedPayload = [
            'preheader' => sprintf('Preview: blocked access attempt for Child tablet A at %s (sample, not a real event).', $now),
            'child_or_device_label' => 'Child tablet A (sample)',
            'url_or_domain' => 'gambling-example.test',
            'event_local_datetime' => $now,
            'device_name' => 'Child tablet A (sample)',
            'ip_address' => '192.168.1.50',
            'timezone' => $timezone,
            'dashboard_url' => route('dashboard'),
        ];
        $blockedSubject = '[Parental WiFi][Preview][Blocked] Sample alert — not a real event';

        $flaggedPayload = [
            'preheader' => sprintf('Preview: flagged site visit for Child laptop B at %s (sample, not a real event).', $now),
            'child_or_device_label' => 'Child laptop B (sample)',
            'url_or_domain' => 'social-network-example.test',
            'event_local_datetime' => $now,
            'device_name' => 'Child laptop B (sample)',
            'ip_address' => '192.168.1.51',
            'timezone' => $timezone,
            'dashboard_url' => route('dashboard'),
        ];
        $flaggedSubject = '[Parental WiFi][Preview][Flagged] Sample alert — not a real event';

        foreach ($recipients as $email) {
            Mail::to($email)->send(new ImmediateBlockedWebsiteAlertMail($blockedPayload, $blockedSubject));
            Mail::to($email)->send(new ImmediateFlaggedWebsiteAlertMail($flaggedPayload, $flaggedSubject));
        }

        $this->info(sprintf(
            'Sent 2 sample immediate alert emails (blocked + flagged) to %d recipient(s).',
            $recipients->count()
        ));

        return self::SUCCESS;
    }
}
