<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create Device Sessions Table
 * 
 * Purpose: Tracks active internet sessions for devices.
 * Used to monitor how long devices are online and calculate
 * time usage for time tracking system.
 * 
 * Key Features:
 * - Tracks session start and end times
 * - Calculates duration for time deduction
 * - Tracks bandwidth usage per session
 * - Used by time tracking system to deduct remaining_time_minutes
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('device_sessions', function (Blueprint $table) {
            $table->id();
            
            // Foreign key to devices table
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            
            // Session start time (when device connected/started browsing)
            $table->timestamp('started_at');
            
            // Session end time (when device disconnected/stopped browsing)
            // NULL = session is still active
            $table->timestamp('ended_at')->nullable();
            
            // Calculated duration in seconds
            // NULL if session is still active
            // Used to deduct from device's remaining_time_minutes
            $table->integer('duration_seconds')->nullable();
            
            // Bandwidth usage for this session
            $table->bigInteger('total_bytes_sent')->default(0);
            $table->bigInteger('total_bytes_received')->default(0);
            
            $table->timestamps();

            // Indexes
            $table->index('device_id');
            $table->index('started_at');
            // Composite index for active session queries
            $table->index(['device_id', 'started_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_sessions');
    }
};

