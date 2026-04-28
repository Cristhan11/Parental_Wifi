<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_registration_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('device_name');
            $table->string('mac_address', 17)->nullable();
            $table->string('hostname')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('request_source')->nullable();
            $table->string('fingerprint')->index();
            $table->string('status')->default('pending')->index();
            $table->string('assigned_role')->nullable();
            $table->boolean('seen_on_home_wifi')->default(false);
            $table->unsignedInteger('requests_count')->default(1);
            $table->timestamp('last_requested_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_registration_requests');
    }
};
