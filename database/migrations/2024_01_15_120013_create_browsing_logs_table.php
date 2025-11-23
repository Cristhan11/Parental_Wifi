<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create Browsing Logs Table
 * 
 * Purpose: Tracks all websites visited by child devices.
 * Provides browsing history for parents to review.
 * 
 * Key Features:
 * - Logs every website visit
 * - Tracks bandwidth usage (bytes sent/received)
 * - Stores user agent for device identification
 * - Indexed by timestamp for efficient date range queries
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('browsing_logs', function (Blueprint $table) {
            $table->id();
            
            // Foreign key to devices table
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            
            // Full URL visited
            $table->string('url');
            
            // Domain extracted from URL (for easier filtering)
            $table->string('domain');
            
            // IP address of the website (for network monitoring)
            $table->string('ip_address', 45)->nullable();
            
            // User agent string (browser/device info)
            $table->string('user_agent')->nullable();
            
            // Bandwidth usage tracking
            $table->bigInteger('bytes_sent')->default(0);
            $table->bigInteger('bytes_received')->default(0);
            
            // Timestamp when website was visited
            $table->timestamp('visited_at');
            
            $table->timestamps();

            // Indexes for performance
            $table->index('device_id');
            $table->index('domain');
            $table->index('visited_at');
            // Composite index for device browsing history queries
            $table->index(['device_id', 'visited_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('browsing_logs');
    }
};

