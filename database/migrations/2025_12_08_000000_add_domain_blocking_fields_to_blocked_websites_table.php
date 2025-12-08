<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: Add Domain Blocking Fields to Blocked Websites Table
 * 
 * Purpose: Adds support for domain-level and app-level blocking in addition to URL-level blocking.
 * 
 * What This Migration Does:
 * - Adds block_type field: Distinguishes between URL, domain, and app blocking
 * - Adds block_subdomains field: Controls whether to block subdomains (e.g., *.facebook.com)
 * - Adds related_domains field: Stores JSON array of related domains for app-level blocking
 * - Updates unique constraint: Allows same domain to be blocked as URL and domain separately
 * - Adds index on block_type: Improves query performance when filtering by block type
 * 
 * Why These Fields?
 * - block_type: Mobile apps use multiple domains (e.g., Facebook uses facebook.com, api.facebook.com, etc.)
 *   We need to distinguish between blocking a specific URL vs. blocking an entire domain vs. blocking an app
 * - block_subdomains: Some apps use subdomains (e.g., m.facebook.com, api.facebook.com)
 *   Parents may want to block all subdomains when blocking a domain
 * - related_domains: When blocking an app, we need to store all related domains that should be blocked
 *   This allows the system to automatically block all domains when parent blocks an app
 * 
 * Backward Compatibility:
 * - Existing records default to block_type = 'url' (maintains current behavior)
 * - All existing blocked websites continue to work as before
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This method adds the new fields to the blocked_websites table.
     * It also updates the unique constraint to include block_type, allowing
     * the same domain to be blocked as both URL and domain separately.
     */
    public function up(): void
    {
        Schema::table('blocked_websites', function (Blueprint $table) {
            // Add block_type field: enum with values 'url', 'domain', 'app'
            // Default is 'url' for backward compatibility (existing records are URL-level blocks)
            // Positioned after 'domain' field for logical grouping
            $table->enum('block_type', ['url', 'domain', 'app'])
                ->default('url')
                ->after('domain')
                ->comment('Type of blocking: url (specific URL), domain (entire domain), app (app with related domains)');
            
            // Add block_subdomains field: boolean flag
            // Default is false (don't block subdomains by default)
            // When true, blocks all subdomains (e.g., *.facebook.com)
            $table->boolean('block_subdomains')
                ->default(false)
                ->after('block_type')
                ->comment('Whether to block subdomains (e.g., *.facebook.com)');
            
            // Add related_domains field: JSON array
            // Stores array of related domains for app-level blocking
            // Example: ['api.facebook.com', 'graph.facebook.com', 'm.facebook.com']
            // Nullable because URL and domain blocks don't need related domains
            $table->json('related_domains')
                ->nullable()
                ->after('block_subdomains')
                ->comment('JSON array of related domains for app-level blocking');
            
            // Add index on block_type for faster filtering
            // Used when filtering blocked websites by type (e.g., show all app blocks)
            $table->index('block_type', 'blocked_websites_block_type_index');
        });
        
        // Update unique constraint: Remove old constraint, add new one with block_type
        // This allows same domain to be blocked as URL and domain separately
        // Example: device can have both 'facebook.com' as URL block and 'facebook.com' as domain block
        Schema::table('blocked_websites', function (Blueprint $table) {
            // Drop old unique constraint
            $table->dropUnique(['device_id', 'domain']);
            
            // Add new unique constraint that includes block_type
            // Same domain can be blocked multiple times if block_type is different
            $table->unique(['device_id', 'domain', 'block_type'], 'blocked_websites_device_domain_type_unique');
        });
    }

    /**
     * Reverse the migrations.
     * 
     * This method removes the new fields and restores the original unique constraint.
     * Note: This will lose data in the new fields, but that's expected for rollback.
     */
    public function down(): void
    {
        Schema::table('blocked_websites', function (Blueprint $table) {
            // Drop the new unique constraint
            $table->dropUnique('blocked_websites_device_domain_type_unique');
            
            // Restore original unique constraint
            $table->unique(['device_id', 'domain']);
            
            // Drop index on block_type
            $table->dropIndex('blocked_websites_block_type_index');
            
            // Drop the new columns
            $table->dropColumn(['block_type', 'block_subdomains', 'related_domains']);
        });
    }
};

