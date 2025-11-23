<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create Device Time Grants Table
 * 
 * Purpose: Tracks when additional internet time is granted to a device
 * after successfully completing a quiz or watching a video.
 * 
 * This table provides an audit trail of all time grants, showing:
 * - How much time was granted
 * - What activity earned the time (quiz or video)
 * - When the grant occurred
 * - Which device received the time
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('device_time_grants', function (Blueprint $table) {
            $table->id();
            
            // Foreign key to devices table
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            
            // Amount of time granted in minutes
            // This gets added to device's remaining_time_minutes
            $table->integer('minutes_granted');
            
            // Source of the time grant:
            // 'quiz' = time earned by passing a quiz
            // 'video' = time earned by completing a video with word validation
            $table->enum('source', ['quiz', 'video']);
            
            // Optional: Reference to the quiz_attempt_id or video_completion_id
            // that triggered this grant (for detailed tracking)
            $table->unsignedBigInteger('source_id')->nullable()->comment('quiz_attempt_id or video_completion_id');
            
            // Timestamp when time was granted
            $table->timestamp('granted_at');
            
            $table->timestamps();

            // Indexes
            $table->index('device_id');
            $table->index('granted_at');
            $table->index(['device_id', 'granted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_time_grants');
    }
};

