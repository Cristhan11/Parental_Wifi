<?php

/**
 * DeviceTestDataSeeder - Test Device for Video/Quiz Assignment
 * 
 * This seeder creates a test device that can be used for testing
 * video and quiz assignments.
 * 
 * What it creates:
 * 1. Test parent user (parent@test.com / password) - if not exists
 * 2. Test device with MAC address (AA:BB:CC:DD:EE:FF)
 * 
 * Usage:
 * - php artisan db:seed --class=DeviceTestDataSeeder
 * 
 * Why firstOrCreate? Prevents duplicate data if seeder runs multiple times.
 * If data already exists, it uses existing records instead of creating duplicates.
 */

namespace Database\Seeders;

use App\Models\Device;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DeviceTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Creates a test device for video/quiz assignment testing.
     * 
     * @return void
     */
    public function run(): void
    {
        // Create or get test parent user
        $parent = User::firstOrCreate(
            ['email' => 'parent@test.com'],
            [
                'name' => 'Test Parent',
                'password' => Hash::make('password'),
                'role' => 'parent',
            ]
        );

        // Create or get test device
        $device = Device::firstOrCreate(
            ['mac_address' => 'AA:BB:CC:DD:EE:FF'],
            [
                'user_id' => $parent->id,
                'name' => 'Test Device',
                'status' => 'active',
                'remaining_time_minutes' => 0, // No time left, will trigger portal
                'total_time_allocated' => 0,
            ]
        );

        $this->command->info('✅ Test device created successfully!');
        $this->command->info('📧 Parent Login: parent@test.com / password');
        $this->command->info('📱 Device Name: ' . $device->name);
        $this->command->info('📱 Device MAC: ' . $device->mac_address);
        $this->command->info('📱 Device Status: ' . $device->status);
        $this->command->info('');
        $this->command->info('You can now assign videos and quizzes to this device.');
    }
}

