<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create Video Completions Table
 * 
 * Purpose: Tracks when a child completes watching a video, including
 * dictionary word validation results. This determines if time should be granted.
 * 
 * Key Features:
 * - Tracks video completion (reached end of video)
 * - Stores words shown during video
 * - Stores words entered by child
 * - Validates if words match
 * - Tracks attempt number (for retry logic)
 * - Only grants time if passed_validation = true
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('video_completions', function (Blueprint $table) {
            $table->id();
            
            // Foreign key to devices table (child device watching video)
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            
            // Foreign key to videos table
            $table->foreignId('video_id')->constrained()->onDelete('cascade');
            
            // Timestamp when video was completed (reached end)
            $table->timestamp('completed_at');
            
            // Total duration watched in seconds
            // Should match video duration if fully watched
            $table->integer('watched_duration')->default(0);
            
            // Dictionary word validation fields (if dictionary_words_enabled)
            // Number of words that were displayed during video
            $table->integer('words_shown_count')->default(0);
            
            // Words entered by child (comma-separated or JSON)
            // Example: "adventure,curious,discover" or ["adventure", "curious", "discover"]
            $table->text('words_entered')->nullable();
            
            // Number of words that were correctly entered
            $table->integer('words_correct')->default(0);
            
            // Whether child passed word validation
            // true = all words correct, grant time
            // false = words incorrect, must retry video
            $table->boolean('passed_validation')->default(false);
            
            // Attempt number (increments each time child retries)
            // First attempt = 1, second attempt = 2, etc.
            $table->integer('attempt_number')->default(1);
            
            $table->timestamps();

            // Indexes
            $table->index('device_id');
            $table->index('video_id');
            $table->index('passed_validation');
            $table->index('completed_at');
            $table->index(['device_id', 'video_id', 'attempt_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_completions');
    }
};

