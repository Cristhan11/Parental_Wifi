<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('requires_email_setup')->default(false)->after('role');
            $table->boolean('force_password_change')->default(false)->after('requires_email_setup');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['requires_email_setup', 'force_password_change']);
        });
    }
};
