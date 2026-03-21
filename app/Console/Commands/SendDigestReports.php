<?php

namespace App\Console\Commands;

use App\Jobs\DispatchDigestReportJob;
use App\Models\User;
use Illuminate\Console\Command;
use InvalidArgumentException;

class SendDigestReports extends Command
{
    protected $signature = 'reporting:send-digest {frequency : daily|weekly|monthly} {--user_id= : Optional parent user ID for targeted run}';

    protected $description = 'Dispatch reporting digest jobs for opted-in parent accounts.';

    public function handle(): int
    {
        $frequency = strtolower((string) $this->argument('frequency'));
        if (!in_array($frequency, ['daily', 'weekly', 'monthly'], true)) {
            throw new InvalidArgumentException('Frequency must be one of: daily, weekly, monthly.');
        }

        $query = User::query()->where('role', 'parent');

        $userId = $this->option('user_id');
        if ($userId !== null) {
            $query->whereKey((int) $userId);
        }

        $query->whereHas('reportingPreference', function ($prefQuery) use ($frequency): void {
            $column = match ($frequency) {
                'daily' => 'daily_digest_enabled',
                'weekly' => 'weekly_digest_enabled',
                'monthly' => 'monthly_digest_enabled',
            };

            $prefQuery->where($column, true);
        });

        $parents = $query->pluck('id');

        foreach ($parents as $parentId) {
            DispatchDigestReportJob::dispatch((int) $parentId, $frequency);
        }

        $this->info(sprintf('Queued %d %s digest job(s).', $parents->count(), $frequency));

        return self::SUCCESS;
    }
}

