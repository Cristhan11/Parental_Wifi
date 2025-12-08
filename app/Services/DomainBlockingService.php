<?php

namespace App\Services;

use App\Models\BlockedWebsite;
use App\Models\Device;
use Illuminate\Support\Facades\Log;

/**
 * Domain Blocking Service
 * 
 * This service handles domain-level and app-level blocking of websites using DNS (dnsmasq).
 * It provides methods to block/unblock domains, detect related domains for apps,
 * and manage dnsmasq configuration files.
 * 
 * What is Domain/App-Level Blocking?
 * - Mobile apps (like Facebook, Instagram, TikTok) don't just use one domain
 * - They make API calls to multiple domains (e.g., api.facebook.com, graph.facebook.com)
 * - Blocking only facebook.com won't block the app - it will still work via API domains
 * - Domain-level blocking uses DNS (dnsmasq) to redirect ALL requests to blocked domains to 127.0.0.1
 * - This works for both web browsers AND mobile apps
 * 
 * How DNS Blocking Works:
 * - dnsmasq is a DNS server that runs on the Raspberry Pi
 * - When a device tries to access a blocked domain, dnsmasq intercepts the DNS query
 * - Instead of returning the real IP address, dnsmasq returns 127.0.0.1 (localhost)
 * - The device can't connect because 127.0.0.1 is not the real server
 * - This blocks both web browsers AND mobile apps (apps also use DNS)
 * 
 * Why We Need This Service:
 * - Centralizes domain blocking logic (related domain detection, DNS config generation)
 * - Provides consistent interface for blocking/unblocking domains
 * - Handles complex scenarios (app blocking with multiple domains, subdomain blocking)
 * - Integrates with shell scripts for secure dnsmasq configuration
 * 
 * Integration Points:
 * - Called by BlockedWebsiteController when creating/updating/deleting blocked websites
 * - Uses ScriptExecutor for secure shell script execution
 * - Works with dnsmasq configuration files in /etc/dnsmasq.d/
 * 
 * Usage Example:
 * ```php
 * $service = new DomainBlockingService($scriptExecutor);
 * $device = Device::find(1);
 * $blockedWebsite = BlockedWebsite::find(1);
 * 
 * // Block domain for device
 * $service->blockDomainForDevice($blockedWebsite, $device);
 * 
 * // Detect related domains for Facebook app
 * $domains = $service->detectRelatedDomains('facebook.com', 'Facebook');
 * // Returns: ['api.facebook.com', 'graph.facebook.com', 'm.facebook.com', ...]
 * 
 * // Update dnsmasq config for device
 * $service->updateDnsmasqBlocklist($device);
 * ```
 */
class DomainBlockingService
{
    /**
     * ScriptExecutor instance for secure script execution.
     * 
     * ScriptExecutor provides a secure wrapper for executing shell scripts.
     * It validates scripts, sanitizes arguments, and handles errors safely.
     * 
     * Why Dependency Injection?
     * - Makes the service testable (can inject mock ScriptExecutor in tests)
     * - Follows Laravel's dependency injection pattern
     * - Allows easy swapping of implementations if needed
     * 
     * @var ScriptExecutor
     */
    protected ScriptExecutor $scriptExecutor;

