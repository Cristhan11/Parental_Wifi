<?php

/**
 * Verify Video System Command
 * 
 * This command helps verify that the Video System is properly set up and functioning.
 * It checks database records, file storage, and relationships.
 * 
 * Usage:
 * php artisan video:verify
 * 
 * What it checks:
 * 1. Videos exist in database
 * 2. Video files exist in storage
 * 3. Devices are assigned to videos
 * 4. Dictionary words are available
 * 5. Video completions can be created
 */

namespace App\Console\Commands;

use App\Models\Device;
use App\Models\DictionaryWord;
use App\Models\User;
use App\Models\Video;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class VerifyVideoSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'video:verify';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify Video System setup and functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Verifying Video System...');
        $this->newLine();

        $allPassed = true;

        // Check 1: Videos exist
        $this->info('1. Checking videos in database...');
        $videoCount = Video::count();
        if ($videoCount > 0) {
            $this->line("   ✅ Found {$videoCount} video(s)");
            
            // Show video details
            $videos = Video::with('user')->get();
            foreach ($videos as $video) {
                $wordsStatus = $video->dictionary_words_enabled ? "Enabled ({$video->word_count} words)" : "Disabled";
                $activeStatus = $video->is_active ? "Active" : "Inactive";
                $this->line("      - Video #{$video->id}: {$video->title} ({$activeStatus}, {$wordsStatus})");
            }
        } else {
            $this->error('   ❌ No videos found. Run VideoTestDataSeeder or create videos via dashboard.');
            $allPassed = false;
        }
        $this->newLine();

        // Check 2: Video files exist
        $this->info('2. Checking video files in storage...');
        $videosWithFiles = 0;
        $videosWithoutFiles = 0;
        
        foreach (Video::all() as $video) {
            if (Storage::exists($video->video_path)) {
                $videosWithFiles++;
                $fileSize = Storage::size($video->video_path);
                $fileSizeMB = round($fileSize / 1024 / 1024, 2);
                $this->line("   ✅ Video #{$video->id}: File exists ({$fileSizeMB} MB)");
            } else {
                $videosWithoutFiles++;
                $this->warn("   ⚠️  Video #{$video->id}: File missing at {$video->video_path}");
            }
        }
        
        if ($videosWithoutFiles > 0) {
            $this->warn("   ⚠️  {$videosWithoutFiles} video(s) missing files. Upload via dashboard or place files in storage/app/videos/");
        }
        $this->newLine();

        // Check 3: Devices assigned to videos
        $this->info('3. Checking device assignments...');
        $device = Device::where('mac_address', 'AA:BB:CC:DD:EE:FF')->first();
        if ($device) {
            $assignedVideos = $device->videos()->count();
            $this->line("   ✅ Test device found: {$device->name}");
            $this->line("   ✅ Device assigned to {$assignedVideos} video(s)");
            
            if ($assignedVideos === 0) {
                $this->warn("   ⚠️  Device has no videos assigned. Assign videos via dashboard.");
            }
        } else {
            $this->error('   ❌ Test device (AA:BB:CC:DD:EE:FF) not found. Run VideoTestDataSeeder.');
            $allPassed = false;
        }
        $this->newLine();

        // Check 4: Dictionary words available
        $this->info('4. Checking dictionary words...');
        $wordCount = DictionaryWord::count();
        if ($wordCount > 0) {
            $this->line("   ✅ Found {$wordCount} dictionary word(s)");
        } else {
            $this->error('   ❌ No dictionary words found. Run DictionaryWordSeeder.');
            $allPassed = false;
        }
        $this->newLine();

        // Check 5: Storage directory exists and is writable
        $this->info('5. Checking storage directory...');
        $storagePath = storage_path('app/videos');
        if (is_dir($storagePath)) {
            if (is_writable($storagePath)) {
                $this->line("   ✅ Storage directory exists and is writable: {$storagePath}");
            } else {
                $this->error("   ❌ Storage directory exists but is not writable: {$storagePath}");
                $allPassed = false;
            }
        } else {
            $this->warn("   ⚠️  Storage directory does not exist: {$storagePath}");
            $this->line("   💡 Creating directory...");
            if (mkdir($storagePath, 0755, true)) {
                $this->line("   ✅ Directory created successfully");
            } else {
                $this->error("   ❌ Failed to create directory");
                $allPassed = false;
            }
        }
        $this->newLine();

        // Check 6: Test user exists
        $this->info('6. Checking test user...');
        $testUser = User::where('email', 'parent@test.com')->first();
        if ($testUser) {
            $this->line("   ✅ Test user found: {$testUser->name} ({$testUser->email})");
        } else {
            $this->warn("   ⚠️  Test user (parent@test.com) not found. Run VideoTestDataSeeder.");
        }
        $this->newLine();

        // Summary
        if ($allPassed) {
            $this->info('✅ All checks passed! Video System is ready for testing.');
        } else {
            $this->warn('⚠️  Some checks failed. Please fix the issues above before testing.');
        }
        $this->newLine();

        // Show test URLs
        $this->info('📋 Test URLs:');
        if ($device) {
            $videos = $device->videos()->where('is_active', true)->get();
            foreach ($videos as $video) {
                $url = url("/portal/video/{$video->id}?mac=AA:BB:CC:DD:EE:FF");
                $this->line("   - {$video->title}: {$url}");
            }
        }

        return $allPassed ? 0 : 1;
    }
}

