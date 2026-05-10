<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_bank_items', function (Blueprint $table) {
            $table->foreignId('quiz_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->string('question_type', 32)->default('multiple_choice')->after('subject');
            $table->string('correct_option', 255)->change();
            $table->index(['quiz_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('question_bank_items', function (Blueprint $table) {
            $table->dropIndex(['quiz_id', 'status']);
            $table->dropConstrainedForeignId('quiz_id');
            $table->dropColumn('question_type');
            $table->string('correct_option', 1)->change();
        });
    }
};
