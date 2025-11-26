<?php

/**
 * Check Video Completion Command
 * 
 * This command helps verify video completion records and word validation.
 * Useful for testing and debugging the video system.
 * 
 * Usage:
 * php artisan video:check-completion {completion_id}
 * php artisan video:check-completion --device=AA:BB:CC:DD:EE:FF
 * php artisan video:check-completion --video={video_id}
 */

namespace App\Console\Commands;

use App\Models\Device;
use App\Models\Video;
use App\Models\VideoCompletion;
use Illuminate\Console\Command;

class CheckVideoCompletion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'video:check-completion 
                            {completion_id? : The video completion ID to check}
                            {--device= : Filter by device MAC address}
                            {--video= : Filter by video ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check video completion records and validation results';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $completionId = $this->argument('completion_id');
        $deviceMac = $this->option('device');
        $videoId = $this->option('video');

        if ($completionId) {
            // Show specific completion
            $completion = VideoCompletion::with(['device', 'video', 'wordDisplays'])->find($completionId);
            
            if (!$completion) {
                $this->error("Video completion #{$completionId} not found.");
                return 1;
            }

            $this->displayCompletion($completion);
        } else {
            // Show all completions (with optional filters)
            $query = VideoCompletion::with(['device', 'video', 'wordDisplays']);

            if ($deviceMac) {
                $device = Device::where('mac_address', $deviceMac)->first();
                if ($device) {
                    $query->where('device_id', $device->id);
                } else {
                    $this->error("Device with MAC {$deviceMac} not found.");
                    return 1;
                }
            }

            if ($videoId) {
                $query->where('video_id', $videoId);
            }

            $completions = $query->orderBy('created_at', 'desc')->get();

            if ($completions->isEmpty()) {
                $this->warn('No video completions found.');
                return 0;
            }

            $this->info("Found {$completions->count()} completion(s):");
            $this->newLine();

            foreach ($completions as $completion) {
                $this->displayCompletion($completion);
                $this->newLine();
            }
        }

        return 0;
    }

    /**
     * Display completion details.
     */
    private function displayCompletion(VideoCompletion $completion)
    {
        $this->info("📹 Video Completion #{$completion->id}");
        $this->line("   Video: {$completion->video->title} (ID: {$completion->video_id})");
        $this->line("   Device: {$completion->device->name} ({$completion->device->mac_address})");
        $this->line("   Attempt: #{$completion->attempt_number}");
        $this->line("   Completed: " . ($completion->completed_at ? $completion->completed_at->format('Y-m-d H:i:s') : 'Not completed'));
        $this->line("   Watched Duration: {$completion->watched_duration} seconds");
        $this->newLine();

        // Word validation details
        if ($completion->video->dictionary_words_enabled) {
            $this->line("   📝 Dictionary Words:");
            $this->line("      Words Shown: {$completion->words_shown_count}");
            $this->line("      Words Correct: {$completion->words_correct}");
            $this->line("      Passed Validation: " . ($completion->passed_validation ? '✅ Yes' : '❌ No'));
            
            if ($completion->wordDisplays->count() > 0) {
                $this->line("      Words Displayed:");
                foreach ($completion->wordDisplays->orderBy('displayed_at_timestamp')->get() as $display) {
                    $this->line("         - '{$display->word_text}' at {$display->displayed_at_timestamp}s");
                }
            }

            if ($completion->words_entered) {
                $wordsEntered = $completion->getWordsEnteredArray();
                $this->line("      Words Entered: " . implode(', ', $wordsEntered));
            }
        } else {
            $this->line("   📝 Dictionary Words: Disabled for this video");
        }
        $this->newLine();

        // Time grant status
        $timeGrant = $completion->device->timeGrants()
            ->where('source', 'video')
            ->where('source_id', $completion->id)
            ->first();

        if ($timeGrant) {
            $this->line("   ⏰ Time Grant:");
            $this->line("      Minutes Granted: {$timeGrant->minutes_granted}");
            $this->line("      Granted At: {$timeGrant->granted_at->format('Y-m-d H:i:s')}");
        } else {
            $this->line("   ⏰ Time Grant: Not granted (validation failed or not completed)");
        }
    }
}

