<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_audit_events', function (Blueprint $table) {
            $table->id();
            $table->string('event', 48);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('attempted_identifier')->nullable();
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->boolean('is_remote')->default(true);
            $table->string('route_name')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['event', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['is_remote', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_audit_events');
    }
};
