<?php

/**
 * Stores per-parent toggles for immediate alerts vs digest emails, timezone, and skip-empty behavior.
 *
 * @see \App\Models\ReportingPreference
 */
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reporting_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->boolean('immediate_alerts_enabled')->default(true);
            $table->boolean('daily_digest_enabled')->default(true);
            $table->boolean('weekly_digest_enabled')->default(true);
            $table->boolean('monthly_digest_enabled')->default(true);
            $table->string('timezone')->default('UTC');
            $table->boolean('skip_empty_digests')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reporting_preferences');
    }
};

