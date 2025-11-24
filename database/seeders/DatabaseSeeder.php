<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     * 
     * This seeder runs automatically when executing: php artisan db:seed
     * It creates:
     * 1. Default admin account (for initial system access)
     * 2. Dictionary words (built-in English words for video validation)
     * 
     * Note: QuizTestDataSeeder is available for testing but not run by default.
     * Run it separately with: php artisan db:seed --class=QuizTestDataSeeder
     * Or use: php artisan quiz:setup-test-data
     */
    public function run(): void
    {
        // Seed default admin account and dictionary words
        $this->call([
            DefaultUserSeeder::class,      // Creates default admin account
            DictionaryWordSeeder::class,    // Seeds built-in dictionary words
        ]);

        // Uncomment the line below to automatically seed quiz test data
        // $this->call(QuizTestDataSeeder::class);
    }
}
