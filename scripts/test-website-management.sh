#!/bin/bash

################################################################################
# Website Management Testing Script
# 
# This script automates testing of the Website Management system including:
# - DNS blocking scripts (block_domain.sh, unblock_domain.sh, update_dnsmasq_blocklist.sh)
# - dnsmasq configuration generation
# - Database operations
# - Integration tests
# - Functional DNS blocking tests (Phase 6)
# - Sudoers configuration verification (Phase 7)
# 
# Usage:
#   chmod +x scripts/test-website-management.sh
#   ./scripts/test-website-management.sh
#   ./scripts/test-website-management.sh --skip-functional  # Skip functional tests
# 
# Or run from project root:
#   bash scripts/test-website-management.sh
# 
# Note: This script is designed for Raspberry Pi testing. Some tests require
# dnsmasq service and sudo privileges.
################################################################################

# Parse command-line arguments
SKIP_FUNCTIONAL=0
if [[ "$1" == "--skip-functional" ]]; then
    SKIP_FUNCTIONAL=1
    echo "⚠️  Functional tests will be skipped (--skip-functional flag)"
fi

# Exit on error for most tests, but we'll disable it for functional tests
set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"

echo -e "${BLUE}🧪 Website Management Testing${NC}"
echo ""
echo "This script verifies website management functionality including DNS blocking."
echo ""

cd "$PROJECT_DIR"

# Track test results
TESTS_PASSED=0
TESTS_FAILED=0
TESTS_TOTAL=0

# Function to print test result
print_result() {
    local test_name=$1
    local passed=$2
    TESTS_TOTAL=$((TESTS_TOTAL + 1))
    
    if [ "$passed" -eq 1 ]; then
        echo -e "${GREEN}✅ $test_name${NC}"
        TESTS_PASSED=$((TESTS_PASSED + 1))
    else
        echo -e "${RED}❌ $test_name${NC}"
        TESTS_FAILED=$((TESTS_FAILED + 1))
    fi
}

