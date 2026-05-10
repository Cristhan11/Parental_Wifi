<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('video_completions', function (Blueprint $table) {
            $table->unsignedTinyInteger('word_guess_failed_count')->default(0)->after('passed_validation');
        });
    }

    public function down(): void
    {
        Schema::table('video_completions', function (Blueprint $table) {
            $table->dropColumn('word_guess_failed_count');
        });
    }
};
