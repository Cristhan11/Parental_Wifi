<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            if (! Schema::hasColumn('devices', 'child_birth_date')) {
                $table->date('child_birth_date')->nullable()->after('last_seen_at');
            }
            if (! Schema::hasColumn('devices', 'child_age_years')) {
                $table->unsignedTinyInteger('child_age_years')->nullable()->after('child_birth_date');
            }
            if (! Schema::hasColumn('devices', 'preferred_quiz_id')) {
                $table->foreignId('preferred_quiz_id')->nullable()->after('child_age_years')->constrained('quizzes')->nullOnDelete();
            }
            if (! Schema::hasColumn('devices', 'preferred_video_id')) {
                $table->foreignId('preferred_video_id')->nullable()->after('preferred_quiz_id')->constrained('videos')->nullOnDelete();
            }
        });

        Schema::table('quizzes', function (Blueprint $table) {
            if (! Schema::hasColumn('quizzes', 'age_bracket')) {
                $table->string('age_bracket', 32)->nullable()->after('level');
            }
        });

        Schema::table('question_bank_items', function (Blueprint $table) {
            if (! Schema::hasColumn('question_bank_items', 'age_bracket')) {
                $table->string('age_bracket', 32)->nullable()->after('level');
            }
        });

        $levelToBracket = [
            'Elementary' => 'AGES_7_9',
            'High School' => 'AGES_13_15',
            'Senior High School' => 'AGES_16_17',
        ];

        foreach ($levelToBracket as $level => $bracket) {
            DB::table('quizzes')->whereNull('age_bracket')->where('level', $level)->update(['age_bracket' => $bracket]);
            DB::table('question_bank_items')->whereNull('age_bracket')->where('level', $level)->update(['age_bracket' => $bracket]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('question_bank_items', 'age_bracket')) {
            Schema::table('question_bank_items', function (Blueprint $table) {
                $table->dropColumn('age_bracket');
            });
        }

        if (Schema::hasColumn('quizzes', 'age_bracket')) {
            Schema::table('quizzes', function (Blueprint $table) {
                $table->dropColumn('age_bracket');
            });
        }

        Schema::table('devices', function (Blueprint $table) {
            if (Schema::hasColumn('devices', 'preferred_quiz_id')) {
                $table->dropForeign(['preferred_quiz_id']);
            }
            if (Schema::hasColumn('devices', 'preferred_video_id')) {
                $table->dropForeign(['preferred_video_id']);
            }
            $drops = [];
            foreach (['child_birth_date', 'child_age_years', 'preferred_quiz_id', 'preferred_video_id'] as $col) {
                if (Schema::hasColumn('devices', $col)) {
                    $drops[] = $col;
                }
            }
            if ($drops !== []) {
                $table->dropColumn($drops);
            }
        });
    }
};
