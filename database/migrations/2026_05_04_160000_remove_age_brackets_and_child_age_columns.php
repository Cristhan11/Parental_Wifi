<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Map legacy age_bracket codes to school `level` before dropping the column. */
    private const BRACKET_TO_LEVEL = [
        'AGES_5_6' => 'Kindergarten',
        'AGES_7_9' => 'Elementary',
        'AGES_10_12' => 'Elementary',
        'AGES_13_15' => 'High School',
        'AGES_16_17' => 'Senior High School',
    ];

    public function up(): void
    {
        if (Schema::hasColumn('quizzes', 'age_bracket')) {
            foreach (self::BRACKET_TO_LEVEL as $bracket => $level) {
                DB::table('quizzes')->where('age_bracket', $bracket)->update(['level' => $level]);
            }
            Schema::table('quizzes', function (Blueprint $table) {
                $table->dropColumn('age_bracket');
            });
        }

        if (Schema::hasColumn('question_bank_items', 'age_bracket')) {
            foreach (self::BRACKET_TO_LEVEL as $bracket => $level) {
                DB::table('question_bank_items')->where('age_bracket', $bracket)->update(['level' => $level]);
            }
            Schema::table('question_bank_items', function (Blueprint $table) {
                $table->dropColumn('age_bracket');
            });
        }

        $deviceDrops = [];
        if (Schema::hasColumn('devices', 'child_birth_date')) {
            $deviceDrops[] = 'child_birth_date';
        }
        if (Schema::hasColumn('devices', 'child_age_years')) {
            $deviceDrops[] = 'child_age_years';
        }
        if ($deviceDrops !== []) {
            Schema::table('devices', function (Blueprint $table) use ($deviceDrops) {
                $table->dropColumn($deviceDrops);
            });
        }
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->string('age_bracket', 32)->nullable()->after('level');
        });
        Schema::table('question_bank_items', function (Blueprint $table) {
            $table->string('age_bracket', 32)->nullable()->after('level');
        });
        Schema::table('devices', function (Blueprint $table) {
            $table->date('child_birth_date')->nullable()->after('last_seen_at');
            $table->unsignedTinyInteger('child_age_years')->nullable()->after('child_birth_date');
        });
    }
};