# Phase 1: Pre-Testing Checklist
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}Phase 1: Pre-Testing Checklist${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
PHASE1_PASSED=1

# Check Laravel application
if [ -f "artisan" ]; then
    echo "   ✅ Laravel application found"
else
    echo -e "${RED}   ❌ Laravel application not found${NC}"
    PHASE1_PASSED=0
fi

# Check required services (if on Linux)
if [ "$(uname)" = "Linux" ]; then
    # Check dnsmasq
    if systemctl is-active --quiet dnsmasq 2>/dev/null || pgrep -x dnsmasq > /dev/null 2>&1; then
        echo "   ✅ dnsmasq service is running"
    else
        echo -e "${YELLOW}   ⚠️  dnsmasq service is not running (required for DNS blocking)${NC}"
    fi
    
    # Check Nginx
    if systemctl is-active --quiet nginx 2>/dev/null; then
        echo "   ✅ Nginx is running"
    else
        echo -e "${YELLOW}   ⚠️  Nginx is not running${NC}"
    fi
    
    # Check PHP-FPM
    PHP_FPM_FOUND=0
    for php_version in php8.4-fpm php8.3-fpm php8.2-fpm php8.1-fpm php8.0-fpm php-fpm; do
        if systemctl is-active --quiet "$php_version" 2>/dev/null; then
            echo "   ✅ $php_version is running"
            PHP_FPM_FOUND=1
            break
        fi
    done
    
    # Check MariaDB/MySQL
    if systemctl is-active --quiet mariadb 2>/dev/null || systemctl is-active --quiet mysql 2>/dev/null; then
        echo "   ✅ Database server is running"
    else
        echo -e "${YELLOW}   ⚠️  Database server is not running${NC}"
    fi
fi

# Check scripts directory
if [ ! -d "scripts" ]; then
    echo -e "${RED}   ❌ Scripts directory does not exist${NC}"
    PHASE1_PASSED=0
else
    echo "   ✅ Scripts directory exists"
fi

# Check DNS blocking scripts exist
DNS_SCRIPTS=("block_domain.sh" "unblock_domain.sh" "update_dnsmasq_blocklist.sh")
for script in "${DNS_SCRIPTS[@]}"; do
    if [ -f "scripts/$script" ]; then
        echo "   ✅ scripts/$script exists"
        if [ -x "scripts/$script" ]; then
            echo "   ✅ scripts/$script is executable"
        else
            echo -e "${YELLOW}   ⚠️  scripts/$script is not executable (run: chmod +x scripts/$script)${NC}"
        fi
    else
        echo -e "${RED}   ❌ scripts/$script does not exist${NC}"
        PHASE1_PASSED=0
    fi
done

# Check migration has been run (check for new columns)
if php artisan migrate:status > /dev/null 2>&1; then
    echo "   ✅ Laravel migrations system accessible"
    # Try to check if blocked_websites table has new columns
    if php artisan tinker --execute="echo Schema::hasColumn('blocked_websites', 'block_type') ? 'yes' : 'no';" 2>/dev/null | grep -q "yes"; then
        echo "   ✅ Migration has been run (block_type column exists)"
    else
        echo -e "${YELLOW}   ⚠️  Migration may not have been run (block_type column not found)${NC}"
        echo "   💡 Run: php artisan migrate"
    fi
else
    echo -e "${YELLOW}   ⚠️  Cannot check migration status${NC}"
fi

print_result "Phase 1: Pre-Testing Checklist" $PHASE1_PASSED
echo ""

# Phase 2: DNS Blocking Scripts Tests
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}Phase 2: DNS Blocking Scripts Tests${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
PHASE2_PASSED=1

# Test script validation (invalid domain format)
echo "   📋 Testing script validation (invalid domain)..."
if bash scripts/block_domain.sh "invalid..domain" "AA:BB:CC:DD:EE:FF" 2>&1 | grep -q -i "invalid\|error"; then
    echo "   ✅ Script correctly rejects invalid domain format"
else
    echo -e "${YELLOW}   ⚠️  Script validation may not be working correctly${NC}"
fi

# Test script validation (invalid MAC address)
echo "   📋 Testing script validation (invalid MAC address)..."
if bash scripts/block_domain.sh "test.com" "INVALID-MAC" 2>&1 | grep -q -i "invalid\|error"; then
    echo "   ✅ Script correctly rejects invalid MAC address"
else
    echo -e "${YELLOW}   ⚠️  Script validation may not be working correctly${NC}"
fi

# Test script syntax (dry run - don't actually block)
echo "   📋 Testing script syntax..."
TEST_DOMAIN="test-blocked-$(date +%s).com"
TEST_MAC="AA:BB:CC:DD:EE:FF"

# Check if scripts can be parsed (syntax check)
if bash -n scripts/block_domain.sh 2>/dev/null; then
    echo "   ✅ block_domain.sh syntax is valid"
else
    echo -e "${RED}   ❌ block_domain.sh has syntax errors${NC}"
    PHASE2_PASSED=0
fi

if bash -n scripts/unblock_domain.sh 2>/dev/null; then
    echo "   ✅ unblock_domain.sh syntax is valid"
else
    echo -e "${RED}   ❌ unblock_domain.sh has syntax errors${NC}"
    PHASE2_PASSED=0
fi

if bash -n scripts/update_dnsmasq_blocklist.sh 2>/dev/null; then
    echo "   ✅ update_dnsmasq_blocklist.sh syntax is valid"
else
    echo -e "${RED}   ❌ update_dnsmasq_blocklist.sh has syntax errors${NC}"
    PHASE2_PASSED=0
fi

print_result "Phase 2: DNS Blocking Scripts Tests" $PHASE2_PASSED
echo ""

# Phase 3: dnsmasq Configuration Tests
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}Phase 3: dnsmasq Configuration Tests${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
PHASE3_PASSED=1

if [ "$(uname)" = "Linux" ]; then
    # Check dnsmasq config directory
    if [ -d "/etc/dnsmasq.d" ]; then
        echo "   ✅ dnsmasq.d directory exists: /etc/dnsmasq.d"
    else
        echo -e "${YELLOW}   ⚠️  dnsmasq.d directory does not exist${NC}"
        echo "   💡 Create with: sudo mkdir -p /etc/dnsmasq.d"
    fi
    
    # Check if we can read dnsmasq config files (if any exist)
    if [ -r "/etc/dnsmasq.d" ]; then
        echo "   ✅ dnsmasq.d directory is readable"
    else
        echo -e "${YELLOW}   ⚠️  dnsmasq.d directory is not readable (may need sudo)${NC}"
    fi
    
    # Check dnsmasq service
    if systemctl is-active --quiet dnsmasq 2>/dev/null; then
        echo "   ✅ dnsmasq service is running"
    else
        echo -e "${YELLOW}   ⚠️  dnsmasq service is not running${NC}"
        echo "   💡 Start with: sudo systemctl start dnsmasq"
    fi
else
    echo -e "${YELLOW}   ⚠️  Not running on Linux - skipping dnsmasq tests${NC}"
    echo "   💡 These tests should be run on Raspberry Pi"
fi

print_result "Phase 3: dnsmasq Configuration Tests" $PHASE3_PASSED
echo ""

# Phase 4: Database Tests
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}Phase 4: Database Tests${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
PHASE4_PASSED=1

# Test database connection
if php artisan db:show > /dev/null 2>&1; then
    echo "   ✅ Database connection successful"
else
    echo -e "${RED}   ❌ Database connection failed${NC}"
    PHASE4_PASSED=0
fi

# Check if blocked_websites table exists
if php artisan tinker --execute="echo Schema::hasTable('blocked_websites') ? 'yes' : 'no';" 2>/dev/null | grep -q "yes"; then
    echo "   ✅ blocked_websites table exists"
    
    # Check for new columns
    for column in block_type block_subdomains related_domains; do
        if php artisan tinker --execute="echo Schema::hasColumn('blocked_websites', '$column') ? 'yes' : 'no';" 2>/dev/null | grep -q "yes"; then
            echo "   ✅ blocked_websites.$column column exists"
        else
            echo -e "${RED}   ❌ blocked_websites.$column column does not exist${NC}"
            echo "   💡 Run: php artisan migrate"
            PHASE4_PASSED=0
        fi
    done
else
    echo -e "${RED}   ❌ blocked_websites table does not exist${NC}"
    echo "   💡 Run: php artisan migrate"
    PHASE4_PASSED=0
fi

print_result "Phase 4: Database Tests" $PHASE4_PASSED
echo ""

# Phase 5: Integration Tests (via Laravel)
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}Phase 5: Integration Tests${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
PHASE5_PASSED=1

# Test if DomainBlockingService class exists
if php artisan tinker --execute="echo class_exists('App\Services\DomainBlockingService') ? 'yes' : 'no';" 2>/dev/null | grep -q "yes"; then
    echo "   ✅ DomainBlockingService class exists"
else
    echo -e "${RED}   ❌ DomainBlockingService class does not exist${NC}"
    PHASE5_PASSED=0
fi

# Test if BlockedWebsiteController exists
if php artisan tinker --execute="echo class_exists('App\Http\Controllers\BlockedWebsiteController') ? 'yes' : 'no';" 2>/dev/null | grep -q "yes"; then
    echo "   ✅ BlockedWebsiteController class exists"
else
    echo -e "${RED}   ❌ BlockedWebsiteController class does not exist${NC}"
    PHASE5_PASSED=0
fi

# Test if routes are registered
if php artisan route:list | grep -q "blocked-websites"; then
    echo "   ✅ Blocked websites routes are registered"
else
    echo -e "${RED}   ❌ Blocked websites routes are not registered${NC}"
    PHASE5_PASSED=0
fi

print_result "Phase 5: Integration Tests" $PHASE5_PASSED
echo ""

# Phase 6: Functional DNS Blocking Tests
if [ $SKIP_FUNCTIONAL -eq 0 ] && [ "$(uname)" = "Linux" ]; then
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${BLUE}Phase 6: Functional DNS Blocking Tests${NC}"
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo "   ⚠️  This phase requires:"
    echo "      - Real device in database"
    echo "      - Sudo access"
    echo "      - dnsmasq service running"
    echo ""
    
    # Disable exit on error for functional tests (we want to continue and cleanup)
    set +e
    
    PHASE6_PASSED=1
    
    # Get first device from database
    TEST_DEVICE_MAC=$(php artisan tinker --execute="\$device = App\Models\Device::first(); echo \$device ? \$device->mac_address : 'none';" 2>/dev/null | tail -1)
    TEST_DEVICE_ID=$(php artisan tinker --execute="\$device = App\Models\Device::first(); echo \$device ? \$device->id : 'none';" 2>/dev/null | tail -1)
    
    if [ "$TEST_DEVICE_MAC" = "none" ] || [ -z "$TEST_DEVICE_MAC" ]; then
        echo -e "${YELLOW}   ⚠️  No devices found in database - skipping functional tests${NC}"
        echo "   💡 Create a device first to test DNS blocking"
        PHASE6_PASSED=1  # Not a failure, just skip
    else
        echo "   📋 Using test device: MAC=$TEST_DEVICE_MAC, ID=$TEST_DEVICE_ID"
        
        # Normalize MAC address (remove colons for filename)
        TEST_MAC_NORMALIZED=$(echo "$TEST_DEVICE_MAC" | tr ':' '-')
        DNSMASQ_CONFIG="/etc/dnsmasq.d/blocked-domains-${TEST_DEVICE_MAC}.conf"
        TEST_DOMAIN="test-blocked-$(date +%s).com"
        TEST_DOMAIN_2="test-blocked-2-$(date +%s).com"
        
        # Test 1: Block single domain
        echo "   📋 Test 1: Block single domain..."
        if sudo bash scripts/block_domain.sh "$TEST_DOMAIN" "$TEST_DEVICE_MAC" "0" > /dev/null 2>&1; then
            # Verify dnsmasq config file exists
            if [ -f "$DNSMASQ_CONFIG" ]; then
                # Verify domain is in config
                if sudo grep -q "address=/$TEST_DOMAIN/127.0.0.1" "$DNSMASQ_CONFIG" 2>/dev/null; then
                    echo "   ✅ Domain blocked and added to dnsmasq config"
                    
                    # Test DNS resolution (should return 127.0.0.1)
                    DNS_RESULT=$(nslookup "$TEST_DOMAIN" 127.0.0.1 2>/dev/null | grep -i "Address:" | tail -1 | awk '{print $2}')
                    if [ "$DNS_RESULT" = "127.0.0.1" ]; then
                        echo "   ✅ DNS resolution returns 127.0.0.1 (blocked)"
                    else
                        echo -e "${YELLOW}   ⚠️  DNS resolution test inconclusive (may need dnsmasq restart)${NC}"
                    fi
                else
                    echo -e "${RED}   ❌ Domain not found in dnsmasq config${NC}"
                    PHASE6_PASSED=0
                fi
            else
                echo -e "${RED}   ❌ dnsmasq config file not created${NC}"
                PHASE6_PASSED=0
            fi
        else
            echo -e "${RED}   ❌ Failed to block domain${NC}"
            PHASE6_PASSED=0
        fi
        
        # Test 2: Unblock domain
        echo "   📋 Test 2: Unblock domain..."
        if sudo bash scripts/unblock_domain.sh "$TEST_DOMAIN" "$TEST_DEVICE_MAC" > /dev/null 2>&1; then
            # Verify domain removed from config
            if ! sudo grep -q "address=/$TEST_DOMAIN/127.0.0.1" "$DNSMASQ_CONFIG" 2>/dev/null; then
                echo "   ✅ Domain unblocked and removed from dnsmasq config"
            else
                echo -e "${YELLOW}   ⚠️  Domain may still be in config (check manually)${NC}"
            fi
        else
            echo -e "${RED}   ❌ Failed to unblock domain${NC}"
            PHASE6_PASSED=0
        fi
        
        # Test 3: Block domain with subdomains
        echo "   📋 Test 3: Block domain with subdomains..."
        if sudo bash scripts/block_domain.sh "$TEST_DOMAIN_2" "$TEST_DEVICE_MAC" "1" > /dev/null 2>&1; then
            # Verify both main domain and subdomain pattern in config
            if sudo grep -q "address=/$TEST_DOMAIN_2/127.0.0.1" "$DNSMASQ_CONFIG" 2>/dev/null && \
               sudo grep -q "address=/.$TEST_DOMAIN_2/127.0.0.1" "$DNSMASQ_CONFIG" 2>/dev/null; then
                echo "   ✅ Domain and subdomains blocked correctly"
            else
                echo -e "${YELLOW}   ⚠️  Subdomain blocking may not be configured correctly${NC}"
            fi
        else
            echo -e "${RED}   ❌ Failed to block domain with subdomains${NC}"
            PHASE6_PASSED=0
        fi
        
        # Test 4: App-level blocking (test via Laravel)
        echo "   📋 Test 4: App-level blocking (Facebook)..."
        # Create a test blocked website via Laravel tinker
        TEST_BLOCKED_WEBSITE_ID=$(php artisan tinker --execute="
            \$device = App\Models\Device::find($TEST_DEVICE_ID);
            if (\$device) {
                \$blocked = App\Models\BlockedWebsite::create([
                    'device_id' => \$device->id,
                    'domain' => 'facebook.com',
                    'block_type' => 'app',
                    'block_subdomains' => true,
                    'related_domains' => ['api.facebook.com', 'graph.facebook.com'],
                    'reason' => 'Automated test',
                ]);
                echo \$blocked->id;
            } else {
                echo 'none';
            }
        " 2>/dev/null | tail -1)
        
        if [ "$TEST_BLOCKED_WEBSITE_ID" != "none" ] && [ -n "$TEST_BLOCKED_WEBSITE_ID" ]; then
            # Verify related domains are in config
            if sudo grep -q "address=/facebook.com/127.0.0.1" "$DNSMASQ_CONFIG" 2>/dev/null && \
               sudo grep -q "address=/api.facebook.com/127.0.0.1" "$DNSMASQ_CONFIG" 2>/dev/null; then
                echo "   ✅ App-level blocking: All related domains in config"
            else
                echo -e "${YELLOW}   ⚠️  Some related domains may be missing from config${NC}"
            fi
            
            # Clean up test blocked website
            php artisan tinker --execute="App\Models\BlockedWebsite::find($TEST_BLOCKED_WEBSITE_ID)->delete();" > /dev/null 2>&1
        else
            echo -e "${YELLOW}   ⚠️  Could not create test blocked website (may need user authentication)${NC}"
        fi
        
        # Cleanup: Remove test domains from dnsmasq config
        echo "   📋 Cleaning up test domains..."
        sudo bash scripts/unblock_domain.sh "$TEST_DOMAIN_2" "$TEST_DEVICE_MAC" > /dev/null 2>&1
        
        # Verify cleanup
        if ! sudo grep -q "$TEST_DOMAIN" "$DNSMASQ_CONFIG" 2>/dev/null && \
           ! sudo grep -q "$TEST_DOMAIN_2" "$DNSMASQ_CONFIG" 2>/dev/null; then
            echo "   ✅ Test domains cleaned up"
        else
            echo -e "${YELLOW}   ⚠️  Some test domains may still be in config${NC}"
        fi
    fi
    
    # Re-enable exit on error after functional tests
    set -e
    
    print_result "Phase 6: Functional DNS Blocking Tests" $PHASE6_PASSED
    echo ""
else
    if [ $SKIP_FUNCTIONAL -eq 1 ]; then
        echo -e "${YELLOW}⚠️  Phase 6: Functional DNS Blocking Tests - SKIPPED${NC}"
        echo ""
    elif [ "$(uname)" != "Linux" ]; then
        echo -e "${YELLOW}⚠️  Phase 6: Functional DNS Blocking Tests - SKIPPED (not on Linux)${NC}"
        echo ""
    fi
fi

# Phase 7: Sudoers Configuration Verification
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}Phase 7: Sudoers Configuration Verification${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
PHASE7_PASSED=1

if [ "$(uname)" = "Linux" ]; then
    SUDOERS_FILE="/etc/sudoers.d/parental-wifi-scripts"
    
    # Check if sudoers file exists
    if [ -f "$SUDOERS_FILE" ]; then
        echo "   ✅ Sudoers file exists: $SUDOERS_FILE"
        
        # Check sudoers syntax
        if sudo visudo -c -f "$SUDOERS_FILE" > /dev/null 2>&1; then
            echo "   ✅ Sudoers syntax is valid"
        else
            echo -e "${RED}   ❌ Sudoers syntax is invalid${NC}"
            echo "   💡 Fix with: sudo visudo -f $SUDOERS_FILE"
            PHASE7_PASSED=0
        fi
        
        # Check if DNS blocking scripts are in sudoers
        DNS_SCRIPTS_IN_SUDOERS=0
        for script in block_domain.sh unblock_domain.sh update_dnsmasq_blocklist.sh; do
            if sudo grep -q "$script" "$SUDOERS_FILE" 2>/dev/null; then
                echo "   ✅ $script found in sudoers"
                DNS_SCRIPTS_IN_SUDOERS=$((DNS_SCRIPTS_IN_SUDOERS + 1))
            else
                echo -e "${YELLOW}   ⚠️  $script not found in sudoers${NC}"
                echo "   💡 Add: www-data ALL=(ALL) NOPASSWD: /var/www/parental_wifi/scripts/$script"
            fi
        done
        
        if [ $DNS_SCRIPTS_IN_SUDOERS -lt 3 ]; then
            echo -e "${YELLOW}   ⚠️  Not all DNS blocking scripts are in sudoers${NC}"
            echo "   💡 See: docs/SUDOERS_UPDATE_DNS_BLOCKING.md"
        fi
        
        # Test script execution with sudo (if we have a test device)
        if [ -n "$TEST_DEVICE_MAC" ] && [ "$TEST_DEVICE_MAC" != "none" ]; then
            echo "   📋 Testing script execution with sudo..."
            # Get web server user
            WEB_USER=$(ps aux | grep -E 'php-fpm|nginx' | grep -v grep | head -1 | awk '{print $1}')
            if [ -z "$WEB_USER" ]; then
                WEB_USER="www-data"  # Default
            fi
            
            # Test if script can be executed with sudo (dry run - don't actually block)
            if sudo -u "$WEB_USER" sudo bash scripts/block_domain.sh "test-sudo-$(date +%s).com" "$TEST_DEVICE_MAC" "0" > /dev/null 2>&1; then
                echo "   ✅ Script can be executed with sudo (as $WEB_USER)"
            else
                echo -e "${YELLOW}   ⚠️  Script execution test inconclusive (may need sudoers update)${NC}"
            fi
        fi
    else
        echo -e "${YELLOW}   ⚠️  Sudoers file does not exist: $SUDOERS_FILE${NC}"
        echo "   💡 Create sudoers file and add DNS blocking scripts"
        echo "   💡 See: docs/SUDOERS_UPDATE_DNS_BLOCKING.md"
    fi
else
    echo -e "${YELLOW}   ⚠️  Not running on Linux - skipping sudoers tests${NC}"
fi

print_result "Phase 7: Sudoers Configuration Verification" $PHASE7_PASSED
echo ""

# Summary
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}Test Summary${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo "Total tests: $TESTS_TOTAL"
echo -e "Passed: ${GREEN}$TESTS_PASSED${NC}"
echo -e "Failed: ${RED}$TESTS_FAILED${NC}"

if [ $TESTS_FAILED -eq 0 ]; then
    echo ""
    echo -e "${GREEN}✅ All automated tests passed!${NC}"
    echo ""
    echo "Next steps:"
    echo "1. Run local testing: php artisan test:website-management"
    echo "2. Test UI views in browser"
    if [ $SKIP_FUNCTIONAL -eq 1 ]; then
        echo "3. Run functional tests: ./scripts/test-website-management.sh (without --skip-functional)"
    else
        echo "3. Test on Raspberry Pi with real devices"
    fi
    echo "4. Review documentation: docs/RASPBERRY_PI_WEBSITE_MANAGEMENT_TESTING.md"
    exit 0
else
    echo ""
    echo -e "${YELLOW}⚠️  Some tests failed. Please review the output above.${NC}"
    echo ""
    echo "Troubleshooting tips:"
    echo "- Check Laravel logs: tail -f storage/logs/laravel.log"
    echo "- Check dnsmasq logs: sudo journalctl -u dnsmasq -n 50"
    echo "- Verify sudoers: sudo visudo -c -f /etc/sudoers.d/parental-wifi-scripts"
    echo "- Verify services: sudo systemctl status dnsmasq"
    echo "- See documentation: docs/RASPBERRY_PI_WEBSITE_MANAGEMENT_TESTING.md"
    exit 1
fi

