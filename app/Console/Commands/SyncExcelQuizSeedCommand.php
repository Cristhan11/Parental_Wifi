<?php

namespace App\Console\Commands;

use App\Models\QuestionBankItem;
use App\Models\Quiz;
use App\Models\User;
use Database\Seeders\BuiltInQuizSeeder;
use Database\Seeders\QuestionBankSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Replace built-in question bank + default admin quizzes from Excel seed files
 * without running migrate:fresh (preserves devices, users, videos, logs, etc.).
 */
class SyncExcelQuizSeedCommand extends Command
{
    protected $signature = 'quiz:sync-excel-seed
                            {--dry-run : Show what would change without writing}';

    protected $description = 'Reload built-in quizzes from database/seed-data/quiz-excel (safe for production / Pi)';

    public function handle(): int
    {
        $directory = database_path('seed-data/quiz-excel');
        if (! is_dir($directory) || glob($directory.'/*.xlsx') === []) {
            $this->error('No .xlsx files found in database/seed-data/quiz-excel. Deploy that folder first.');

            return self::FAILURE;
        }

        $owner = User::query()
            ->where('email', 'admin123@email.com')
            ->orWhere('role', User::ROLE_ADMIN)
            ->orWhere('role', User::ROLE_PARENT_ADMIN)
            ->orWhere('role', User::ROLE_PARENT)
            ->orderBy('id')
            ->first();

        if (! $owner) {
            $this->error('No owner account found (admin123@email.com or admin/parent role).');

            return self::FAILURE;
        }

        $bankCount = QuestionBankItem::query()->whereNull('user_id')->count();
        $legacyQuizCount = Quiz::query()
            ->where('user_id', $owner->id)
            ->where('title', 'like', '% Starter Quiz')
            ->count();
        $excelQuizCount = Quiz::query()
            ->where('user_id', $owner->id)
            ->where('description', 'like', 'Built-in quiz seeded from %.xlsx')
            ->count();

        $this->info('Built-in quiz sync (does not delete devices, users, videos, or parent-created quizzes).');
        $this->line("  Global question bank rows to remove: {$bankCount}");
        $this->line("  Legacy \"Starter Quiz\" rows to remove: {$legacyQuizCount}");
        $this->line("  Previous Excel-seeded quizzes to remove: {$excelQuizCount}");
        $this->newLine();
        $this->warn('Quizzes removed here also drop device_quiz assignments and quiz_attempts for those quizzes (DB cascade).');
        $this->warn('Back up the database first (e.g. copy storage/database.sqlite or mysqldump).');
        $this->newLine();

        if ($this->option('dry-run')) {
            $this->comment('Dry run only — no changes written.');

            return self::SUCCESS;
        }

        if (! $this->confirm('Continue?', true)) {
            $this->comment('Cancelled.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($owner): void {
            QuestionBankItem::query()->whereNull('user_id')->delete();

            Quiz::query()
                ->where('user_id', $owner->id)
                ->where(function ($q): void {
                    $q->where('title', 'like', '% Starter Quiz')
                        ->orWhere('description', 'like', 'Built-in quiz seeded from %.xlsx')
                        ->orWhere('description', 'like', 'Built-in % quiz for %. Questions are drawn randomly%');
                })
                ->delete();
        });

        $this->call(QuestionBankSeeder::class);
        $this->call(BuiltInQuizSeeder::class);

        $this->newLine();
        $this->info('Done. Assign the new default quizzes to child devices in the parent portal if needed.');

        return self::SUCCESS;
    }
}
