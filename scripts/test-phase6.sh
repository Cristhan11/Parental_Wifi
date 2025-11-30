#!/bin/bash

################################################################################
# Test Phase 6 Execution Script
# 
# This script automates the execution of Test Phase 6 verification tests
# for Portal Core system integration.
# 
# Usage:
#   chmod +x scripts/test-phase6.sh
#   ./scripts/test-phase6.sh
# 
# Or run from project root:
#   bash scripts/test-phase6.sh
# 
# This script performs automated checks that complement the manual tests
# described in docs/TESTING.md
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

echo -e "${BLUE}🧪 Test Phase 6 - Portal Core System Integration Tests${NC}"
echo ""
echo "This script verifies the complete Portal Core workflow:"
echo "time expiration → portal redirect → quiz/video → time granting → unblocking"
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

# Test 1: Database Connectivity
echo -e "${BLUE}💾 Test 1: Database Connectivity${NC}"
TEST1_PASSED=1

if php artisan db:show >/dev/null 2>&1; then
    echo "   ✅ Database connection successful"
else
    echo -e "${RED}   ❌ Database connection failed${NC}"
    echo "   💡 Check .env file and database credentials"
    TEST1_PASSED=0
fi

print_result "Test 1: Database Connectivity" $TEST1_PASSED
echo ""

# Test 2: Queue System (for CheckTimeExpiration job)
echo -e "${BLUE}📬 Test 2: Queue System Configuration${NC}"
TEST2_PASSED=1

# Check if queue configuration exists
if grep -q "QUEUE_CONNECTION" .env 2>/dev/null; then
    QUEUE_CONNECTION=$(grep "QUEUE_CONNECTION" .env | cut -d '=' -f2 | tr -d ' ')
    echo "   📋 Queue connection: $QUEUE_CONNECTION"
    
    if [ "$QUEUE_CONNECTION" = "database" ] || [ "$QUEUE_CONNECTION" = "redis" ] || [ "$QUEUE_CONNECTION" = "sync" ]; then
        echo "   ✅ Queue connection is configured"
        
        # If using database queue, check if jobs table exists
        if [ "$QUEUE_CONNECTION" = "database" ]; then
            if php artisan migrate:status | grep -q "jobs" 2>/dev/null; then
                echo "   ✅ Jobs table exists (database queue)"
            else
                echo -e "${YELLOW}   ⚠️  Jobs table may not exist${NC}"
                echo "   💡 Run: php artisan queue:table && php artisan migrate"
            fi
        fi
    else
        echo -e "${YELLOW}   ⚠️  Queue connection may not be properly configured${NC}"
    fi
else
    echo -e "${YELLOW}   ⚠️  QUEUE_CONNECTION not found in .env${NC}"
    echo "   💡 Add QUEUE_CONNECTION=database to .env"
fi

print_result "Test 2: Queue System Configuration" $TEST2_PASSED
echo ""

# Test 3: CheckTimeExpiration Job File
echo -e "${BLUE}🔍 Test 3: CheckTimeExpiration Job File${NC}"
TEST3_PASSED=1

JOB_FILE="app/Jobs/CheckTimeExpiration.php"
if [ -f "$JOB_FILE" ]; then
    echo "   ✅ CheckTimeExpiration.php exists"
    
    # Check if job is scheduled
    if grep -q "CheckTimeExpiration" routes/console.php 2>/dev/null; then
        echo "   ✅ CheckTimeExpiration is scheduled in routes/console.php"
    else
        echo -e "${YELLOW}   ⚠️  CheckTimeExpiration may not be scheduled${NC}"
        echo "   💡 Check routes/console.php"
    fi
else
    echo -e "${RED}   ❌ CheckTimeExpiration.php not found${NC}"
    TEST3_PASSED=0
fi

print_result "Test 3: CheckTimeExpiration Job File" $TEST3_PASSED
echo ""

# Test 4: Portal Controller and Routes
echo -e "${BLUE}🌐 Test 4: Portal Controller and Routes${NC}"
TEST4_PASSED=1

CONTROLLER_FILE="app/Http/Controllers/PortalController.php"
if [ -f "$CONTROLLER_FILE" ]; then
    echo "   ✅ PortalController.php exists"
else
    echo -e "${RED}   ❌ PortalController.php not found${NC}"
    TEST4_PASSED=0
fi

# Check if portal routes exist in web.php
if grep -q "portal" routes/web.php 2>/dev/null; then
    echo "   ✅ Portal routes exist in routes/web.php"
else
    echo -e "${RED}   ❌ Portal routes not found in routes/web.php${NC}"
    TEST4_PASSED=0
fi

print_result "Test 4: Portal Controller and Routes" $TEST4_PASSED
echo ""

# Test 5: Time Granting Service
echo -e "${BLUE}🎁 Test 5: Time Granting Service${NC}"
TEST5_PASSED=1

