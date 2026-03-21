<?php

namespace App\Console\Commands;

use App\Jobs\DispatchDigestReportJob;
use App\Models\ReportingRecipient;
use Illuminate\Console\Command;

class SendTestReport extends Command
{
    protected $signature = 'reporting:send-test {user_id : Parent user ID}';

    protected $description = 'Dispatch a daily digest test report for a specific parent.';

    public function handle(): int
    {
        $userId = (int) $this->argument('user_id');

        $hasRecipient = ReportingRecipient::query()
            ->where('user_id', $userId)
            ->enabled()
            ->exists();

        if (!$hasRecipient) {
            $this->error('No enabled reporting recipients found for this user.');
            return self::FAILURE;
        }

        DispatchDigestReportJob::dispatch($userId, 'daily');
        $this->info('Queued test daily digest job.');

        return self::SUCCESS;
    }
}

