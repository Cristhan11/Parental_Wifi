<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create Devices Table
 * 
 * Purpose: Stores information about child devices that parents want to monitor.
 * Each device is identified by its MAC address and belongs to a parent user.
 * 
 * Key Features:
 * - MAC address is unique (one device = one MAC)
 * - Tracks device status (active, blocked, whitelisted)
 * - Time tracking: remaining_time_minutes and total_time_allocated (default 15 minutes)
 * - Stores IP address when device is connected
 * - Tracks when device was last seen online
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            
            // Foreign key to users table (parent who owns this device)
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Device name (e.g., "John's iPhone", "Sarah's Laptop")
            $table->string('name');
            
            // MAC address - unique identifier for the device
            // Format: XX:XX:XX:XX:XX:XX or XX-XX-XX-XX-XX-XX
            $table->string('mac_address', 17)->unique();
            
            // Device status:
            // 'active' = device can access internet (if time available)
            // 'blocked' = device is blocked from internet access
            // 'whitelisted' = device has unrestricted access (bypasses time limits)
            $table->enum('status', ['active', 'blocked', 'whitelisted'])->default('active');
            
            // IP address assigned to device when connected (nullable if not connected)
            $table->string('ip_address', 45)->nullable();
            
            // Time tracking fields - CRITICAL for captive portal functionality
            // remaining_time_minutes: How much internet time the device has left
            // total_time_allocated: Total time allocated to device (for tracking)
            // Default: 15 minutes as per requirements
            $table->integer('remaining_time_minutes')->default(15);
            $table->integer('total_time_allocated')->default(15);
            
            // Last time device was seen online (for monitoring)
            $table->timestamp('last_seen_at')->nullable();
            
            $table->timestamps();

            // Indexes for performance
            // MAC address is frequently queried for device lookup
            $table->index('mac_address');
            // User ID for filtering devices by parent
            $table->index('user_id');
            // Status for filtering active/blocked devices
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};

