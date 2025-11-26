<?php

namespace App\Console\Commands;

use App\Models\Video;
use App\Models\VideoCompletion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

/**
 * Cleanup Old Videos and Completions
 * 
 * This command can be used to automatically clean up old video completions
 * and optionally videos that haven't been used in a while.
 * 
 * Useful for freeing up storage space on Raspberry Pi or other limited storage devices.
 * 
 * Usage:
 * php artisan video:cleanup-old --days=90  (delete completions older than 90 days)
 * php artisan video:cleanup-old --days=90 --delete-videos  (also delete unused videos)
 */
class CleanupOldVideos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'video:cleanup-old 
                            {--days=90 : Delete completions older than this many days}
                            {--delete-videos : Also delete videos with no recent completions}
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old video completions and optionally unused videos';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $deleteVideos = $this->option('delete-videos');
        $dryRun = $this->option('dry-run');
        
        $cutoffDate = Carbon::now()->subDays($days);
        
        $this->info("Cleaning up video completions older than {$days} days (before {$cutoffDate->format('Y-m-d')})...");
        
        if ($dryRun) {
            $this->warn("🔍 DRY RUN MODE - No files will be deleted");
        }
        
        // Find old completions
        $oldCompletions = VideoCompletion::where('completed_at', '<', $cutoffDate)->get();
        $completionCount = $oldCompletions->count();
        
        $this->info("Found {$completionCount} old completion(s)");
        
        if ($completionCount > 0) {
            if ($dryRun) {
                $this->line("Would delete {$completionCount} completion(s)");
            } else {
                $deleted = VideoCompletion::where('completed_at', '<', $cutoffDate)->delete();
                $this->info("✅ Deleted {$deleted} old completion(s)");
            }
        }
        
        // Optionally delete videos with no recent completions
        if ($deleteVideos) {
            $this->newLine();
            $this->info("Checking for videos with no completions in the last {$days} days...");
            
            // Find videos with no completions in the last X days
            $videosToDelete = Video::whereDoesntHave('completions', function($query) use ($cutoffDate) {
                $query->where('completed_at', '>=', $cutoffDate);
            })
            ->where('is_active', false) // Only delete inactive videos
            ->get();
            
            $videoCount = $videosToDelete->count();
            $this->info("Found {$videoCount} inactive video(s) with no recent completions");
            
            if ($videoCount > 0) {
                $totalSize = 0;
                
                foreach ($videosToDelete as $video) {
                    $fullPath = storage_path('app/public/' . $video->video_path);
                    if (file_exists($fullPath)) {
                        $totalSize += filesize($fullPath);
                    }
                }
                
                $totalSizeMB = round($totalSize / 1024 / 1024, 2);
                
                if ($dryRun) {
                    $this->line("Would delete {$videoCount} video(s) ({$totalSizeMB} MB)");
                    foreach ($videosToDelete as $video) {
                        $completionCount = $video->completions()->count();
                        $this->line("  - {$video->title} (ID: {$video->id}, {$completionCount} old completion(s))");
                    }
                } else {
                    if (!$this->confirm("Delete {$videoCount} video(s) and free {$totalSizeMB} MB? This will also delete all completion history for these videos.", false)) {
                        $this->info('Cancelled.');
                        return Command::SUCCESS;
                    }
                    
                    $deleted = 0;
                    $freed = 0;
                    
                    foreach ($videosToDelete as $video) {
                        try {
                            // Delete video file
                            $videoPath = $video->video_path;
                            if (Storage::disk('public')->exists($videoPath)) {
                                $fullPath = storage_path('app/public/' . $videoPath);
                                if (file_exists($fullPath)) {
                                    $freed += filesize($fullPath);
                                }
                                Storage::disk('public')->delete($videoPath);
                            }
                            
                            // Delete video record (completions cascade delete automatically)
                            $video->delete();
                            $deleted++;
                            
                            $this->info("  ✅ Deleted: {$video->title}");
                        } catch (\Exception $e) {
                            $this->error("  ❌ Error deleting video {$video->id}: " . $e->getMessage());
                        }
                    }
                    
                    $freedMB = round($freed / 1024 / 1024, 2);
                    $this->newLine();
                    $this->info("✅ Deleted {$deleted} video(s), freed {$freedMB} MB");
                }
            }
        }
        
        $this->newLine();
        $this->info("✅ Cleanup complete!");
        
        if ($dryRun) {
            $this->warn("💡 Run without --dry-run to actually perform the cleanup");
        }
        
        return Command::SUCCESS;
    }
}

