<?php

/**
 * SetupQuizTestData - Artisan Command for Quiz Test Data
 * 
 * This command provides an easy way to set up test data for the quiz system.
 * It's an alternative to running the seeder directly, with additional features.
 * 
 * Usage:
 * - php artisan quiz:setup-test-data (creates/updates test data)
 * - php artisan quiz:setup-test-data --fresh (deletes old data first)
 * 
 * Why a command? Provides better user experience than running seeder directly:
 * - Shows progress messages
 * - Displays summary table with login info
 * - Option to clear existing data first
 * - Better error handling and feedback
 */

namespace App\Console\Commands;

use Database\Seeders\QuizTestDataSeeder;
use Illuminate\Console\Command;

class SetupQuizTestData extends Command
{
    /**
     * The name and signature of the console command.
     * 
     * Command: php artisan quiz:setup-test-data
     * Option: --fresh (clears existing test data before creating new)
     * 
     * @var string
     */
    protected $signature = 'quiz:setup-test-data {--fresh : Clear existing test data first}';

    /**
     * The console command description.
     * 
     * This appears when running: php artisan list
     * 
     * @var string
     */
    protected $description = 'Set up test data for quiz system frontend testing';

    /**
     * Execute the console command.
     * 
     * This method runs when the command is executed.
     * It handles the --fresh option and calls the seeder.
     * 
     * Process:
     * 1. Check if --fresh option is used
     * 2. If --fresh: Ask confirmation, then delete existing test data
     * 3. Run QuizTestDataSeeder to create test data
     * 4. Display summary table with login info and URLs
     * 
     * @return int Command exit code (SUCCESS = 0, FAILURE = 1)
     */
    public function handle(): int
    {
        $this->info('🎯 Setting up quiz test data...');

        // Step 1: Handle --fresh option (clear existing data first)
        // This is useful when you want to reset test data to a clean state
        if ($this->option('fresh')) {
            if ($this->confirm('This will delete existing test data. Continue?', false)) {
                $this->warn('Clearing existing test data...');
                
                // Delete test user and related data
                $testUser = \App\Models\User::where('email', 'parent@test.com')->first();
                if ($testUser) {
                    $testUser->quizzes()->delete();
                    $testUser->devices()->delete();
                    $testUser->delete();
                    $this->info('✅ Existing test data cleared.');
                }
            } else {
                $this->info('Cancelled.');
                return Command::SUCCESS;
            }
        }

        // Run the seeder
        try {
            $seeder = new QuizTestDataSeeder();
            $seeder->setCommand($this);
            $seeder->run();
            
            $this->newLine();
            $this->info('✨ Test data setup complete!');
            $this->newLine();
            $this->table(
                ['Item', 'Details'],
                [
                    ['Parent Login', 'parent@test.com / password'],
                    ['Device MAC', 'AA:BB:CC:DD:EE:FF'],
                    ['Portal URL', '/portal/quiz/{quiz_id}?mac=AA:BB:CC:DD:EE:FF'],
                ]
            );
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Error setting up test data: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

