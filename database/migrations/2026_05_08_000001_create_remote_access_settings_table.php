<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Single-row deployment settings for remote access reporting links (optional override of REPORTING_DASHBOARD_URL).
 *
 * @see \App\Models\RemoteAccessSetting
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remote_access_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('reporting_dashboard_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remote_access_settings');
    }
};
