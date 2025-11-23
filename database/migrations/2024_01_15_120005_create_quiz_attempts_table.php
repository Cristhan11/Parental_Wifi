<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create Quiz Attempts Table
 * 
 * Purpose: Tracks each time a child attempts a quiz.
 * Stores their answers, calculates score, and records if they passed.
 * 
 * This table is used to:
 * - Validate quiz completion for time granting
 * - Track quiz performance
 * - Prevent duplicate time grants for same attempt
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            
            // Foreign key to devices table (child device taking the quiz)
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            
            // Foreign key to quizzes table
            $table->foreignId('quiz_id')->constrained()->onDelete('cascade');
            
            // Child's answers stored as JSON
            // Format: {
            //   "answers": [
            //     {"question_id": 1, "answer": "4"},
            //     {"question_id": 2, "answer": "Paris"}
            //   ]
            // }
            $table->json('answers');
            
            // Calculated score (percentage: 0-100)
            $table->integer('score')->default(0);
            
            // Whether the child passed (score >= passing_score)
            $table->boolean('passed')->default(false);
            
            // Timestamp when quiz was completed
            $table->timestamp('completed_at');
            
            $table->timestamps();

            // Indexes
            $table->index('device_id');
            $table->index('quiz_id');
            $table->index('passed');
            $table->index('completed_at');
            $table->index(['device_id', 'quiz_id', 'completed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_attempts');
    }
};

