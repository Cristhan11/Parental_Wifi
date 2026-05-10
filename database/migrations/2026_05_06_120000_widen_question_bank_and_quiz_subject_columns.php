<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_bank_items', function (Blueprint $table) {
            $table->string('subject', 191)->change();
        });

        Schema::table('quizzes', function (Blueprint $table) {
            $table->string('subject', 191)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('question_bank_items', function (Blueprint $table) {
            $table->string('subject', 20)->change();
        });

        Schema::table('quizzes', function (Blueprint $table) {
            $table->string('subject', 20)->nullable()->change();
        });
    }
};
