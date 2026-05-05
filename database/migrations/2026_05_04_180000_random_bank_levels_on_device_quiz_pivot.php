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
        if (! Schema::hasColumn('device_quiz', 'random_bank_levels')) {
            Schema::table('device_quiz', function (Blueprint $table) {
                $table->json('random_bank_levels')->nullable()->after('quiz_id');
            });
        }

        $randomQuizIds = DB::table('quizzes')
            ->where('title', Quiz::RANDOM_MODE_SETTINGS_TITLE)
            ->pluck('id');

        $fallbackLevels = json_encode(QuizSchoolLevel::levels());

        if ($randomQuizIds->isNotEmpty()) {
            foreach ($randomQuizIds as $quizId) {
                $global = null;
                if (Schema::hasColumn('quizzes', 'random_bank_levels')) {
                    $global = DB::table('quizzes')->where('id', $quizId)->value('random_bank_levels');
                }
                $decoded = is_string($global) ? json_decode($global, true) : null;
                if (! is_array($decoded) || $decoded === []) {
                    $payload = $fallbackLevels;
                } else {
                    $payload = json_encode(array_values(array_unique(array_intersect(QuizSchoolLevel::levels(), $decoded))));
                }

                DB::table('device_quiz')
                    ->where('quiz_id', $quizId)
                    ->update(['random_bank_levels' => $payload]);
            }

            DB::table('device_quiz')
                ->whereIn('quiz_id', $randomQuizIds)
                ->whereNull('random_bank_levels')
                ->update(['random_bank_levels' => $fallbackLevels]);
        }

        if (Schema::hasColumn('quizzes', 'random_bank_levels')) {
            Schema::table('quizzes', function (Blueprint $table) {
                $table->dropColumn('random_bank_levels');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('quizzes', 'random_bank_levels')) {
            Schema::table('quizzes', function (Blueprint $table) {
                $table->json('random_bank_levels')->nullable()->after('questions');
            });
        }

        $randomQuizIds = DB::table('quizzes')
            ->where('title', Quiz::RANDOM_MODE_SETTINGS_TITLE)
            ->pluck('id');

        foreach ($randomQuizIds as $quizId) {
            $levels = DB::table('device_quiz')
                ->where('quiz_id', $quizId)
                ->whereNotNull('random_bank_levels')
                ->value('random_bank_levels');
            if ($levels !== null) {
                DB::table('quizzes')->where('id', $quizId)->update(['random_bank_levels' => $levels]);

                break;
            }
        }

        if (Schema::hasColumn('device_quiz', 'random_bank_levels')) {
            Schema::table('device_quiz', function (Blueprint $table) {
                $table->dropColumn('random_bank_levels');
            });
        }
    }
};
