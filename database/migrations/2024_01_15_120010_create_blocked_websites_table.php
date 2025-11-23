<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create Blocked Websites Table
 * 
 * Purpose: Stores websites that parents want to block for specific devices.
 * When a child device tries to access a blocked website, access is denied
 * and an access attempt is logged.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('blocked_websites', function (Blueprint $table) {
            $table->id();
            
            // Foreign key to devices table
            // Each device can have different blocked websites
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            
            // Full URL that was blocked
            $table->string('url');
            
            // Domain extracted from URL (for easier filtering)
            // Example: "facebook.com" from "https://www.facebook.com/page"
            $table->string('domain');
            
            // Optional reason why website is blocked (for parent reference)
            $table->text('reason')->nullable();
            
            $table->timestamps();

            // Indexes
            $table->index('device_id');
            $table->index('domain');
            // Unique constraint: same domain can't be blocked twice for same device
            $table->unique(['device_id', 'domain']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blocked_websites');
    }
};

