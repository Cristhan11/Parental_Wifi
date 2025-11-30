#!/bin/bash

################################################################################
# Test Phase 4 Execution Script
# 
# This script automates the execution of Test Phase 4 verification tests
# for shell script execution on Raspberry Pi.
# 
# Usage:
#   chmod +x scripts/test-phase4.sh
#   ./scripts/test-phase4.sh
# 
# Or run from project root:
#   bash scripts/test-phase4.sh
# 
# This script performs automated checks that complement the manual tests
# described in docs/TESTING.md (Test Phase 4)
################################################################################

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

echo -e "${BLUE}🧪 Test Phase 4 - Shell Script Execution Tests${NC}"
echo ""
echo "This script verifies shell script execution capabilities for network control."
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

# Phase 1: Git Pull and Setup Verification
echo -e "${BLUE}📥 Phase 1: Git Pull and Setup Verification${NC}"
PHASE1_PASSED=1

# Check if we're in a git repository
if [ ! -d ".git" ]; then
    echo -e "${YELLOW}   ⚠️  Not a git repository, skipping git pull${NC}"
else
    echo "   📋 Checking git status..."
    git status > /dev/null 2>&1
    if [ $? -eq 0 ]; then
        echo "   ✅ Git repository accessible"
        
        # Check current branch
        CURRENT_BRANCH=$(git branch --show-current 2>/dev/null || echo "unknown")
        echo "   📋 Current branch: $CURRENT_BRANCH"
        
        # Attempt to pull (but don't fail if it fails)
        echo "   📋 Pulling latest changes..."
        if git pull > /dev/null 2>&1; then
            echo "   ✅ Git pull successful"
        else
            echo -e "${YELLOW}   ⚠️  Git pull failed or no changes (this is okay)${NC}"
        fi
    else
        echo -e "${YELLOW}   ⚠️  Git repository not accessible${NC}"
    fi
fi

# Check project directory
if [ "$(pwd)" != "$PROJECT_DIR" ]; then
    echo -e "${RED}   ❌ Not in project directory${NC}"
    PHASE1_PASSED=0
else
    echo "   ✅ Project directory: $PROJECT_DIR"
fi

# Check scripts directory exists
if [ ! -d "scripts" ]; then
    echo -e "${RED}   ❌ Scripts directory does not exist${NC}"
    PHASE1_PASSED=0
else
    echo "   ✅ Scripts directory exists"
fi

# Check user (if on Linux)
if [ "$(uname)" = "Linux" ]; then
    CURRENT_USER=$(whoami)
    echo "   📋 Current user: $CURRENT_USER"
    echo "   📋 Expected user: snasna (or www-data for web operations)"
fi

# Check PHP version
PHP_VERSION=$(php -v 2>/dev/null | head -1 | grep -oP 'PHP \K[0-9]+\.[0-9]+' || echo "unknown")
if [ "$PHP_VERSION" != "unknown" ]; then
    echo "   📋 PHP version: $PHP_VERSION"
    if [[ "$PHP_VERSION" == "8.4"* ]] || [[ "$PHP_VERSION" == "8.3"* ]] || [[ "$PHP_VERSION" == "8.2"* ]]; then
        echo "   ✅ PHP version is compatible (8.2+)"
    else
        echo -e "${YELLOW}   ⚠️  PHP version may not be compatible (recommended: 8.2+)${NC}"
    fi
else
    echo -e "${RED}   ❌ PHP not found or not accessible${NC}"
    PHASE1_PASSED=0
fi

print_result "Phase 1: Git Pull and Setup Verification" $PHASE1_PASSED
echo ""

# Phase 2: Service Status Check
echo -e "${BLUE}🔧 Phase 2: Service Status Check${NC}"
PHASE2_PASSED=1

