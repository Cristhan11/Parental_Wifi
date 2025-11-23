<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create Device Schedules Table
 * 
 * Purpose: Stores time-based internet access rules for devices.
 * Parents can set schedules like "Internet allowed Monday-Friday 3PM-9PM"
 * or "Maximum 2 hours per day on weekends".
 * 
 * Key Features:
 * - Day of week scheduling
 * - Time window (start_time to end_time)
 * - Optional daily duration limit
 * - Active/inactive toggle
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('device_schedules', function (Blueprint $table) {
            $table->id();
            
            // Foreign key to devices table
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            
            // Day of week for this schedule
            // Each day needs a separate schedule entry
            $table->enum('day_of_week', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']);
            
            // Time window: when internet is allowed
            // Example: 15:00 = 3:00 PM
            $table->time('start_time');
            $table->time('end_time');
            
            // Optional: Daily duration limit in minutes
            // Example: 120 = maximum 2 hours per day
            // NULL = no daily limit (only time window applies)
            $table->integer('duration_limit_minutes')->nullable();
            
            // Active flag: allows enabling/disabling schedules
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();

            // Indexes
            $table->index('device_id');
            // Composite index for efficient schedule lookup
            $table->index(['device_id', 'day_of_week', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_schedules');
    }
};

