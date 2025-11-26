<?php

/**
 * VideoTestDataSeeder - Test Data for Video System
 * 
 * This seeder creates sample data for testing the video system.
 * It's used during development to quickly set up a test environment.
 * 
 * What it creates:
 * 1. Test parent user (parent@test.com / password) - if not exists
 * 2. Test device with MAC address (AA:BB:CC:DD:EE:FF) - if not exists
 * 3. One test video with dictionary words enabled
 * 4. Links device to video (assigns video to device)
 * 
 * IMPORTANT: This seeder creates a video RECORD in the database, but you must
 * manually upload the actual video file through the parent dashboard or place
 * the video file in storage/app/public/videos/ directory.
 * 
 * Usage:
 * - php artisan db:seed --class=VideoTestDataSeeder
 * 
 * Why firstOrCreate? Prevents duplicate data if seeder runs multiple times.
 * If data already exists, it uses existing records instead of creating duplicates.
 */

namespace Database\Seeders;

use App\Models\Device;
use App\Models\User;
use App\Models\Video;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class VideoTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * This method is called automatically when running the seeder.
     * It creates all test data in the correct order (user → device → videos → assignments).
     * 
     * @return void
     */
    public function run(): void
    {
        // Step 1: Create or get test parent user
        // firstOrCreate() checks if user exists (by email), creates if not
        // This prevents duplicate users if seeder runs multiple times
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

        // Create single test video
        // This video can be configured with dictionary words or without
        $video = Video::firstOrCreate(
            [
                'user_id' => $parent->id,
                'title' => 'Test Video',
            ],
            [
                'description' => 'A test video for development and testing.',
                'video_path' => 'videos/test_video.mp4', // Placeholder - upload actual file via dashboard
                'duration_seconds' => 300, // 5 minutes (default)
                'dictionary_words_enabled' => true,
                'word_count' => 5, // 5 words will appear during video
                'time_reward_minutes' => 15,
                'is_active' => true,
            ]
        );

        // Assign video to device
        $device->videos()->syncWithoutDetaching([$video->id]);

        $this->command->info('✅ Video test data created successfully!');
        $this->command->info('📧 Parent Login: parent@test.com / password');
        $this->command->info('📱 Device MAC: AA:BB:CC:DD:EE:FF');
        $this->command->info('🎬 Video created: ' . $video->title . ' (ID: ' . $video->id . ')');
        $this->command->warn('⚠️  Note: Video file needs to be uploaded manually via parent dashboard or placed in storage/app/public/videos/');
        $this->command->info('');
        $this->command->info('Test Video URL:');
        $this->command->info('  http://127.0.0.1:8000/portal/video/' . $video->id . '?mac=AA:BB:CC:DD:EE:FF');
    }
}
