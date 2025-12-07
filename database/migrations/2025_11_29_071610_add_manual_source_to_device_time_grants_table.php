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
        // Note: MySQL/MariaDB requires raw SQL to alter enum columns
        // SQLite doesn't support MODIFY COLUMN, so we skip this for SQLite (used in tests)
        $driver = DB::connection()->getDriverName();
        
        if (in_array($driver, ['mysql', 'mariadb'])) {
            DB::statement("ALTER TABLE device_time_grants MODIFY COLUMN source ENUM('quiz', 'video', 'manual') NOT NULL");
        }
        // For SQLite (testing), the enum constraint is not enforced at database level
        // but Laravel's validation will still enforce it
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum values (remove 'manual')
        // Only run on MySQL/MariaDB
        $driver = DB::connection()->getDriverName();
        
        if (in_array($driver, ['mysql', 'mariadb'])) {
            DB::statement("ALTER TABLE device_time_grants MODIFY COLUMN source ENUM('quiz', 'video') NOT NULL");
        }
    }
};
