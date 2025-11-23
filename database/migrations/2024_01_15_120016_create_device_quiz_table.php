<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create Device Quiz Pivot Table
 * 
 * Purpose: Many-to-many relationship between devices and quizzes.
 * Allows parents to assign specific quizzes to specific devices.
 * 
 * Example: Device "John's iPhone" can be assigned "Math Quiz" and "Science Quiz"
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('device_quiz', function (Blueprint $table) {
            $table->id();
            
            // Foreign key to devices table
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            
            // Foreign key to quizzes table
            $table->foreignId('quiz_id')->constrained()->onDelete('cascade');
            
            $table->timestamps();

            // Unique constraint: same quiz can't be assigned twice to same device
            $table->unique(['device_id', 'quiz_id']);
            
            // Indexes
            $table->index('device_id');
            $table->index('quiz_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_quiz');
    }
};

