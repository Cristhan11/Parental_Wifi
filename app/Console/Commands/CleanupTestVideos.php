<?php

namespace App\Console\Commands;

use App\Models\Video;
use App\Models\VideoCompletion;
use App\Models\VideoWordDisplay;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Cleanup Test Videos
 * 
 * This command removes all test videos, completions, and video files
 * to prepare the system for fresh testing.
 * 
 * Usage:
 * php artisan video:cleanup-test
 */
class CleanupTestVideos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'video:cleanup-test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove all test videos, completions, and video files';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (!$this->confirm('This will delete ALL videos, completions, and video files. Are you sure?', false)) {
            $this->info('Cancelled.');
            return Command::SUCCESS;
        }

        $this->info('Cleaning up test data...');

        // Get all videos
        $videos = Video::all();
        $videoCount = $videos->count();
        $completionCount = VideoCompletion::count();
        $wordDisplayCount = VideoWordDisplay::count();

        $this->info("Found {$videoCount} video(s), {$completionCount} completion(s), {$wordDisplayCount} word display(s)");

        // Delete video files
        $deletedFiles = 0;
        $freedSpace = 0;

        foreach ($videos as $video) {
            try {
                $videoPath = $video->video_path;
                
                // Delete video file
                if (Storage::disk('public')->exists($videoPath)) {
                    $fullPath = storage_path('app/public/' . $videoPath);
                    if (file_exists($fullPath)) {
                        $freedSpace += filesize($fullPath);
                        Storage::disk('public')->delete($videoPath);
                        $deletedFiles++;
                        $this->info("  ✅ Deleted file: {$videoPath}");
                    }
                }
            } catch (\Exception $e) {
                $this->error("  ❌ Error deleting file for video {$video->id}: " . $e->getMessage());
            }
        }

        // Delete all video completions (cascade will handle word displays)
        VideoCompletion::query()->delete();
        $this->info("✅ Deleted {$completionCount} completion(s)");

        // Delete all videos (this will cascade delete device assignments)
        Video::query()->delete();
        $this->info("✅ Deleted {$videoCount} video(s)");

        $freedMB = round($freedSpace / 1024 / 1024, 2);
        $this->newLine();
        $this->info("✅ Cleanup complete!");
        $this->info("  - Deleted {$deletedFiles} video file(s)");
        $this->info("  - Freed {$freedMB} MB of storage space");

        return Command::SUCCESS;
    }
}

