<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create Flagged Websites Table
 * 
 * Purpose: Stores websites that parents want to monitor/flag (not block).
 * When a child device visits a flagged website, it's logged and parent
 * is notified, but access is still allowed.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('flagged_websites', function (Blueprint $table) {
            $table->id();
            
            // Foreign key to devices table
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            
            // Full URL that was flagged
            $table->string('url');
            
            // Domain extracted from URL
            $table->string('domain');
            
            // Optional reason why website is flagged (for parent reference)
            $table->text('reason')->nullable();
            
            $table->timestamps();

            // Indexes
            $table->index('device_id');
            $table->index('domain');
            // Unique constraint: same domain can't be flagged twice for same device
            $table->unique(['device_id', 'domain']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flagged_websites');
    }
};

