#!/bin/bash

################################################################################
# Test Phase 3 Execution Script
# 
# This script automates the execution of Test Phase 3 verification tests
# for Raspberry Pi video storage system.
# 
# Usage:
#   chmod +x scripts/test-phase3.sh
#   ./scripts/test-phase3.sh
# 
# Or run from project root:
#   bash scripts/test-phase3.sh
# 
# This script performs automated checks that complement the manual tests
# described in docs/VIDEO_SYSTEM_TESTING.md
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

echo -e "${BLUE}🧪 Test Phase 3 - Raspberry Pi File System & Storage Tests${NC}"
echo ""
echo "This script verifies critical file system operations for video storage."
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

# Test 1: Storage Directory Permissions
echo -e "${BLUE}📁 Test 1: Storage Directory Permissions${NC}"
TEST1_PASSED=1

STORAGE_DIR="storage/app/public/videos"
if [ ! -d "$STORAGE_DIR" ]; then
    echo -e "${RED}   ❌ Directory does not exist: $STORAGE_DIR${NC}"
    echo "   💡 Run: mkdir -p $STORAGE_DIR"
    TEST1_PASSED=0
else
    echo "   ✅ Directory exists: $STORAGE_DIR"
    
    # Check if writable
    if [ ! -w "$STORAGE_DIR" ]; then
        echo -e "${RED}   ❌ Directory is not writable${NC}"
        echo "   💡 Run: chmod -R 775 $STORAGE_DIR"
        echo "   💡 Run: chown -R www-data:www-data $STORAGE_DIR"
        TEST1_PASSED=0
    else
        echo "   ✅ Directory is writable"
    fi
    
    # Test write access
    TEST_FILE="$STORAGE_DIR/.test_write"
    if echo "test" > "$TEST_FILE" 2>/dev/null; then
        echo "   ✅ Write test successful"
        rm -f "$TEST_FILE"
    else
        echo -e "${RED}   ❌ Cannot write test file${NC}"
        TEST1_PASSED=0
    fi
fi

print_result "Test 1: Storage Directory Permissions" $TEST1_PASSED
echo ""

# Test 2: Symlink Functionality
echo -e "${BLUE}🔗 Test 2: Symlink Functionality${NC}"
TEST2_PASSED=1

SYMLINK_PATH="public/storage"
if [ ! -e "$SYMLINK_PATH" ]; then
    echo -e "${RED}   ❌ Symlink does not exist: $SYMLINK_PATH${NC}"
    echo "   💡 Run: php artisan storage:link"
    TEST2_PASSED=0
else
    if [ -L "$SYMLINK_PATH" ]; then
        echo "   ✅ Symlink exists: $SYMLINK_PATH"
        TARGET=$(readlink "$SYMLINK_PATH")
        echo "   📋 Symlink points to: $TARGET"
        
        if [ -d "$SYMLINK_PATH" ]; then
            echo "   ✅ Symlink target is valid and accessible"
        else
            echo -e "${RED}   ❌ Symlink target is invalid${NC}"
            TEST2_PASSED=0
        fi
    else
        echo -e "${YELLOW}   ⚠️  Path exists but is not a symlink${NC}"
        echo "   💡 Remove directory and run: php artisan storage:link"
        TEST2_PASSED=0
    fi
fi

print_result "Test 2: Symlink Functionality" $TEST2_PASSED
echo ""

# Test 3: PHP Upload Limits
echo -e "${BLUE}⚙️  Test 3: PHP Upload Limits${NC}"
TEST3_PASSED=1

# Get PHP configuration
UPLOAD_MAX=$(php -r "echo ini_get('upload_max_filesize');")
POST_MAX=$(php -r "echo ini_get('post_max_size');")

echo "   📋 upload_max_filesize: $UPLOAD_MAX"
echo "   📋 post_max_size: $POST_MAX"

# Convert to bytes (simplified check)
if [[ "$UPLOAD_MAX" == *"M"* ]]; then
    UPLOAD_MB=$(echo "$UPLOAD_MAX" | sed 's/M//' | sed 's/m//')
    if [ "$UPLOAD_MB" -ge 512 ]; then
        echo "   ✅ upload_max_filesize is sufficient (>= 512M)"
    else
        echo -e "${RED}   ❌ upload_max_filesize is too small: $UPLOAD_MAX${NC}"
        echo "   💡 Update php.ini: upload_max_filesize = 512M"
        TEST3_PASSED=0
    fi
else
    echo -e "${YELLOW}   ⚠️  Cannot parse upload_max_filesize: $UPLOAD_MAX${NC}"
fi

if [[ "$POST_MAX" == *"M"* ]]; then
    POST_MB=$(echo "$POST_MAX" | sed 's/M//' | sed 's/m//')
    if [ "$POST_MB" -ge 512 ]; then
        echo "   ✅ post_max_size is sufficient (>= 512M)"
    else
        echo -e "${RED}   ❌ post_max_size is too small: $POST_MAX${NC}"
        echo "   💡 Update php.ini: post_max_size = 512M"
        TEST3_PASSED=0
    fi
