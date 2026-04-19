<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class EnsureDefaultAdminCommand extends Command
{
    protected $signature = 'admin:ensure-default';

    protected $description = 'Create or update the default system admin (admin@parentalwifi.local / admin123) using the current database connection';

    public function handle(): int
    {
        User::updateOrCreate(
            ['email' => 'admin@parentalwifi.local'],
            [
                'name' => 'System Administrator',
                'password' => 'admin123',
                'role' => User::ROLE_ADMIN,
                'email_verified_at' => now(),
            ]
        );

        $user = User::where('email', 'admin@parentalwifi.local')->first();
        $ok = Auth::validate([
            'email' => 'admin@parentalwifi.local',
            'password' => 'admin123',
        ]);

        $this->info('Database connection: '.config('database.default'));
        $this->info('Default admin user id: '.$user->id);
        $this->info($ok ? 'Password check: OK (ready to log in).' : 'Password check: FAILED — inspect User model / password cast.');

        $this->newLine();
        $this->line('  Email:    admin@parentalwifi.local');
        $this->line('  Password: admin123');
        $this->warn('  Change this password after first login under Profile.');

        return self::SUCCESS;
    }
}
