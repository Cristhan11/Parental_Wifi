<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->string('level', 40)->nullable()->after('description');
            $table->string('subject', 20)->nullable()->after('level');
            $table->unsignedSmallInteger('question_count')->default(10)->after('subject');
            $table->string('scoring_mode', 20)->default('pass_score')->after('question_count');
            $table->unsignedSmallInteger('minutes_per_correct')->default(1)->after('scoring_mode');
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->unsignedSmallInteger('correct_count')->default(0)->after('score');
            $table->unsignedSmallInteger('total_questions')->default(0)->after('correct_count');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropColumn(['correct_count', 'total_questions']);
        });

        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn(['level', 'subject', 'question_count', 'scoring_mode', 'minutes_per_correct']);
        });
    }
};
