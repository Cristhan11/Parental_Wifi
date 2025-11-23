<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create Quizzes Table
 * 
 * Purpose: Stores quizzes that parents create for their children.
 * Quizzes are educational assessments that children must pass to earn
 * additional internet time.
 * 
 * Key Features:
 * - Questions stored as JSON (simpler implementation)
 * - Passing score determines if child passes
 * - Time reward: minutes granted upon passing
 * - Active flag: allows enabling/disabling quizzes
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            
            // Foreign key to users table (parent who created the quiz)
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Quiz title (e.g., "Math Quiz - Addition", "Science Quiz")
            $table->string('title');
            
            // Quiz description (optional instructions for children)
            $table->text('description')->nullable();
            
            // Questions stored as JSON
            // Format: {
            //   "questions": [
            //     {
            //       "id": 1,
            //       "question": "What is 2+2?",
            //       "type": "multiple_choice",
            //       "options": ["2", "3", "4", "5"],
            //       "correct_answer": "4"
            //     }
            //   ]
            // }
            $table->json('questions');
            
            // Passing score as percentage (e.g., 70 = 70% correct answers needed)
            $table->integer('passing_score')->default(70);
            
            // Time reward in minutes granted when quiz is passed
            // (e.g., 15 minutes for passing this quiz)
            $table->integer('time_reward_minutes')->default(15);
            
            // Active flag: allows parents to enable/disable quizzes
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};