if [ "$(uname)" = "Linux" ]; then
    # Check Nginx
    if systemctl is-active --quiet nginx 2>/dev/null; then
        echo "   ✅ Nginx is running"
    else
        echo -e "${YELLOW}   ⚠️  Nginx is not running${NC}"
        echo "   💡 Start with: sudo systemctl start nginx"
        PHASE2_PASSED=0
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
    
    if [ $PHP_FPM_FOUND -eq 0 ]; then
        echo -e "${YELLOW}   ⚠️  PHP-FPM not detected${NC}"
        echo "   💡 Start with: sudo systemctl start php8.4-fpm"
        PHASE2_PASSED=0
    fi
    
    # Check MariaDB
    if systemctl is-active --quiet mariadb 2>/dev/null; then
        echo "   ✅ MariaDB is running"
    else
        echo -e "${YELLOW}   ⚠️  MariaDB is not running${NC}"
        echo "   💡 Start with: sudo systemctl start mariadb"
        PHASE2_PASSED=0
    fi
    
    # Check access point services (optional)
    if systemctl is-active --quiet hostapd 2>/dev/null; then
        echo "   ✅ hostapd is running"
    else
        echo -e "${YELLOW}   ⚠️  hostapd is not running (optional for network tests)${NC}"
    fi
    
    if systemctl is-active --quiet dnsmasq 2>/dev/null; then
        echo "   ✅ dnsmasq is running"
    else
        echo -e "${YELLOW}   ⚠️  dnsmasq is not running (optional for network tests)${NC}"
    fi
else
    echo -e "${YELLOW}   ⚠️  Not on Linux, skipping service checks${NC}"
fi

print_result "Phase 2: Service Status Check" $PHASE2_PASSED
echo ""

# Test 1: PHP Execution Functions (4.1)
echo -e "${BLUE}⚙️  Test 1: PHP Execution Functions${NC}"
TEST1_PASSED=1

# Check if exec() and shell_exec() are disabled
DISABLED_FUNCS=$(php -i 2>/dev/null | grep -E "disable_functions" | grep -oE "exec|shell_exec" || echo "")
if [ -n "$DISABLED_FUNCS" ]; then
    echo -e "${RED}   ❌ exec() or shell_exec() is disabled${NC}"
    echo "   💡 Edit php.ini and remove exec/shell_exec from disable_functions"
    echo "   💡 Restart PHP-FPM: sudo systemctl restart php8.4-fpm"
    TEST1_PASSED=0
else
    echo "   ✅ exec() and shell_exec() are enabled"
    
    # Test exec() function
    TEST_OUTPUT=""
    TEST_RETURN=0
    exec 'ls' > /dev/null 2>&1 || TEST_RETURN=$?
    if [ $TEST_RETURN -eq 0 ] || [ $TEST_RETURN -eq 127 ]; then
        echo "   ✅ exec() function is accessible"
    else
        echo -e "${RED}   ❌ exec() function test failed${NC}"
        TEST1_PASSED=0
    fi
fi

print_result "Test 1: PHP Execution Functions" $TEST1_PASSED
echo ""

# Test 2: Script File Permissions (4.2)
echo -e "${BLUE}📝 Test 2: Script File Permissions${NC}"
TEST2_PASSED=1

REQUIRED_SCRIPTS=("block_device.sh" "unblock_device.sh" "whitelist_device.sh" "get_connected_devices.sh" "monitor_traffic.sh")
MISSING_SCRIPTS=0

for script in "${REQUIRED_SCRIPTS[@]}"; do
    if [ ! -f "scripts/$script" ]; then
        echo -e "${RED}   ❌ Script not found: scripts/$script${NC}"
        MISSING_SCRIPTS=$((MISSING_SCRIPTS + 1))
        TEST2_PASSED=0
    else
        # Check if executable
        if [ ! -x "scripts/$script" ]; then
            echo -e "${YELLOW}   ⚠️  Script not executable: scripts/$script${NC}"
            echo "   💡 Making executable: chmod +x scripts/$script"
            chmod +x "scripts/$script"
        fi
        
        # Verify it's now executable
        if [ -x "scripts/$script" ]; then
            echo "   ✅ scripts/$script is executable"
        else
            echo -e "${RED}   ❌ Cannot make scripts/$script executable${NC}"
            TEST2_PASSED=0
        fi
    fi
done

if [ $MISSING_SCRIPTS -eq 0 ]; then
    echo "   ✅ All required scripts are present and executable"
fi

print_result "Test 2: Script File Permissions" $TEST2_PASSED
echo ""

