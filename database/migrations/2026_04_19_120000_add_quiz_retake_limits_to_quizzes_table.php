<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Daily cap on passed completions (per device) and cooldown between attempts.
     */
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->unsignedInteger('max_passes_per_day')->nullable()->after('time_reward_minutes');
            $table->unsignedInteger('retry_cooldown_minutes')->nullable()->after('max_passes_per_day');
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn(['max_passes_per_day', 'retry_cooldown_minutes']);
        });
    }
};
