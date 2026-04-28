<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_bank_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('level', 40);
            $table->string('subject', 20);
            $table->text('question_text');
            $table->text('option_a');
            $table->text('option_b');
            $table->text('option_c');
            $table->text('option_d');
            $table->string('correct_option', 1);
            $table->text('explanation')->nullable();
            $table->string('status', 10)->default('Active');
            $table->string('source_competency', 255)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'level', 'subject', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_bank_items');
    }
};
