<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

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
 * - Email: admin123@email.com
 * - Password: admin123
 * - Role: admin (displayed as Parent Owner in UI)
 *
 * Usage:
 * - Runs automatically when executing: php artisan db:seed
 * - Uses updateOrCreate() on email so re-running the seeder restores the documented
 *   password, role, and verification (fixes "credentials do not match" if the row
 *   existed with a different password). Change the password in the app after first login.
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
        // Bootstrap system admin (same email every time). updateOrCreate keeps credentials
        // aligned with this seeder when you run: php artisan db:seed --class=DefaultUserSeeder
        // Plaintext password: User model uses the `hashed` cast (single Hash::make on save).
        User::updateOrCreate(
            ['email' => 'admin123@email.com'],
            [
                'name' => 'Parent Owner',
                'password' => 'admin123',
                'role' => User::ROLE_ADMIN,
                'email_verified_at' => null,
                'requires_email_setup' => true,
                'force_password_change' => true,
            ]
        );

        // Output confirmation message
        $this->command->info('Default admin account created:');
        $this->command->info('  Email: admin123@email.com');
        $this->command->info('  Password: admin123');
        $this->command->warn('  ⚠️  Please change the password after first login!');
    }
}
