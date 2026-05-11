<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dictionary_words', function (Blueprint $table) {
            $table->dropIndex(['difficulty_level']);
            $table->dropColumn('difficulty_level');
        });
    }

    public function down(): void
    {
        Schema::table('dictionary_words', function (Blueprint $table) {
            $table->enum('difficulty_level', ['easy', 'medium', 'hard'])->default('medium')->after('definition');
            $table->index('difficulty_level');
        });
    }
};