    /**
     * Predefined mappings of apps to their related domains.
     * 
     * When a parent blocks an app (e.g., Facebook), we need to know all domains
     * that the app uses. This array stores common apps and their related domains.
     * 
     * Why Predefined Mappings?
     * - Apps use multiple domains that parents may not know about
     * - Example: Facebook uses api.facebook.com, graph.facebook.com, m.facebook.com, etc.
     * - Parents shouldn't have to manually find and add all domains
     * - System automatically suggests all related domains when blocking an app
     * 
     * How It Works:
     * - When parent blocks "Facebook" as an app, system looks up 'facebook.com' in this array
     * - Returns all related domains that should also be blocked
     * - Parent can review and modify the list before saving
     * 
     * Adding New Apps:
     * - Add entry with main domain as key
     * - Value is array of related domains
     * - Keep domains in lowercase for consistency
     * 
     * @var array<string, array<string>>
     */
    protected array $appDomainMappings = [
        // Facebook family
        'facebook.com' => [
            'api.facebook.com',
            'graph.facebook.com',
            'm.facebook.com',
            'connect.facebook.com',
            'www.facebook.com',
            'static.xx.fbcdn.net',
            'fbcdn.net',
            'video.xx.fbcdn.net',
            'scontent.xx.fbcdn.net',
            'edge-mqtt.facebook.com',
            'b-api.facebook.com',
            'b-graph.facebook.com',
            'star.c10r.facebook.com',
        ],
        'instagram.com' => [
            'api.instagram.com',
            'i.instagram.com',
            'www.instagram.com',
            'graph.instagram.com',
            'scontent.xx.fbcdn.net',   // shared CDN with Facebook/Instagram
            'cdninstagram.com',        // image/video CDN
        ],
        'whatsapp.com' => [
            'web.whatsapp.com',
            'api.whatsapp.com',
            'mmg.whatsapp.net',
            'static.whatsapp.net',
        ],

        // TikTok
        'tiktok.com' => [
            'api.tiktok.com',
            'www.tiktok.com',
            'm.tiktok.com',
            'log.tiktok.com',
            'mon.tiktok.com',
            'v16.muscdn.com',
            'p16-sign-va.tiktokcdn.com',
            'p16-va.tiktokcdn.com',
            'tiktokcdn.com',
        ],

        // YouTube / Google video delivery
        'youtube.com' => [
            'www.youtube.com',
            'm.youtube.com',
            'i.ytimg.com',
            'yt3.ggpht.com',
            'googlevideo.com',
            'ytimg.com',
            'youtubei.googleapis.com',
        ],

        // Twitter / X
        'twitter.com' => [
            'api.twitter.com',
            'mobile.twitter.com',
            't.co',
            'twimg.com',
            'pbs.twimg.com',
            'video.twimg.com',
        ],

        // Snapchat
        'snapchat.com' => [
            'api.snapchat.com',
            'app.snapchat.com',
            'www.snapchat.com',
            'sc-cdn.net',
            'sc-prod.net',
        ],

        // Discord
        'discord.com' => [
            'api.discord.com',
            'cdn.discordapp.com',
            'media.discordapp.net',
            'gateway.discord.gg',
        ],
    ];

    /**
     * Constructor - Initialize DomainBlockingService with ScriptExecutor.
     * 
     * This constructor uses Laravel's dependency injection to automatically
     * receive a ScriptExecutor instance. Laravel's service container will
     * automatically create and inject the ScriptExecutor when DomainBlockingService
     * is instantiated.
     * 
     * @param ScriptExecutor $scriptExecutor The script executor service (injected by Laravel)
     */
    public function __construct(ScriptExecutor $scriptExecutor)
    {
        $this->scriptExecutor = $scriptExecutor;
    }

    /**
     * Detect related domains for an app.
     * 
     * When a parent blocks an app (e.g., Facebook), we need to find all domains
     * that the app uses. This method looks up the app in predefined mappings and
     * returns all related domains.
     * 
     * How It Works:
     * - Takes a domain (e.g., 'facebook.com') and optional app name
     * - Looks up the domain in appDomainMappings array
     * - Returns array of related domains that should also be blocked
     * - If domain not found, returns empty array (parent can add manually)
     * 
     * Why This Method?
     * - Apps use multiple domains that parents may not know about
     * - System automatically suggests all related domains
     * - Saves parents time (don't have to manually research all domains)
     * - Improves blocking effectiveness (blocks all app domains, not just main one)
     * 
     * @param string $domain The main domain (e.g., 'facebook.com')
     * @param string|null $appName Optional app name for better matching (e.g., 'Facebook')
     * @return array<string> Array of related domains
     * 
     * Usage Example:
     * ```php
     * $service = new DomainBlockingService($scriptExecutor);
     * $domains = $service->detectRelatedDomains('facebook.com', 'Facebook');
     * // Returns: ['api.facebook.com', 'graph.facebook.com', 'm.facebook.com', ...]
     * ```
     */
    public function detectRelatedDomains(string $domain, ?string $appName = null): array
    {
        // Normalize domain to lowercase for lookup
        $domain = strtolower(trim($domain));
        
        // Remove www. prefix if present (www.facebook.com -> facebook.com)
        $domain = preg_replace('/^www\./', '', $domain);
        
        // Look up domain in predefined mappings
        if (isset($this->appDomainMappings[$domain])) {
            return $this->appDomainMappings[$domain];
        }
        
        // If not found, return empty array (parent can add manually)
        Log::info("No related domains found for domain: {$domain}", [
            'domain' => $domain,
            'app_name' => $appName,
        ]);
        
        return [];
    }

