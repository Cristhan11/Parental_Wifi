<?php

namespace App\Console\Commands;

use App\Jobs\DispatchDigestReportJob;
use App\Models\ReportingRecipient;
use Illuminate\Console\Command;

/**
 * Minimal CLI test: queues ONE daily digest job for a given parent user id.
 *
 * Differences vs `SendDigestReports`:
 * - Always uses frequency `daily` (hard-coded in `dispatch()`).
 * - Checks that at least one enabled recipient exists first — avoids queueing a useless job.
 *
 * Typical use: SSH into Raspberry Pi, configure MAIL_*, run `php artisan queue:work` in another terminal,
 * then: `php artisan reporting:send-test 1`
 */
class SendTestReport extends Command
{
    protected $signature = 'reporting:send-test
                            {user_id : Parent user ID}
                            {--sync : Run the job immediately (no queue) — use to debug SMTP without queue:work}';

    protected $description = 'Send or queue a daily digest test report for a specific parent.';

    public function handle(): int
    {
        $userId = (int) $this->argument('user_id');

        // `exists()` is efficient — we only need true/false, not the actual rows.
        $hasRecipient = ReportingRecipient::query()
            ->where('user_id', $userId)
            ->enabled()
            ->exists();

        if (! $hasRecipient) {
            $this->error('No enabled reporting recipients found for this user.');

            return self::FAILURE;
        }

        if ($this->option('sync')) {
            DispatchDigestReportJob::dispatchSync($userId, 'daily', isManualTest: true);
            $this->info('Ran test daily digest synchronously. Check Reports dispatch history and inbox.');

            return self::SUCCESS;
        }

        DispatchDigestReportJob::dispatch($userId, 'daily', isManualTest: true);
        $this->info('Queued test daily digest job. Ensure laravel-queue (or queue:work) is running.');

        return self::SUCCESS;
    }
}
