<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add 'manual' source to device_time_grants table
 * 
 * This migration adds 'manual' as a valid value to the source enum column.
 * This allows time to be granted manually (e.g., by parent/admin) in addition
 * to quiz and video completions.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Alter the enum column to include 'manual' as a valid value
        // Note: MySQL requires raw SQL to alter enum columns
        DB::statement("ALTER TABLE device_time_grants MODIFY COLUMN source ENUM('quiz', 'video', 'manual') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum values (remove 'manual')
        DB::statement("ALTER TABLE device_time_grants MODIFY COLUMN source ENUM('quiz', 'video') NOT NULL");
    }
};
