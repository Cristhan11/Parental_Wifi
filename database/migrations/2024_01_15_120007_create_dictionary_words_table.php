<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create Dictionary Words Table
 * 
 * Purpose: Stores the educational dictionary word database.
 * Contains built-in English words with definitions that appear during
 * video playback. Parents can also add custom words.
 * 
 * Key Features:
 * - Word and definition for educational purposes
 * - Difficulty level (for future filtering)
 * - Built-in flag: distinguishes system words from parent-added words
 * - Words are reusable across multiple videos
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dictionary_words', function (Blueprint $table) {
            $table->id();
            
            // The dictionary word itself (e.g., "adventure", "curious")
            $table->string('word')->unique();
            
            // Definition of the word (educational purpose)
            // Example: "adventure" = "an exciting or dangerous experience"
            $table->text('definition');
            
            // Difficulty level (for future use in filtering/selection)
            // 'easy', 'medium', 'hard'
            $table->enum('difficulty_level', ['easy', 'medium', 'hard'])->default('medium');
            
            // Whether this is a built-in system word or parent-added
            // true = built-in English dictionary word (from seeder)
            // false = custom word added by parent
            $table->boolean('is_built_in')->default(false);
            
            // Optional: Foreign key to users if parent-added word
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            
            $table->timestamps();

            // Indexes
            $table->index('word');
            $table->index('difficulty_level');
            $table->index('is_built_in');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dictionary_words');
    }
};

