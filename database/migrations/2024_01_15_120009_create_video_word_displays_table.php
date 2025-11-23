<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create Video Word Displays Table
 * 
 * Purpose: Tracks which dictionary words were displayed during a specific
 * video viewing session. This is critical for word validation at the end.
 * 
 * Key Features:
 * - Links to video_completion (specific viewing session)
 * - Links to dictionary_word (which word was shown)
 * - Records timestamp when word was displayed (for verification)
 * - Stores word text at time of display (in case word is deleted later)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('video_word_displays', function (Blueprint $table) {
            $table->id();
            
            // Foreign key to video_completions table
            // Links to a specific video viewing session
            $table->foreignId('video_completion_id')->constrained()->onDelete('cascade');
            
            // Foreign key to dictionary_words table
            $table->foreignId('dictionary_word_id')->constrained()->onDelete('cascade');
            
            // Timestamp in video when word was displayed (in seconds)
            // Example: 125.5 = word appeared at 2 minutes 5.5 seconds
            $table->decimal('displayed_at_timestamp', 10, 2);
            
            // Word text stored at time of display
            // (preserved in case dictionary word is deleted later)
            $table->string('word_text');
            
            $table->timestamps();

            // Indexes
            $table->index('video_completion_id');
            $table->index('dictionary_word_id');
            // Custom shorter index name (MySQL limit is 64 characters)
            $table->index(['video_completion_id', 'displayed_at_timestamp'], 'vw_display_completion_timestamp_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_word_displays');
    }
};

