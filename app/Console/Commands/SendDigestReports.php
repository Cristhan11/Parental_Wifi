<?php

namespace App\Console\Commands;

use App\Jobs\DispatchDigestReportJob;
use App\Models\User;
use Illuminate\Console\Command;
use InvalidArgumentException;

/**
 * Scheduled digest entry point: finds parents who opted in and queues one {@see DispatchDigestReportJob} per parent.
 *
 * Why a command + job split?
 * - This `handle()` method should finish quickly (seconds). It only DISPATCHES jobs.
 * - The job does heavy work: query metrics, render HTML, talk to SMTP. That runs in a queue worker.
 *
 * How to run manually:
 *   php artisan reporting:send-digest daily
 *   php artisan reporting:send-digest weekly --user_id=5   # only parent id 5
 *
 * On Raspberry Pi you typically do NOT run this by hand — `routes/console.php` schedules it.
 */
class SendDigestReports extends Command
{
    /**
     * `{frequency}` = required argument. `{--user_id=}` = optional named option (null if omitted).
     */
    protected $signature = 'reporting:send-digest {frequency : daily|weekly|monthly} {--user_id= : Optional parent user ID for targeted run}';

    protected $description = 'Dispatch reporting digest jobs for opted-in parent accounts.';

    public function handle(): int
    {
        // Normalize to lowercase so "Daily" and "daily" both work.
        $frequency = strtolower((string) $this->argument('frequency'));
        if (! in_array($frequency, ['daily', 'weekly', 'monthly'], true)) {
            // Third argument `true` to in_array() enables strict type comparison (recommended).
            throw new InvalidArgumentException('Frequency must be one of: daily, weekly, monthly.');
        }

        // Start an Eloquent query: only users with role `parent` (not children/admins for this feature).
        $query = User::query()->where('role', 'parent');

        // Optional filter: `--user_id=123` on the CLI becomes `$this->option('user_id')` as string "123" or null.
        $userId = $this->option('user_id');
        if ($userId !== null) {
            // `whereKey` is shorthand for `where('id', ...)`.
            $query->whereKey((int) $userId);
        }

        // `whereHas('reportingPreference', ...)` keeps only users who HAVE a related reporting_preferences row
        // AND that row has the matching digest column set to true.
        $query->whereHas('reportingPreference', function ($prefQuery) use ($frequency): void {
            // PHP 8 `match` is like a strict switch: it must be exhaustive for the given expression type.
            $column = match ($frequency) {
                'daily' => 'daily_digest_enabled',
                'weekly' => 'weekly_digest_enabled',
                'monthly' => 'monthly_digest_enabled',
            };

            $prefQuery->where($column, true);
        });

        // `pluck('id')` returns a Collection of integer IDs (still lazy until you iterate).
        $parents = $query->pluck('id');

        // `dispatch()` pushes a job onto the queue (database/redis/etc. — see QUEUE_CONNECTION in .env).
        foreach ($parents as $parentId) {
            DispatchDigestReportJob::dispatch((int) $parentId, $frequency);
        }

        $this->info(sprintf('Queued %d %s digest job(s).', $parents->count(), $frequency));

        // Exit code 0 tells the shell / cron the command succeeded.
        return self::SUCCESS;
    }
}
