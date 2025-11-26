<?php

namespace App\Console\Commands;

use App\Models\Video;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Cleanup Orphaned Video Files
 * 
 * This command finds video files in storage that don't have corresponding
 * database records and optionally deletes them.
 * 
 * Useful for cleaning up files left behind after video deletion failures
 * or manual database operations.
 * 
 * Usage:
 * php artisan video:cleanup-orphaned
 * php artisan video:cleanup-orphaned --delete  (actually delete files)
 */
class CleanupOrphanedVideos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'video:cleanup-orphaned {--delete : Actually delete orphaned files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find and optionally delete orphaned video files (files without database records)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Scanning for orphaned video files...');
        
        // Get all video files in storage
        $videoFiles = Storage::disk('public')->files('videos');
        
        // Get all video paths from database
        $videoPaths = Video::pluck('video_path')->toArray();
        
        $orphanedFiles = [];
        $totalSize = 0;
        
        foreach ($videoFiles as $file) {
            // Check if this file has a corresponding database record
            if (!in_array($file, $videoPaths)) {
                $orphanedFiles[] = $file;
                $fullPath = storage_path('app/public/' . $file);
                if (file_exists($fullPath)) {
                    $totalSize += filesize($fullPath);
                }
            }
        }
        
        if (empty($orphanedFiles)) {
            $this->info('✅ No orphaned video files found!');
            return Command::SUCCESS;
        }
        
        // Display orphaned files
        $this->warn("Found " . count($orphanedFiles) . " orphaned video file(s):");
        $this->newLine();
        
        $totalSizeMB = round($totalSize / 1024 / 1024, 2);
        
        foreach ($orphanedFiles as $file) {
            $fullPath = storage_path('app/public/' . $file);
            $size = file_exists($fullPath) ? filesize($fullPath) : 0;
            $sizeMB = round($size / 1024 / 1024, 2);
            $this->line("  - {$file} ({$sizeMB} MB)");
        }
        
        $this->newLine();
        $this->info("Total orphaned size: {$totalSizeMB} MB");
        
        // Delete if --delete flag is set
        if ($this->option('delete')) {
            if (!$this->confirm('Are you sure you want to delete these files?', true)) {
                $this->info('Cancelled.');
                return Command::SUCCESS;
            }
            
            $deleted = 0;
            $failed = 0;
            
            foreach ($orphanedFiles as $file) {
                try {
                    if (Storage::disk('public')->delete($file)) {
                        $deleted++;
                        $this->info("  ✅ Deleted: {$file}");
                    } else {
                        // Try direct unlink as fallback
                        $fullPath = storage_path('app/public/' . $file);
                        if (file_exists($fullPath) && @unlink($fullPath)) {
                            $deleted++;
                            $this->info("  ✅ Deleted (direct): {$file}");
                        } else {
                            $failed++;
                            $this->error("  ❌ Failed to delete: {$file}");
                        }
                    }
                } catch (\Exception $e) {
                    $failed++;
                    $this->error("  ❌ Error deleting {$file}: " . $e->getMessage());
                }
            }
            
            $this->newLine();
            $this->info("✅ Deleted: {$deleted} file(s)");
            if ($failed > 0) {
                $this->warn("⚠️  Failed: {$failed} file(s)");
            }
        } else {
            $this->newLine();
            $this->info('💡 Run with --delete flag to actually delete these files:');
            $this->line('   php artisan video:cleanup-orphaned --delete');
        }
        
        return Command::SUCCESS;
    }
}