    /**
     * Block domain(s) for a specific device via DNS.
     * 
     * This method blocks one or more domains for a device by adding them to
     * the dnsmasq blocklist. It handles:
     * - Main domain blocking
     * - Related domains blocking (for app-level blocks)
     * - Subdomain blocking (wildcard patterns)
     * 
     * How It Works:
     * - Gets all domains to block from BlockedWebsite (main + related)
     * - Calls block_domain.sh script for each domain
     * - Handles subdomain blocking if block_subdomains is true
     * - Updates dnsmasq config and restarts service
     * 
     * Why DNS Blocking?
     * - Works for both web browsers AND mobile apps
     * - Apps use DNS to resolve domain names (just like browsers)
     * - DNS blocking is more effective than URL blocking for apps
     * - dnsmasq redirects blocked domains to 127.0.0.1 (localhost)
     * 
     * @param BlockedWebsite $blockedWebsite The blocked website record
     * @param Device $device The device to block domains for
     * @return bool True if successful, false otherwise
     * 
     * Usage Example:
     * ```php
     * $service = new DomainBlockingService($scriptExecutor);
     * $blockedWebsite = BlockedWebsite::find(1);
     * $device = Device::find(1);
     * 
     * if ($service->blockDomainForDevice($blockedWebsite, $device)) {
     *     echo "Domain blocked successfully";
     * }
     * ```
     */
    public function blockDomainForDevice(BlockedWebsite $blockedWebsite, Device $device): bool
    {
        try {
            // Get all domains to block (main + related)
            $domainsToBlock = $blockedWebsite->getDomainsToBlock();
            
            Log::info("Blocking domains for device", [
                'device_id' => $device->id,
                'device_name' => $device->name,
                'mac_address' => $device->mac_address,
                'domains' => $domainsToBlock,
                'block_subdomains' => $blockedWebsite->shouldBlockSubdomains(),
            ]);
            
            // Block each domain
            foreach ($domainsToBlock as $domain) {
                $blockSubdomains = $blockedWebsite->shouldBlockSubdomains() ? '1' : '0';
                
                // Execute block_domain.sh script
                $result = $this->scriptExecutor->execute('block_domain.sh', [
                    $domain,
                    $device->mac_address,
                    $blockSubdomains,
                ]);
                
                if (!$result['success']) {
                    Log::error("Failed to block domain for device", [
                        'domain' => $domain,
                        'device_id' => $device->id,
                        'error' => $result['error'] ?? 'Unknown error',
                    ]);
                    return false;
                }
            }
            
            // Update dnsmasq blocklist to ensure consistency
            return $this->updateDnsmasqBlocklist($device);
            
        } catch (\Exception $e) {
            Log::error("Exception while blocking domain for device", [
                'device_id' => $device->id,
                'blocked_website_id' => $blockedWebsite->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Unblock domain(s) for a specific device.
     * 
     * This method removes domain(s) from the dnsmasq blocklist for a device.
     * It handles:
     * - Main domain unblocking
     * - Related domains unblocking (for app-level blocks)
     * 
     * How It Works:
     * - Gets all domains to unblock from BlockedWebsite (main + related)
     * - Calls unblock_domain.sh script for each domain
     * - Updates dnsmasq config and restarts service
     * 
     * @param BlockedWebsite $blockedWebsite The blocked website record
     * @param Device $device The device to unblock domains for
     * @return bool True if successful, false otherwise
     * 
     * Usage Example:
     * ```php
     * $service = new DomainBlockingService($scriptExecutor);
     * $blockedWebsite = BlockedWebsite::find(1);
     * $device = Device::find(1);
     * 
     * if ($service->unblockDomainForDevice($blockedWebsite, $device)) {
     *     echo "Domain unblocked successfully";
     * }
     * ```
     */
    public function unblockDomainForDevice(BlockedWebsite $blockedWebsite, Device $device): bool
    {
        try {
            // Get all domains to unblock (main + related)
            $domainsToUnblock = $blockedWebsite->getDomainsToBlock();
            
            Log::info("Unblocking domains for device", [
                'device_id' => $device->id,
                'device_name' => $device->name,
                'mac_address' => $device->mac_address,
                'domains' => $domainsToUnblock,
            ]);
            
            // Unblock each domain
            foreach ($domainsToUnblock as $domain) {
                // Execute unblock_domain.sh script
                $result = $this->scriptExecutor->execute('unblock_domain.sh', [
                    $domain,
                    $device->mac_address,
                ]);
                
                if (!$result['success']) {
                    Log::error("Failed to unblock domain for device", [
                        'domain' => $domain,
                        'device_id' => $device->id,
                        'error' => $result['error'] ?? 'Unknown error',
                    ]);
                    return false;
                }
            }
            
            // Update dnsmasq blocklist to ensure consistency
            return $this->updateDnsmasqBlocklist($device);
            
        } catch (\Exception $e) {
            Log::error("Exception while unblocking domain for device", [
                'device_id' => $device->id,
                'blocked_website_id' => $blockedWebsite->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Update dnsmasq blocklist for a device.
     * 
     * This method regenerates the complete dnsmasq blocklist for a device from
     * the database. It ensures that the dnsmasq config matches the database state.
     * 
     * Why This Method?
     * - Database is the source of truth for blocked domains
     * - dnsmasq config can get out of sync (manual edits, script failures, etc.)
     * - This method ensures consistency by regenerating config from database
     * - Handles bulk updates, deletions, and complex scenarios
     * 
     * How It Works:
     * - Gets all blocked domains for device from database
     * - Calls update_dnsmasq_blocklist.sh script
     * - Script generates complete config file with all domains
     * - Restarts dnsmasq service to apply changes
     * 
     * When to Call:
     * - After creating/updating/deleting blocked websites
     * - After bulk imports
     * - Periodically to ensure consistency (optional)
     * 
     * @param Device $device The device to update blocklist for
     * @return bool True if successful, false otherwise
     * 
     * Usage Example:
     * ```php
     * $service = new DomainBlockingService($scriptExecutor);
     * $device = Device::find(1);
     * 
     * if ($service->updateDnsmasqBlocklist($device)) {
     *     echo "dnsmasq blocklist updated successfully";
     * }
     * ```
     */
    public function updateDnsmasqBlocklist(Device $device): bool
    {
        try {
            Log::info("Updating dnsmasq blocklist for device", [
                'device_id' => $device->id,
                'device_name' => $device->name,
                'mac_address' => $device->mac_address,
            ]);
            
            // Execute update_dnsmasq_blocklist.sh script
            $result = $this->scriptExecutor->execute('update_dnsmasq_blocklist.sh', [
                $device->mac_address,
            ]);
            
            if (!$result['success']) {
                Log::error("Failed to update dnsmasq blocklist for device", [
                    'device_id' => $device->id,
                    'error' => $result['error'] ?? 'Unknown error',
                ]);
                return false;
            }
            
            return true;
            
        } catch (\Exception $e) {
            Log::error("Exception while updating dnsmasq blocklist for device", [
                'device_id' => $device->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get all blocked domains for a device.
     * 
     * This method returns an array of all domains that are blocked for a device.
     * It includes:
     * - Main domains from blocked websites
     * - Related domains from app-level blocks
     * 
     * This is used by:
     * - update_dnsmasq_blocklist.sh script to generate dnsmasq config
     * - Controllers to display blocked domains
     * - Background jobs to check if domain should be blocked
     * 
     * @param Device $device The device to get blocked domains for
     * @return array<string> Array of domain names
     * 
     * Usage Example:
     * ```php
     * $service = new DomainBlockingService($scriptExecutor);
     * $device = Device::find(1);
     * 
     * $domains = $service->getBlockedDomainsForDevice($device);
     * // Returns: ['facebook.com', 'api.facebook.com', 'instagram.com', ...]
     * ```
     */
    public function getBlockedDomainsForDevice(Device $device): array
    {
        $allDomains = [];
        
        // Get all blocked websites for this device
        $blockedWebsites = BlockedWebsite::where('device_id', $device->id)->get();
        
        // Collect all domains (main + related)
        foreach ($blockedWebsites as $blockedWebsite) {
            $domains = $blockedWebsite->getDomainsToBlock();
            $allDomains = array_merge($allDomains, $domains);
        }
        
        // Remove duplicates and return
        return array_unique($allDomains);
    }

    /**
     * Normalize domain from URL.
     * 
     * This method extracts a clean domain name from a URL string.
     * It handles various URL formats:
     * - https://www.facebook.com/page -> facebook.com
     * - http://facebook.com -> facebook.com
     * - www.facebook.com -> facebook.com
     * - facebook.com -> facebook.com
     * 
     * Why This Method?
     * - URLs come in many formats (with/without protocol, www, paths, etc.)
     * - We need clean domain names for DNS blocking
     * - Consistent domain format makes blocking more reliable
     * 
     * @param string $url The URL to extract domain from
     * @return string Clean domain name (e.g., 'facebook.com')
     * 
     * Usage Example:
     * ```php
     * $service = new DomainBlockingService($scriptExecutor);
     * 
     * $domain = $service->normalizeDomain('https://www.facebook.com/page');
     * // Returns: 'facebook.com'
     * 
     * $domain = $service->normalizeDomain('http://api.facebook.com');
     * // Returns: 'api.facebook.com'
     * ```
     */
    public function normalizeDomain(string $url): string
    {
        // Remove protocol (http://, https://)
        $url = preg_replace('/^https?:\/\//', '', $url);
        
        // Remove path and query string (everything after /)
        $url = preg_replace('/\/.*$/', '', $url);
        
        // Remove port (everything after :)
        $url = preg_replace('/:.*$/', '', $url);
        
        // Remove www. prefix (optional, but cleaner)
        $url = preg_replace('/^www\./', '', $url);
        
        // Trim whitespace and convert to lowercase
        $url = strtolower(trim($url));
        
        return $url;
    }
}

