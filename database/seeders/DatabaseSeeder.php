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
     */
    public function run(): void
    {
        // Seed default admin account and dictionary words
        $this->call([
            DefaultUserSeeder::class,      // Creates default admin account
            DictionaryWordSeeder::class,    // Seeds built-in dictionary words
        ]);
    }
}