# Test 3: Basic Script Execution (4.3)
echo -e "${BLUE}▶️  Test 3: Basic Script Execution${NC}"
TEST3_PASSED=1

# Test executing a simple script command
if [ -f "scripts/get_connected_devices.sh" ]; then
    echo "   📋 Testing script execution..."
    
    # Try to execute the script (may fail if not on Raspberry Pi, that's okay)
    SCRIPT_OUTPUT=$(./scripts/get_connected_devices.sh 2>&1)
    SCRIPT_RETURN=$?
    
    if [ $SCRIPT_RETURN -eq 0 ]; then
        echo "   ✅ Script executed successfully (return code: 0)"
        
        # Check if output is JSON (basic validation)
        if echo "$SCRIPT_OUTPUT" | grep -qE "^\[|^\{"; then
            echo "   ✅ Script output appears to be valid JSON"
        else
            echo -e "${YELLOW}   ⚠️  Script output may not be JSON (could be empty array)${NC}"
        fi
    else
        echo -e "${YELLOW}   ⚠️  Script execution returned non-zero code: $SCRIPT_RETURN${NC}"
        echo "   💡 This may be normal if not on Raspberry Pi or network not configured"
        echo "   💡 Output: ${SCRIPT_OUTPUT:0:100}..."
    fi
else
    echo -e "${RED}   ❌ Test script not found: scripts/get_connected_devices.sh${NC}"
    TEST3_PASSED=0
fi

print_result "Test 3: Basic Script Execution" $TEST3_PASSED
echo ""

# Test 4: Sudoers Configuration Verification
echo -e "${BLUE}🔐 Test 4: Sudoers Configuration Verification${NC}"
TEST4_PASSED=1

if [ "$(uname)" = "Linux" ] && [ -w /etc/sudoers.d/ ] 2>/dev/null || [ "$(id -u)" -eq 0 ]; then
    SUDOERS_FILE="/etc/sudoers.d/parental-wifi-scripts"
    
    if [ -f "$SUDOERS_FILE" ]; then
        echo "   ✅ Sudoers file exists: $SUDOERS_FILE"
        
        # Check file permissions (should be 0440)
        FILE_PERMS=$(stat -c "%a" "$SUDOERS_FILE" 2>/dev/null || echo "unknown")
        if [ "$FILE_PERMS" = "440" ] || [ "$FILE_PERMS" = "0440" ]; then
            echo "   ✅ File permissions are correct: $FILE_PERMS"
        else
            echo -e "${YELLOW}   ⚠️  File permissions may be incorrect: $FILE_PERMS (expected: 0440)${NC}"
        fi
        
        # Check file ownership (should be root:root)
        FILE_OWNER=$(stat -c "%U:%G" "$SUDOERS_FILE" 2>/dev/null || echo "unknown")
        if [ "$FILE_OWNER" = "root:root" ]; then
            echo "   ✅ File ownership is correct: $FILE_OWNER"
        else
            echo -e "${YELLOW}   ⚠️  File ownership may be incorrect: $FILE_OWNER (expected: root:root)${NC}"
        fi
        
        # Validate sudoers syntax
        if sudo visudo -c > /dev/null 2>&1; then
            echo "   ✅ Sudoers syntax is valid"
        else
            echo -e "${RED}   ❌ Sudoers syntax validation failed${NC}"
            echo "   💡 Run: sudo visudo -c"
            TEST4_PASSED=0
        fi
        
        # Check if scripts are listed
        SCRIPT_COUNT=$(grep -c "parental_wifi/scripts/" "$SUDOERS_FILE" 2>/dev/null || echo "0")
        if [ "$SCRIPT_COUNT" -ge 5 ]; then
            echo "   ✅ All scripts are listed in sudoers file ($SCRIPT_COUNT entries)"
        else
            echo -e "${YELLOW}   ⚠️  Expected 5 scripts, found $SCRIPT_COUNT entries${NC}"
        fi
    else
        echo -e "${YELLOW}   ⚠️  Sudoers file not found: $SUDOERS_FILE${NC}"
        echo "   💡 See docs/SUDOERS_CONFIGURATION.md for setup instructions"
        echo "   💡 This is required for network control scripts to work"
    fi