SERVICE_FILE="app/Services/TimeGrantingService.php"
if [ -f "$SERVICE_FILE" ]; then
    echo "   ✅ TimeGrantingService.php exists"
    
    # Check for required methods
    if grep -q "grantTime" "$SERVICE_FILE" && grep -q "grantTimeFromQuiz" "$SERVICE_FILE" && grep -q "grantTimeFromVideo" "$SERVICE_FILE"; then
        echo "   ✅ Required methods exist in TimeGrantingService"
    else
        echo -e "${YELLOW}   ⚠️  Some required methods may be missing${NC}"
    fi
else
    echo -e "${RED}   ❌ TimeGrantingService.php not found${NC}"
    TEST5_PASSED=0
fi

print_result "Test 5: Time Granting Service" $TEST5_PASSED
echo ""

# Test 6: Time Tracking Service
echo -e "${BLUE}⏰ Test 6: Time Tracking Service${NC}"
TEST6_PASSED=1

SERVICE_FILE="app/Services/TimeTrackingService.php"
if [ -f "$SERVICE_FILE" ]; then
    echo "   ✅ TimeTrackingService.php exists"
    
    # Check for getExpiredDevices method
    if grep -q "getExpiredDevices" "$SERVICE_FILE"; then
        echo "   ✅ getExpiredDevices() method exists"
    else
        echo -e "${YELLOW}   ⚠️  getExpiredDevices() method not found${NC}"
    fi
else
    echo -e "${RED}   ❌ TimeTrackingService.php not found${NC}"
    TEST6_PASSED=0
fi

print_result "Test 6: Time Tracking Service" $TEST6_PASSED
echo ""

# Test 7: Device Model Methods
echo -e "${BLUE}📱 Test 7: Device Model Methods${NC}"
TEST7_PASSED=1

MODEL_FILE="app/Models/Device.php"
if [ -f "$MODEL_FILE" ]; then
    echo "   ✅ Device.php exists"
    
    # Check for required methods
    REQUIRED_METHODS=("hasTimeExpired" "hasRemainingTime" "isBlocked" "isActive" "grantTime")
    for method in "${REQUIRED_METHODS[@]}"; do
        if grep -q "function $method" "$MODEL_FILE"; then
            echo "   ✅ Device::$method() exists"
        else
            echo -e "${YELLOW}   ⚠️  Device::$method() not found${NC}"
        fi
    done
else
    echo -e "${RED}   ❌ Device.php not found${NC}"
    TEST7_PASSED=0
fi

print_result "Test 7: Device Model Methods" $TEST7_PASSED
echo ""

# Test 8: Run Laravel Artisan Command
echo -e "${BLUE}🔧 Test 8: Laravel Artisan Test Command${NC}"
TEST8_PASSED=1

if php artisan test:phase6 >/dev/null 2>&1; then
    echo "   ✅ test:phase6 command executed successfully"
    echo ""
    echo "   Running detailed tests..."
    php artisan test:phase6
    TEST8_PASSED=$?
    
    if [ $TEST8_PASSED -eq 0 ]; then
        TEST8_PASSED=1
    else
        TEST8_PASSED=0
    fi
else
    echo -e "${RED}   ❌ test:phase6 command failed or not found${NC}"
    echo "   💡 Check if TestPhase6Verification command exists"
    TEST8_PASSED=0
fi

print_result "Test 8: Laravel Artisan Test Command" $TEST8_PASSED
echo ""

# Test 9: Web Server Status (Linux only)
if [ "$(uname)" = "Linux" ]; then
    echo -e "${BLUE}🌐 Test 9: Web Server Status${NC}"
    TEST9_PASSED=1
    
    # Check Nginx
    if systemctl is-active --quiet nginx 2>/dev/null; then
        echo "   ✅ Nginx is running"
    else
        # Check Apache
        if systemctl is-active --quiet apache2 2>/dev/null; then
            echo "   ✅ Apache is running"
        else
            echo -e "${YELLOW}   ⚠️  No web server detected (Nginx/Apache not running)${NC}"
            echo "   💡 Start web server: sudo systemctl start nginx"
            TEST9_PASSED=0
        fi
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
        echo "   💡 Start PHP-FPM: sudo systemctl start php8.4-fpm"
        TEST9_PASSED=0
    fi
    
    print_result "Test 9: Web Server Status" $TEST9_PASSED
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
    echo "1. Run detailed verification: php artisan test:phase6"
    echo "2. Follow manual tests in docs/TESTING.md (Test Phase 6 section)"
    echo "3. Test with real device: Set device time to 0, trigger CheckTimeExpiration job"
    echo "4. Test portal access: http://your-ip/portal?mac=AA:BB:CC:DD:EE:FF"
    echo "5. Test quiz/video completion and time granting"
    exit 0
else
    echo -e "${RED}❌ Some tests failed. Please fix the issues above.${NC}"
    echo ""
    echo "Refer to docs/TESTING.md for troubleshooting steps."
    exit 1
fi

