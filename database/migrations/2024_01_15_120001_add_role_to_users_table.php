<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add Role to Users Table
 * 
 * Purpose: Adds a 'role' field to the users table to distinguish between
 * parent users and admin users. This enables role-based access control
 * throughout the application.
 * 
 * Default value: 'parent' - All new users will be parents by default
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add role column after email field
            // 'parent' = regular parent user who manages child devices
            // 'admin' = system administrator (future use)
            $table->string('role')->default('parent')->after('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};

