<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Blocked Website Model
 * 
 * Stores websites blocked for all of a parent's child devices (household-wide list per user).
 * Supports three types of blocking:
 * - URL-level: Block specific URLs (e.g., https://facebook.com/page)
 * - Domain-level: Block entire domain + subdomains (e.g., facebook.com blocks *.facebook.com)
 * - App-level: Block app with all related domains (e.g., Facebook app blocks facebook.com, api.facebook.com, etc.)
 * 
 * What is Domain/App-Level Blocking?
 * - Mobile apps (like Facebook, Instagram, TikTok) don't just use one domain
 * - They make API calls to multiple domains (e.g., api.facebook.com, graph.facebook.com)
 * - Blocking only facebook.com won't block the app - it will still work via API domains
 * - Domain-level blocking uses DNS (dnsmasq) to redirect ALL requests to blocked domains to 127.0.0.1
 * - This works for both web browsers AND mobile apps
 */
class BlockedWebsite extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'url',
        'domain',
        'reason',
        'block_type',        // Type of blocking: 'url', 'domain', or 'app'
        'block_subdomains',  // Whether to block subdomains (e.g., *.facebook.com)
        'related_domains',   // JSON array of related domains for app-level blocking
    ];

    /**
     * Get the attributes that should be cast.
     * 
     * Casts ensure that:
     * - block_subdomains is always a boolean (not string "1" or "0")
     * - related_domains is automatically converted to/from JSON array
     * 
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'block_subdomains' => 'boolean',
            'related_domains' => 'array',  // Automatically converts JSON to array and vice versa
        ];
    }

    /**
     * Parent account that owns this block rule (applies to all of their child devices).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if this is a domain-level block.
     * 
     * Domain-level blocking blocks the entire domain (and optionally subdomains).
     * Example: Blocking "facebook.com" as domain will block all requests to facebook.com
     * 
     * @return bool True if block_type is 'domain'
     * 
     * Usage Example:
     * $blocked = BlockedWebsite::find(1);
     * if ($blocked->isDomainBlock()) {
     *     echo "This blocks the entire domain: {$blocked->domain}";
     * }
     */
    public function isDomainBlock(): bool
    {
        return $this->block_type === 'domain';
    }

    /**
     * Check if this is an app-level block.
     * 
     * App-level blocking blocks an app with all its related domains.
     * Example: Blocking "Facebook" as app will block facebook.com, api.facebook.com, graph.facebook.com, etc.
     * 
     * @return bool True if block_type is 'app'
     * 
     * Usage Example:
     * $blocked = BlockedWebsite::find(1);
     * if ($blocked->isAppBlock()) {
     *     $domains = $blocked->getDomainsToBlock();
     *     echo "This blocks app with domains: " . implode(', ', $domains);
     * }
     */
    public function isAppBlock(): bool
    {
        return $this->block_type === 'app';
    }

    /**
     * Get all domains that should be blocked for this blocked website.
     * 
     * Returns an array of all domains that need to be blocked:
     * - For URL blocks: Returns array with just the domain
     * - For domain blocks: Returns array with just the domain
     * - For app blocks: Returns array with main domain + all related domains
     * 
     * This is used by DomainBlockingService to generate dnsmasq config.
     * 
     * @return array<string> Array of domain names to block
     * 
     * Usage Example:
     * $blocked = BlockedWebsite::find(1);
     * $domains = $blocked->getDomainsToBlock();
     * // For app block: ['facebook.com', 'api.facebook.com', 'graph.facebook.com']
     * // For domain block: ['facebook.com']
     * // For URL block: ['facebook.com']
     */
    public function getDomainsToBlock(): array
    {
        $domains = [$this->domain];  // Always include the main domain
        
        // If this is an app block, include all related domains
        if ($this->isAppBlock() && is_array($this->related_domains)) {
            $domains = array_merge($domains, $this->related_domains);
        }
        
        // Remove duplicates and return
        return array_unique($domains);
    }

    /**
     * Check if subdomains should be blocked.
     * 
     * When block_subdomains is true, the system will block all subdomains of the domain.
     * Example: If domain is "facebook.com" and block_subdomains is true, it blocks:
     * - facebook.com
     * - www.facebook.com
     * - m.facebook.com
     * - api.facebook.com
     * - *.facebook.com (all subdomains)
     * 
     * This is used by DomainBlockingService to generate wildcard dnsmasq rules.
     * 
     * @return bool True if subdomains should be blocked
     * 
     * Usage Example:
     * $blocked = BlockedWebsite::find(1);
     * if ($blocked->shouldBlockSubdomains()) {
     *     echo "This will block all subdomains of {$blocked->domain}";
     * }
     */
    public function shouldBlockSubdomains(): bool
    {
        return $this->block_subdomains === true;
    }
}

