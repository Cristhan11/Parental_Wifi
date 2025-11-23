<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create Access Attempts Table
 * 
 * Purpose: Logs security events when children try to access blocked websites
 * or visit flagged websites. Parents are notified of these events in real-time.
 * 
 * Key Features:
 * - Tracks blocked website access attempts (denied)
 * - Tracks flagged website visits (allowed but logged)
 * - Used for security alerts and notifications
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('access_attempts', function (Blueprint $table) {
            $table->id();
            
            // Foreign key to devices table
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            
            // Type of access attempt:
            // 'blocked_website' = tried to access blocked site (denied)
            // 'flagged_website' = visited flagged site (allowed but logged)
            $table->enum('type', ['blocked_website', 'flagged_website']);
            
            // Full URL that was attempted/visited
            $table->string('url');
            
            // Domain extracted from URL
            $table->string('domain');
            
            // IP address of the website
            $table->string('ip_address', 45)->nullable();
            
            // Timestamp when attempt occurred
            $table->timestamp('attempted_at');
            
            $table->timestamps();

            // Indexes
            $table->index('device_id');
            $table->index('type');
            $table->index('attempted_at');
            // Composite index for security event queries
            $table->index(['device_id', 'type', 'attempted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('access_attempts');
    }
};