else
    echo -e "${YELLOW}   ⚠️  Cannot parse post_max_size: $POST_MAX${NC}"
fi

print_result "Test 3: PHP Upload Limits" $TEST3_PASSED
echo ""

# Test 4: Storage Space
echo -e "${BLUE}💾 Test 4: Storage Space Availability${NC}"
TEST4_PASSED=1

STORAGE_DIR_ABS=$(realpath "$STORAGE_DIR" 2>/dev/null || echo "$PROJECT_DIR/$STORAGE_DIR")
FREE_SPACE=$(df -BG "$STORAGE_DIR_ABS" | tail -1 | awk '{print $4}' | sed 's/G//')

if [ -z "$FREE_SPACE" ]; then
    echo -e "${RED}   ❌ Cannot determine available storage space${NC}"
    TEST4_PASSED=0
else
    echo "   📋 Free space: ${FREE_SPACE}GB"
    
    if [ "$FREE_SPACE" -ge 1 ] || [ "$FREE_SPACE" -ge 0 ]; then
        # Check if at least 100MB (0.1GB) is available
        FREE_MB=$(df -BM "$STORAGE_DIR_ABS" | tail -1 | awk '{print $4}' | sed 's/M//')
        if [ "$FREE_MB" -ge 100 ]; then
            echo "   ✅ Sufficient storage space available (${FREE_MB}MB free)"
        else
            echo -e "${RED}   ❌ Insufficient storage space: ${FREE_MB}MB free (need 100MB)${NC}"
            TEST4_PASSED=0
        fi
    else
        echo -e "${RED}   ❌ Insufficient storage space${NC}"
        TEST4_PASSED=0
    fi
fi

print_result "Test 4: Storage Space Availability" $TEST4_PASSED
echo ""

# Test 5: File Size Limit Validation (check code)
echo -e "${BLUE}📏 Test 5: File Size Limit Enforcement${NC}"
TEST5_PASSED=1

REQUEST_FILE="app/Http/Requests/StoreVideoRequest.php"
if [ ! -f "$REQUEST_FILE" ]; then
    echo -e "${RED}   ❌ StoreVideoRequest.php not found${NC}"
    TEST5_PASSED=0
else
    if grep -q "max:512000\|max:524288" "$REQUEST_FILE"; then
        echo "   ✅ File size validation rule exists in StoreVideoRequest"
    else
        echo -e "${RED}   ❌ File size validation rule not found${NC}"
        echo "   💡 Add 'max:512000' rule to video_file validation"
        TEST5_PASSED=0
    fi
fi

print_result "Test 5: File Size Limit Enforcement" $TEST5_PASSED
echo ""

# Test 6: Web Server Status (Linux only)
if [ "$(uname)" = "Linux" ]; then
    echo -e "${BLUE}🌐 Test 6: Web Server Status${NC}"
    TEST6_PASSED=1
    
    # Check Nginx
    if systemctl is-active --quiet nginx 2>/dev/null; then
        echo "   ✅ Nginx is running"
        NGINX_VERSION=$(nginx -v 2>&1 | head -1)
        echo "      $NGINX_VERSION"
    else
        # Check Apache
        if systemctl is-active --quiet apache2 2>/dev/null; then
            echo "   ✅ Apache is running"
        else
            echo -e "${YELLOW}   ⚠️  No web server detected (Nginx/Apache not running)${NC}"
            echo "   💡 Start web server: sudo systemctl start nginx"
            TEST6_PASSED=0
        fi
    fi
    
    # Check PHP-FPM
    PHP_FPM_FOUND=0
    for php_version in php8.2-fpm php8.1-fpm php8.0-fpm php-fpm; do
        if systemctl is-active --quiet "$php_version" 2>/dev/null; then
            echo "   ✅ $php_version is running"
            PHP_FPM_FOUND=1
            break
        fi
    done
    
    if [ $PHP_FPM_FOUND -eq 0 ]; then
        echo -e "${YELLOW}   ⚠️  PHP-FPM not detected${NC}"
        echo "   💡 Start PHP-FPM: sudo systemctl start php8.2-fpm"
        TEST6_PASSED=0
    fi
    
    print_result "Test 6: Web Server Status" $TEST6_PASSED
    echo ""
fi

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
    echo "1. Run Laravel verification: php artisan test:phase3"
    echo "2. Follow manual tests in docs/VIDEO_SYSTEM_TESTING.md"
    echo "3. Upload a test video (20MB+) via Parent Dashboard"
    echo "4. Test video playback via portal from WiFi-connected device"
    exit 0
else
    echo -e "${RED}❌ Some tests failed. Please fix the issues above.${NC}"
    echo ""
    echo "Refer to docs/VIDEO_SYSTEM_TESTING.md for troubleshooting steps."
    exit 1
fi

