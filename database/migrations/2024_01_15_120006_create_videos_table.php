<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create Videos Table
 * 
 * Purpose: Stores educational videos that children can watch to earn
 * additional internet time. Videos can have dictionary word validation enabled.
 * 
 * Key Features:
 * - Video stored locally on server (storage/app/videos/)
 * - Dictionary words can be enabled/disabled per video
 * - Word count: how many words to display during video
 * - Time reward: minutes granted upon successful completion
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            
            // Foreign key to users table (parent who added the video)
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Video title
            $table->string('title');
            
            // Video description
            $table->text('description')->nullable();
            
            // Video file path (stored in storage/app/videos/)
            // Example: "videos/educational_video_1.mp4"
            $table->string('video_path');
            
            // Video duration in seconds (for progress tracking)
            $table->integer('duration_seconds');
            
            // Dictionary word validation settings
            // If enabled, random dictionary words will appear during video
            // Child must remember and input them at the end
            $table->boolean('dictionary_words_enabled')->default(false);
            
            // Number of dictionary words to display during this video
            // (e.g., 5 words for a 10-minute video)
            $table->integer('word_count')->default(0)->nullable();
            
            // Time reward in minutes granted when video is completed
            // (and words validated if dictionary_words_enabled = true)
            $table->integer('time_reward_minutes')->default(15);
            
            // Active flag: allows parents to enable/disable videos
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('is_active');
            $table->index('dictionary_words_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};

