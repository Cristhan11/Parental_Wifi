<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            // Add role column to devices table
            // 'child' = child device (default, subject to time limits)
            // 'guest' = guest device (temporary access)
            // 'parent' = parent device (unrestricted access)
            $table->string('role')->default('child')->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
