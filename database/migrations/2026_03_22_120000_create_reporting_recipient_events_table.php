<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Immutable audit rows for reporting recipient CRUD — supports delete history (row removed from reporting_recipients).
 *
 * @see \App\Models\ReportingRecipientEvent
 * @see \App\Observers\ReportingRecipientObserver
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reporting_recipient_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('email');
            $table->string('action', 16); // added | updated | removed
            $table->text('summary');
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reporting_recipient_events');
    }
};