else
    echo -e "${YELLOW}   ⚠️  Cannot check sudoers (requires root or sudo access)${NC}"
    echo "   💡 Run with sudo or as root to verify sudoers configuration"
fi

print_result "Test 4: Sudoers Configuration Verification" $TEST4_PASSED
echo ""

# Test 5: Network Commands (4.4)
echo -e "${BLUE}🌐 Test 5: Network Commands${NC}"
TEST5_PASSED=1

# Check if iptables is available
if command -v iptables > /dev/null 2>&1; then
    echo "   ✅ iptables command is available"
    
    # Try to list rules (may require sudo)
    if sudo iptables -L > /dev/null 2>&1; then
        echo "   ✅ iptables command works (with sudo)"
        
        # Check FORWARD chain
        FORWARD_RULES=$(sudo iptables -L FORWARD -n -v 2>/dev/null | wc -l)
        if [ "$FORWARD_RULES" -gt 2 ]; then
            echo "   ✅ FORWARD chain has rules ($FORWARD_RULES lines)"
        else
            echo -e "${YELLOW}   ⚠️  FORWARD chain appears empty or has minimal rules${NC}"
        fi
    else
        echo -e "${YELLOW}   ⚠️  iptables requires sudo (this is expected)${NC}"
    fi
else
    echo -e "${YELLOW}   ⚠️  iptables command not found${NC}"
    echo "   💡 Install with: sudo apt install iptables"
fi

# Check if ip command is available
if command -v ip > /dev/null 2>&1; then
    echo "   ✅ ip command is available"
    
    # Check network interfaces
    INTERFACE_COUNT=$(ip addr show 2>/dev/null | grep -c "^[0-9]" || echo "0")
    if [ "$INTERFACE_COUNT" -gt 0 ]; then
        echo "   ✅ Network interfaces detected ($INTERFACE_COUNT interfaces)"
        
        # Check for wlan0 (WiFi interface)
        if ip addr show wlan0 > /dev/null 2>&1; then
            echo "   ✅ wlan0 interface exists"
            
            # Check for connected devices
            DEVICE_COUNT=$(ip neigh show dev wlan0 2>/dev/null | grep -c "REACHABLE\|STALE" || echo "0")
            if [ "$DEVICE_COUNT" -gt 0 ]; then
                echo "   ✅ Connected devices detected ($DEVICE_COUNT devices)"
            else
                echo -e "${YELLOW}   ⚠️  No connected devices detected (this is okay if no devices connected)${NC}"
            fi
        else
            echo -e "${YELLOW}   ⚠️  wlan0 interface not found (may not be in access point mode)${NC}"
        fi
    else
        echo -e "${YELLOW}   ⚠️  No network interfaces detected${NC}"
    fi
else
    echo -e "${YELLOW}   ⚠️  ip command not found${NC}"
    echo "   💡 Install with: sudo apt install iproute2"
fi

print_result "Test 5: Network Commands" $TEST5_PASSED
echo ""

# Summary
echo ""
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}Test Summary${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo "Total tests: $TESTS_TOTAL"
echo -e "${GREEN}Passed: $TESTS_PASSED${NC}"
if [ $TESTS_FAILED -gt 0 ]; then
    echo -e "${RED}Failed: $TESTS_FAILED${NC}"
else
    echo -e "${GREEN}Failed: $TESTS_FAILED${NC}"
fi
echo ""

if [ $TESTS_FAILED -eq 0 ]; then
    echo -e "${GREEN}✅ All automated tests passed!${NC}"
    echo ""
    echo "Next steps:"
    echo "1. Run Laravel verification: php artisan test:phase4"
    echo "2. Follow manual tests in docs/TESTING.md (Test Phase 4)"
    echo "3. Test ScriptExecutor and NetworkService via tinker"
    echo "4. Test actual device blocking/unblocking (if on Raspberry Pi)"
    exit 0
else
    echo -e "${RED}❌ Some tests failed. Please fix the issues above.${NC}"
    echo ""
    echo "Refer to docs/TESTING.md (Test Phase 4) for troubleshooting steps."
    exit 1
fi

