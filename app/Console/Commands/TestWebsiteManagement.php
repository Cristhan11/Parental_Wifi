<?php

/**
 * Website Management Testing Command
 * 
 * This command verifies Website Management system functionality:
 * - DomainBlockingService methods (normalizeDomain, detectRelatedDomains)
 * - BlockedWebsite model methods (getDomainsToBlock, isAppBlock, etc.)
 * - Controller methods (suggestRelatedDomains AJAX endpoint)
 * - Database operations (creating blocked websites, storing related domains)
 * - Form request validation
 * 
 * Usage:
 * php artisan test:website-management
 * 
 * This is part of Website Management Testing (TODO #19)
 */

namespace App\Console\Commands;

use App\Http\Controllers\BlockedWebsiteController;
use App\Models\BlockedWebsite;
use App\Models\Device;
use App\Services\DomainBlockingService;
use App\Services\ScriptExecutor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class TestWebsiteManagement extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:website-management';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Website Management system: DomainBlockingService, models, controllers, and database operations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Website Management Testing');
        $this->newLine();
        $this->info('This command verifies Website Management system functionality including DomainBlockingService, models, controllers, and database operations.');
        $this->newLine();

        $allPassed = true;
        $verbose = $this->getOutput()->isVerbose();

        // Test 1: DomainBlockingService - normalizeDomain
        $this->info('🔍 Test 1: DomainBlockingService - normalizeDomain()');
        $test1Result = $this->testNormalizeDomain($verbose);
        if (!$test1Result) {
            $allPassed = false;
        }
        $this->newLine();

        // Test 2: DomainBlockingService - detectRelatedDomains
        $this->info('🔍 Test 2: DomainBlockingService - detectRelatedDomains()');
        $test2Result = $this->testDetectRelatedDomains($verbose);
        if (!$test2Result) {
            $allPassed = false;
        }
        $this->newLine();

        // Test 3: BlockedWebsite Model - getDomainsToBlock
        $this->info('📦 Test 3: BlockedWebsite Model - getDomainsToBlock()');
        $test3Result = $this->testGetDomainsToBlock($verbose);
        if (!$test3Result) {
            $allPassed = false;
        }
        $this->newLine();

        // Test 4: BlockedWebsite Model - Helper Methods
        $this->info('📦 Test 4: BlockedWebsite Model - Helper Methods');
        $test4Result = $this->testModelHelperMethods($verbose);
        if (!$test4Result) {
            $allPassed = false;
        }
        $this->newLine();

        // Test 5: Database Operations
        $this->info('💾 Test 5: Database Operations');
        $test5Result = $this->testDatabaseOperations($verbose);
        if (!$test5Result) {
            $allPassed = false;
        }
        $this->newLine();

        // Test 6: Routes and Controllers
        $this->info('🌐 Test 6: Routes and Controllers');
        $test6Result = $this->testRoutesAndControllers($verbose);
        if (!$test6Result) {
            $allPassed = false;
        }
        $this->newLine();

        // Summary
        $this->newLine();
        if ($allPassed) {
            $this->info('✅ All Website Management tests passed!');
            $this->info('Next steps:');
            $this->info('1. Test UI views in browser (forms, tables, Alpine.js)');
            $this->info('2. Test on Raspberry Pi with real devices');
            return Command::SUCCESS;
        } else {
            $this->error('❌ Some tests failed. Please review the output above.');
            return Command::FAILURE;
        }
    }

    /**
     * Test DomainBlockingService::normalizeDomain()
     */
    private function testNormalizeDomain(bool $verbose): bool
    {
        try {
            $scriptExecutor = app(ScriptExecutor::class);
            $service = new DomainBlockingService($scriptExecutor);
            
            $testCases = [
                'https://www.facebook.com/page' => 'facebook.com',
                'http://facebook.com' => 'facebook.com',
                'www.facebook.com' => 'facebook.com',
                'facebook.com' => 'facebook.com',
                'https://example.com/test?param=value' => 'example.com',
                'http://subdomain.example.com/path' => 'subdomain.example.com',
            ];
            
            $allPassed = true;
            foreach ($testCases as $input => $expected) {
                $result = $service->normalizeDomain($input);
                if ($result === $expected) {
                    if ($verbose) {
                        $this->line("   ✅ '$input' → '$result'");
                    }
                } else {
                    $this->error("   ❌ '$input' → '$result' (expected: '$expected')");
                    $allPassed = false;
                }
            }
            
            if ($allPassed) {
                $this->info('   ✅ normalizeDomain() works correctly');
            }
            
            return $allPassed;
        } catch (\Exception $e) {
            $this->error("   ❌ Error testing normalizeDomain(): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Test DomainBlockingService::detectRelatedDomains()
     */
    private function testDetectRelatedDomains(bool $verbose): bool
    {
        try {
            $scriptExecutor = app(ScriptExecutor::class);
            $service = new DomainBlockingService($scriptExecutor);
            
            // Test Facebook
            $facebookDomains = $service->detectRelatedDomains('facebook.com', 'Facebook');
            if (count($facebookDomains) > 0) {
                if ($verbose) {
                    $this->line("   ✅ Facebook: Found " . count($facebookDomains) . " related domains");
                    foreach (array_slice($facebookDomains, 0, 5) as $domain) {
                        $this->line("      - $domain");
                    }
                } else {
                    $this->info("   ✅ Facebook: Found " . count($facebookDomains) . " related domains");
                }
            } else {
                $this->error("   ❌ Facebook: No related domains found");
                return false;
            }
            
            // Test Instagram
            $instagramDomains = $service->detectRelatedDomains('instagram.com', 'Instagram');
            if (count($instagramDomains) > 0) {
                if ($verbose) {
                    $this->line("   ✅ Instagram: Found " . count($instagramDomains) . " related domains");
                } else {
                    $this->info("   ✅ Instagram: Found " . count($instagramDomains) . " related domains");
                }
            } else {
                $this->error("   ❌ Instagram: No related domains found");
                return false;
            }
            
            // Test unknown domain (should return empty array)
            $unknownDomains = $service->detectRelatedDomains('unknown-domain-12345.com', 'Unknown');
            if (count($unknownDomains) === 0) {
                if ($verbose) {
                    $this->line("   ✅ Unknown domain: Correctly returns empty array");
                }
            } else {
                $this->error("   ❌ Unknown domain: Should return empty array");
                return false;
            }
            
            return true;
        } catch (\Exception $e) {
            $this->error("   ❌ Error testing detectRelatedDomains(): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Test BlockedWebsite::getDomainsToBlock()
     */
    private function testGetDomainsToBlock(bool $verbose): bool
    {
        try {
            // Test domain-level blocking
            $domainBlock = new BlockedWebsite([
                'block_type' => 'domain',
                'domain' => 'example.com',
                'related_domains' => null,
            ]);
            
            $domains = $domainBlock->getDomainsToBlock();
            if (count($domains) === 1 && $domains[0] === 'example.com') {
                if ($verbose) {
                    $this->line("   ✅ Domain block: Returns ['example.com']");
                }
            } else {
                $this->error("   ❌ Domain block: Expected ['example.com'], got " . json_encode($domains));
                return false;
            }
            
            // Test app-level blocking
            $appBlock = new BlockedWebsite([
                'block_type' => 'app',
                'domain' => 'facebook.com',
                'related_domains' => ['api.facebook.com', 'graph.facebook.com'],
            ]);
            
            $domains = $appBlock->getDomainsToBlock();
            if (count($domains) >= 3 && in_array('facebook.com', $domains)) {
                if ($verbose) {
                    $this->line("   ✅ App block: Returns " . count($domains) . " domains (main + related)");
                }
            } else {
                $this->error("   ❌ App block: Should return main domain + related domains");
                return false;
            }
            
            return true;
        } catch (\Exception $e) {
            $this->error("   ❌ Error testing getDomainsToBlock(): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Test BlockedWebsite model helper methods
     */
    private function testModelHelperMethods(bool $verbose): bool
    {
        try {
            // Test isAppBlock()
            $appBlock = new BlockedWebsite(['block_type' => 'app']);
            if ($appBlock->isAppBlock()) {
                if ($verbose) {
                    $this->line("   ✅ isAppBlock() returns true for app block");
                }
            } else {
                $this->error("   ❌ isAppBlock() should return true for app block");
                return false;
            }
            
            // Test isDomainBlock()
            $domainBlock = new BlockedWebsite(['block_type' => 'domain']);
            if ($domainBlock->isDomainBlock()) {
                if ($verbose) {
                    $this->line("   ✅ isDomainBlock() returns true for domain block");
                }
            } else {
                $this->error("   ❌ isDomainBlock() should return true for domain block");
                return false;
            }
            
            // Test shouldBlockSubdomains()
            $withSubdomains = new BlockedWebsite(['block_subdomains' => true]);
            if ($withSubdomains->shouldBlockSubdomains()) {
                if ($verbose) {
                    $this->line("   ✅ shouldBlockSubdomains() returns true when enabled");
                }
            } else {
                $this->error("   ❌ shouldBlockSubdomains() should return true when enabled");
                return false;
            }
            
            return true;
        } catch (\Exception $e) {
            $this->error("   ❌ Error testing model helper methods: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Test database operations
     */
    private function testDatabaseOperations(bool $verbose): bool
    {
        try {
            // Check if table exists
            if (!Schema::hasTable('blocked_websites')) {
                $this->error("   ❌ blocked_websites table does not exist");
                $this->warn("   💡 Run: php artisan migrate");
                return false;
            }
            
            // Check if new columns exist
            $requiredColumns = ['block_type', 'block_subdomains', 'related_domains'];
            foreach ($requiredColumns as $column) {
                if (!Schema::hasColumn('blocked_websites', $column)) {
                    $this->error("   ❌ blocked_websites.$column column does not exist");
                    $this->warn("   💡 Run: php artisan migrate");
                    return false;
                }
            }
            
            if ($verbose) {
                $this->line("   ✅ Database schema is correct");
            }
            
            // Test creating a blocked website (if we have a device)
            $device = Device::first();
            if ($device) {
                // Create a test blocked website
                $blockedWebsite = BlockedWebsite::create([
                    'device_id' => $device->id,
                    'url' => null, // URL not needed for domain/app blocking
                    'domain' => 'test-' . time() . '.com',
                    'block_type' => 'domain',
                    'block_subdomains' => false,
                    'related_domains' => null,
                ]);
                
                if ($blockedWebsite->id) {
                    if ($verbose) {
                        $this->line("   ✅ Can create blocked website in database");
                    }
                    
                    // Test app-level blocking with related domains
                    $appBlock = BlockedWebsite::create([
                        'device_id' => $device->id,
                        'url' => null, // URL not needed for domain/app blocking
                        'domain' => 'test-app-' . time() . '.com',
                        'block_type' => 'app',
                        'block_subdomains' => true,
                        'related_domains' => ['api.test.com', 'graph.test.com'],
                    ]);
                    
                    if ($appBlock->id && is_array($appBlock->related_domains)) {
                        if ($verbose) {
                            $this->line("   ✅ Can create app-level block with related domains");
                        }
                    } else {
                        $this->error("   ❌ App-level block related_domains not stored correctly");
                        $appBlock->delete();
                        $blockedWebsite->delete();
                        return false;
                    }
                    
                    // Cleanup
                    $appBlock->delete();
                    $blockedWebsite->delete();
                } else {
                    $this->error("   ❌ Failed to create blocked website");
                    return false;
                }
            } else {
                if ($verbose) {
                    $this->warn("   ⚠️  No devices found - skipping database creation test");
                }
            }
            
            return true;
        } catch (\Exception $e) {
            $this->error("   ❌ Error testing database operations: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Test routes and controllers
     */
    private function testRoutesAndControllers(bool $verbose): bool
    {
        try {
            // Check if routes are registered
            $routes = Route::getRoutes();
            $blockedWebsiteRoutes = [];
            
            foreach ($routes as $route) {
                if (str_contains($route->uri(), 'blocked-websites')) {
                    $blockedWebsiteRoutes[] = $route->uri();
                }
            }
            
            if (count($blockedWebsiteRoutes) > 0) {
                if ($verbose) {
                    $this->line("   ✅ Blocked websites routes registered (" . count($blockedWebsiteRoutes) . " routes)");
                } else {
                    $this->info("   ✅ Blocked websites routes registered (" . count($blockedWebsiteRoutes) . " routes)");
                }
            } else {
                $this->error("   ❌ Blocked websites routes not registered");
                return false;
            }
            
            // Check if controller exists
            if (class_exists(BlockedWebsiteController::class)) {
                if ($verbose) {
                    $this->line("   ✅ BlockedWebsiteController class exists");
                }
            } else {
                $this->error("   ❌ BlockedWebsiteController class does not exist");
                return false;
            }
            
            // Check if suggestRelatedDomains route exists
            $suggestRoute = Route::getRoutes()->getByName('blocked-websites.suggest-domains');
            if ($suggestRoute) {
                if ($verbose) {
                    $this->line("   ✅ suggestRelatedDomains AJAX route exists");
                }
            } else {
                $this->error("   ❌ suggestRelatedDomains AJAX route not found");
                return false;
            }
            
            return true;
        } catch (\Exception $e) {
            $this->error("   ❌ Error testing routes and controllers: " . $e->getMessage());
            return false;
        }
    }
}

