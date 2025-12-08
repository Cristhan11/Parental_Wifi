<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Make URL Field Nullable in Blocked Websites Table
 * 
 * Purpose: Since we removed URL-level blocking, the url field is no longer required
 * for domain-level and app-level blocking. Making it nullable allows these block types
 * to be created without providing a URL.
 * 
 * Why This Migration?
 * - Domain and App blocking don't need a URL (only domain name)
 * - URL field was required in original migration
 * - Making it nullable allows flexibility for different block types
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('blocked_websites', function (Blueprint $table) {
            $table->string('url')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blocked_websites', function (Blueprint $table) {
            $table->string('url')->nullable(false)->change();
        });
    }
};
