<?php

use App\Models\Quiz;
use App\Support\QuizSchoolLevel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('quizzes', 'random_bank_levels')) {
            Schema::table('quizzes', function (Blueprint $table) {
                $table->json('random_bank_levels')->nullable()->after('questions');
            });
        }

        DB::table('quizzes')
            ->where('title', Quiz::RANDOM_MODE_SETTINGS_TITLE)
            ->whereNull('random_bank_levels')
            ->update(['random_bank_levels' => json_encode(QuizSchoolLevel::levels())]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('quizzes', 'random_bank_levels')) {
            Schema::table('quizzes', function (Blueprint $table) {
                $table->dropColumn('random_bank_levels');
            });
        }
    }
};
