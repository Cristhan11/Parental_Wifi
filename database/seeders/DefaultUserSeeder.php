<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Default User Seeder
 * 
 * Purpose: Creates a default admin account for initial system access
 * 
 * Security Note:
 * - This creates a hardcoded admin account for first-time setup
 * - Default credentials should be changed immediately after first login
 * - This account is required to access the system and create additional parent/admin accounts
 * 
 * Default Credentials:
 * - Email: admin@parentalwifi.local
 * - Password: admin123
 * - Role: admin
 * 
 * Usage:
 * - Runs automatically when executing: php artisan db:seed
 * - Uses firstOrCreate() to prevent duplicate accounts if seeder runs multiple times
 * - Only creates the account if it doesn't already exist
 */
class DefaultUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Creates a default admin user with hardcoded credentials.
     * This account is essential for initial system access and user management.
     */
    public function run(): void
    {
        // Create default admin account
        // firstOrCreate() ensures we don't create duplicates if seeder runs multiple times
        User::firstOrCreate(
            // Search criteria: Look for user with this email
            [
                'email' => 'admin@parentalwifi.local',
            ],
            // Data to create if user doesn't exist
            [
                'name' => 'System Administrator',
                'email' => 'admin@parentalwifi.local',
                'password' => Hash::make('admin123'), // Password is hashed for security
                'role' => 'admin', // Admin role for full system access
                'email_verified_at' => now(), // Mark email as verified (no email verification needed for default account)
            ]
        );

        // Output confirmation message
        $this->command->info('Default admin account created:');
        $this->command->info('  Email: admin@parentalwifi.local');
        $this->command->info('  Password: admin123');
        $this->command->warn('  ⚠️  Please change the password after first login!');
    }
}

