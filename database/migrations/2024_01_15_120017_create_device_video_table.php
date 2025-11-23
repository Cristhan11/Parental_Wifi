<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create Device Video Pivot Table
 * 
 * Purpose: Many-to-many relationship between devices and videos.
 * Allows parents to assign specific videos to specific devices.
 * 
 * Example: Device "Sarah's Laptop" can be assigned "Educational Video 1" and "Science Video"
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('device_video', function (Blueprint $table) {
            $table->id();
            
            // Foreign key to devices table
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            
            // Foreign key to videos table
            $table->foreignId('video_id')->constrained()->onDelete('cascade');
            
            $table->timestamps();

            // Unique constraint: same video can't be assigned twice to same device
            $table->unique(['device_id', 'video_id']);
            
            // Indexes
            $table->index('device_id');
            $table->index('video_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_video');
    }
};

