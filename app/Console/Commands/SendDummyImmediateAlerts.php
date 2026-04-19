<?php

namespace App\Console\Commands;

use App\Mail\ImmediateBlockedWebsiteAlertMail;
use App\Mail\ImmediateFlaggedWebsiteAlertMail;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Artisan command: send FAKE immediate alert emails so you can preview HTML without a real blocked/flagged event.
 *
 * How Artisan commands work (Laravel):
 * - Extends `Illuminate\Console\Command`.
 * - `$signature` defines the CLI name and arguments (what you type after `php artisan`).
 * - `handle()` runs when the command executes; it must return an exit code (SUCCESS = 0, FAILURE = non‑zero).
 *
 * Difference vs real alerts:
 * - Real flow: an event fires → listener → Mail. See `SendImmediateBlockedWebsiteAlert`.
 * - This command: builds arrays by hand and calls `Mail::send` directly. It does NOT write rows to
 *   `report_dispatch_logs` (by design — previews should not pollute audit history).
 */
class SendDummyImmediateAlerts extends Command
{
    /**
     * CLI definition. First line is the command name; lines below are wrapped arguments.
     * {user_id : ...} means a required argument named user_id, with help text after the colon.
     */
    protected $signature = 'reporting:send-dummy-immediate-alerts
                            {user_id : Parent user ID whose enabled recipients receive the previews}';

    /** Shown when you run `php artisan list` or `php artisan help reporting:send-dummy-immediate-alerts`. */
    protected $description = 'Send two sample immediate alert emails (blocked attempt + flagged visit) for layout preview.';

    /**
     * Main entry point — Laravel calls this automatically when you run the command.
     *
     * @return int Command exit status: `Command::SUCCESS` (0) or `Command::FAILURE` (1)
     */
    public function handle(): int
    {
        // `argument()` reads positional CLI args; (int) forces PHP integer type.
        $userId = (int) $this->argument('user_id');

        // `find($id)` returns one User model or null if no row matches.
        $user = User::query()->find($userId);
        if (! $user) {
            // `$this->error()` prints red text to the terminal.
            $this->error("User {$userId} not found.");

            return self::FAILURE;
        }

        // Relationship `reportingRecipients()` is defined on User model.
        // `enabled()` is a query scope on ReportingRecipient (only rows where is_enabled = true).
        // `pluck('email')` returns a Collection of email strings.
        $recipients = $user->reportingRecipients()->enabled()->pluck('email');
        if ($recipients->isEmpty()) {
            $this->error('No enabled reporting recipients for this user.');

            return self::FAILURE;
        }

        // Preview uses a fixed timezone label in the fake payload (real listeners use the parent’s preference).
        $timezone = 'UTC';
        // `CarbonImmutable::now($tz)` = current moment in that zone; `format()` turns it into a readable string.
        $now = CarbonImmutable::now($timezone)->format('M d, Y H:i:s');

        // Keys below mirror what real listeners pass into the Blade templates — keeps previews visually accurate.
        $blockedPayload = [
            'preheader' => sprintf('Preview: blocked access attempt for Child tablet A at %s (sample, not a real event).', $now),
            'child_or_device_label' => 'Child tablet A (sample)',
            'url_or_domain' => 'gambling-example.test',
            'event_local_datetime' => $now,
            'device_name' => 'Child tablet A (sample)',
            'ip_address' => '192.168.1.50',
            'timezone' => $timezone,
            // `route()` generates an absolute URL using APP_URL from .env — same helper real emails use.
            'dashboard_url' => config('reporting.email_dashboard_url'),
        ];
        // Subject line includes “Preview” so inboxes never confuse this with a live security alert.
        $blockedSubject = '[Parental WiFi][Preview][Blocked] Sample alert — not a real event';

        $flaggedPayload = [
            'preheader' => sprintf('Preview: flagged site visit for Child laptop B at %s (sample, not a real event).', $now),
            'child_or_device_label' => 'Child laptop B (sample)',
            'url_or_domain' => 'social-network-example.test',
            'event_local_datetime' => $now,
            'device_name' => 'Child laptop B (sample)',
            'ip_address' => '192.168.1.51',
            'timezone' => $timezone,
            'dashboard_url' => config('reporting.email_dashboard_url'),
        ];
        $flaggedSubject = '[Parental WiFi][Preview][Flagged] Sample alert — not a real event';

        // Send both templates to every recipient (same pattern as production listeners).
        foreach ($recipients as $email) {
            // `Mail::to($email)->send($mailable)` uses MAIL_* from .env (SMTP on Pi).
            Mail::to($email)->send(new ImmediateBlockedWebsiteAlertMail($blockedPayload, $blockedSubject));
            Mail::to($email)->send(new ImmediateFlaggedWebsiteAlertMail($flaggedPayload, $flaggedSubject));
        }

        // `%d` = integer placeholder; `count()` is Collection method.
        $this->info(sprintf(
            'Sent 2 sample immediate alert emails (blocked + flagged) to %d recipient(s).',
            $recipients->count()
        ));

        return self::SUCCESS;
    }
}
