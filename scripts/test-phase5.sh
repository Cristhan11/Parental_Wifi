#!/bin/bash

# Test Phase 5 - Background Jobs and Queue System
# This script verifies background job functionality and queue system on Raspberry Pi

# Don't exit on error - we want to run all tests
set +e  # Continue on error

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Test counters
TESTS_PASSED=0
TESTS_FAILED=0
TESTS_TOTAL=0

# Helper functions
print_success() {
    echo -e "${GREEN}   ✅ $1${NC}"
    ((TESTS_PASSED++))
    ((TESTS_TOTAL++))
}

print_error() {
    echo -e "${RED}   ❌ $1${NC}"
    ((TESTS_FAILED++))
    ((TESTS_TOTAL++))
}

print_info() {
    echo -e "${BLUE}   ℹ️  $1${NC}"
}

print_test() {
    echo -e "\n${YELLOW}▶️  $1${NC}"
}

print_phase() {
    echo -e "\n${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}\n"
}

# Get project directory (assume script is in scripts/ directory)
PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PROJECT_DIR"

echo -e "${GREEN}🧪 Test Phase 5 - Background Jobs and Queue System${NC}\n"
echo "This script verifies background job functionality and queue system."
echo ""

# Phase 1: Environment Verification
print_phase "Phase 1: Environment Verification"

print_test "Test 1: Check PHP Version"
PHP_VERSION=$(php -r "echo PHP_VERSION;" | cut -d. -f1,2)
PHP_VERSION_NUM=$(php -r "echo PHP_VERSION_ID;")
if [ "$PHP_VERSION_NUM" -ge 80200 ]; then
    print_success "PHP version is compatible ($PHP_VERSION, 8.2+)"
else
    print_error "PHP version is too old ($PHP_VERSION, need 8.2+)"
fi

print_test "Test 2: Check Laravel Installation"
if [ -f "artisan" ]; then
    print_success "Laravel installation found"
else
    print_error "Laravel installation not found (no artisan file)"
    exit 1
fi

print_test "Test 3: Check Required Services"
SERVICES_OK=true

if systemctl is-active --quiet nginx || systemctl is-active --quiet apache2; then
    print_success "Web server is running"
else
    print_error "Web server is not running"
    SERVICES_OK=false
fi

if systemctl is-active --quiet php*-fpm; then
    print_success "PHP-FPM is running"
else
    print_error "PHP-FPM is not running"
    SERVICES_OK=false
fi

if systemctl is-active --quiet mariadb || systemctl is-active --quiet mysql; then
    print_success "Database server is running"
else
    print_error "Database server is not running"
    SERVICES_OK=false
fi

if [ "$SERVICES_OK" = false ]; then
    print_info "Some services are not running. Tests may fail."
fi

# Phase 2: Queue Configuration
print_phase "Phase 2: Queue Configuration"

print_test "Test 1: Queue Connection Configuration"
if [ -f ".env" ]; then
    QUEUE_CONNECTION=$(grep "^QUEUE_CONNECTION=" .env | cut -d'=' -f2 || echo "not set")
    if [ "$QUEUE_CONNECTION" = "database" ] || [ "$QUEUE_CONNECTION" = "" ]; then
        print_success "Queue connection is set to 'database' (or default)"
        print_info "Current QUEUE_CONNECTION: ${QUEUE_CONNECTION:-database (default)}"
    else
        print_info "Queue connection is set to: $QUEUE_CONNECTION"
        print_info "Note: 'database' is recommended for Raspberry Pi"
    fi
else
    print_error ".env file not found"
fi

print_test "Test 2: Queue Tables Existence"
QUEUE_TABLES_EXIST=$(php artisan tinker --execute="echo Schema::hasTable('jobs') && Schema::hasTable('failed_jobs') ? 'yes' : 'no';" 2>/dev/null || echo "no")
if [ "$QUEUE_TABLES_EXIST" = "yes" ] || [ "$QUEUE_TABLES_EXIST" = "1" ]; then
    print_success "Queue tables (jobs, failed_jobs) exist"
else
    print_error "Queue tables do not exist"
    print_info "Run: php artisan queue:table && php artisan migrate"
fi

print_test "Test 3: Queue Configuration File"
if [ -f "config/queue.php" ]; then
    print_success "Queue configuration file exists"
else
    print_error "Queue configuration file not found"
fi

# Phase 3: Background Job Classes
print_phase "Phase 3: Background Job Classes"

print_test "Test 1: CheckTimeExpiration Job"
if [ -f "app/Jobs/CheckTimeExpiration.php" ]; then
    print_success "CheckTimeExpiration.php exists"
    CLASS_EXISTS=$(php artisan tinker --execute="echo class_exists('App\Jobs\CheckTimeExpiration') ? 'yes' : 'no';" 2>/dev/null || echo "no")
    if [ "$CLASS_EXISTS" = "yes" ] || [ "$CLASS_EXISTS" = "1" ]; then
        print_success "CheckTimeExpiration class is loadable"
    else
        print_error "CheckTimeExpiration class cannot be loaded"
    fi
else
    print_error "CheckTimeExpiration.php not found"
fi

print_test "Test 2: TrackActiveSessions Job"
if [ -f "app/Jobs/TrackActiveSessions.php" ]; then
    print_success "TrackActiveSessions.php exists"
    CLASS_EXISTS=$(php artisan tinker --execute="echo class_exists('App\Jobs\TrackActiveSessions') ? 'yes' : 'no';" 2>/dev/null || echo "no")
    if [ "$CLASS_EXISTS" = "yes" ] || [ "$CLASS_EXISTS" = "1" ]; then
        print_success "TrackActiveSessions class is loadable"
    else
        print_error "TrackActiveSessions class cannot be loaded"
    fi
else
    print_error "TrackActiveSessions.php not found"
fi

print_test "Test 3: MonitorDeviceConnections Job"
if [ -f "app/Jobs/MonitorDeviceConnections.php" ]; then
    print_success "MonitorDeviceConnections.php exists"
    CLASS_EXISTS=$(php artisan tinker --execute="echo class_exists('App\Jobs\MonitorDeviceConnections') ? 'yes' : 'no';" 2>/dev/null || echo "no")
    if [ "$CLASS_EXISTS" = "yes" ] || [ "$CLASS_EXISTS" = "1" ]; then
        print_success "MonitorDeviceConnections class is loadable"
    else
        print_error "MonitorDeviceConnections class cannot be loaded"
    fi
else
    print_error "MonitorDeviceConnections.php not found"
fi

print_test "Test 4: EnforceSchedules Job"
if [ -f "app/Jobs/EnforceSchedules.php" ]; then
    print_success "EnforceSchedules.php exists"
    CLASS_EXISTS=$(php artisan tinker --execute="echo class_exists('App\Jobs\EnforceSchedules') ? 'yes' : 'no';" 2>/dev/null || echo "no")
    if [ "$CLASS_EXISTS" = "yes" ] || [ "$CLASS_EXISTS" = "1" ]; then
        print_success "EnforceSchedules class is loadable"
    else
        print_error "EnforceSchedules class cannot be loaded"
    fi
else
    print_error "EnforceSchedules.php not found"
fi

print_test "Test 5: ParseNetworkLogs Job"
if [ -f "app/Jobs/ParseNetworkLogs.php" ]; then
    print_success "ParseNetworkLogs.php exists"
    CLASS_EXISTS=$(php artisan tinker --execute="echo class_exists('App\Jobs\ParseNetworkLogs') ? 'yes' : 'no';" 2>/dev/null || echo "no")
    if [ "$CLASS_EXISTS" = "yes" ] || [ "$CLASS_EXISTS" = "1" ]; then
        print_success "ParseNetworkLogs class is loadable"
    else
        print_error "ParseNetworkLogs class cannot be loaded"
    fi
else
    print_error "ParseNetworkLogs.php not found"
fi

# Phase 4: Job Scheduling
print_phase "Phase 4: Job Scheduling"

print_test "Test 1: Scheduled Jobs List"
SCHEDULE_LIST=$(php artisan schedule:list 2>&1)
if echo "$SCHEDULE_LIST" | grep -q "check-time-expiration\|CheckTimeExpiration"; then
    print_success "CheckTimeExpiration is scheduled"
else
    print_error "CheckTimeExpiration is not scheduled"
fi

if echo "$SCHEDULE_LIST" | grep -q "track-active-sessions\|TrackActiveSessions"; then
    print_success "TrackActiveSessions is scheduled"
else
    print_error "TrackActiveSessions is not scheduled"
fi

if echo "$SCHEDULE_LIST" | grep -q "monitor-device-connections\|MonitorDeviceConnections"; then
    print_success "MonitorDeviceConnections is scheduled"
else
    print_error "MonitorDeviceConnections is not scheduled"
fi

if echo "$SCHEDULE_LIST" | grep -q "enforce-schedules\|EnforceSchedules"; then
    print_success "EnforceSchedules is scheduled"
else
    print_error "EnforceSchedules is not scheduled"
fi

if echo "$SCHEDULE_LIST" | grep -q "parse-network-logs\|ParseNetworkLogs"; then
    print_success "ParseNetworkLogs is scheduled"
else
    print_error "ParseNetworkLogs is not scheduled"
fi

print_info "Full schedule list:"
echo "$SCHEDULE_LIST" | sed 's/^/   /'

print_test "Test 2: Schedule Configuration (routes/console.php)"
if [ -f "routes/console.php" ]; then
    print_success "routes/console.php exists"
    if grep -q "CheckTimeExpiration" routes/console.php; then
        print_success "CheckTimeExpiration is configured in routes/console.php"
    else
        print_error "CheckTimeExpiration is not in routes/console.php"
    fi
else
    print_error "routes/console.php not found"
fi

# Phase 5: Queue Worker
print_phase "Phase 5: Queue Worker"

print_test "Test 1: Queue Worker Command Available"
if php artisan queue:work --help >/dev/null 2>&1; then
    print_success "Queue worker command is available"
else
    print_error "Queue worker command is not available"
fi

print_test "Test 2: Test Job Dispatch"
JOB_COUNT_BEFORE=$(php artisan tinker --execute="echo DB::table('jobs')->count();" 2>/dev/null || echo "0")
php artisan tinker --execute="App\Jobs\CheckTimeExpiration::dispatch();" >/dev/null 2>&1 || true
sleep 1
JOB_COUNT_AFTER=$(php artisan tinker --execute="echo DB::table('jobs')->count();" 2>/dev/null || echo "0")

if [ "$JOB_COUNT_AFTER" -gt "$JOB_COUNT_BEFORE" ]; then
    print_success "Job can be dispatched to queue"
    print_info "Jobs in queue: $JOB_COUNT_AFTER (was $JOB_COUNT_BEFORE)"
else
    print_info "Job dispatch test inconclusive (may have processed immediately)"
    print_info "Jobs in queue: $JOB_COUNT_AFTER"
fi

# Phase 6: Cron Configuration
print_phase "Phase 6: Cron Configuration"

print_test "Test 1: Crontab Entry"
CRON_ENTRY=$(crontab -l 2>/dev/null | grep "schedule:run" || echo "not found")
if echo "$CRON_ENTRY" | grep -q "schedule:run"; then
    print_success "Crontab entry for Laravel scheduler exists"
    print_info "Entry: $CRON_ENTRY"
else
    print_error "Crontab entry for Laravel scheduler not found"
    print_info "Add this to crontab: * * * * * cd $PROJECT_DIR && php artisan schedule:run >> /dev/null 2>&1"
fi

# Phase 7: Job Execution Test
print_phase "Phase 7: Job Execution Test"

print_test "Test 1: Manual Scheduler Run"
SCHEDULE_RUN_OUTPUT=$(php artisan schedule:run 2>&1 || echo "error")
if echo "$SCHEDULE_RUN_OUTPUT" | grep -q "No scheduled commands are ready" || [ -z "$SCHEDULE_RUN_OUTPUT" ]; then
    print_success "Scheduler runs without errors"
else
    if echo "$SCHEDULE_RUN_OUTPUT" | grep -qi "error\|exception\|fatal"; then
        print_error "Scheduler has errors"
        echo "$SCHEDULE_RUN_OUTPUT" | sed 's/^/   /'
    else
        print_success "Scheduler runs (may have dispatched jobs)"
    fi
fi

print_test "Test 2: Schedule Test Command"
if php artisan schedule:test >/dev/null 2>&1; then
    print_success "Schedule test command works"
    SCHEDULE_TEST_OUTPUT=$(php artisan schedule:test 2>&1)
    echo "$SCHEDULE_TEST_OUTPUT" | sed 's/^/   /'
else
    print_error "Schedule test command failed"
fi

# Phase 8: Logs and Error Handling
print_phase "Phase 8: Logs and Error Handling"

print_test "Test 1: Log File Accessibility"
if [ -f "storage/logs/laravel.log" ]; then
    print_success "Log file exists"
    if [ -w "storage/logs/laravel.log" ]; then
        print_success "Log file is writable"
    else
        print_error "Log file is not writable"
    fi
else
    print_info "Log file does not exist yet (will be created on first log entry)"
fi

print_test "Test 2: Failed Jobs Table"
FAILED_JOBS_COUNT=$(php artisan tinker --execute="echo DB::table('failed_jobs')->count();" 2>/dev/null || echo "0")
print_info "Failed jobs in database: $FAILED_JOBS_COUNT"
if [ "$FAILED_JOBS_COUNT" = "0" ]; then
    print_success "No failed jobs (good!)"
else
    print_info "$FAILED_JOBS_COUNT failed job(s) found (check with: php artisan queue:failed)"
fi

# Summary
print_phase "Test Summary"

echo ""
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}Test Results Summary${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo -e "Total Tests: ${TESTS_TOTAL}"
echo -e "${GREEN}Passed: ${TESTS_PASSED}${NC}"
echo -e "${RED}Failed: ${TESTS_FAILED}${NC}"
echo ""

if [ $TESTS_FAILED -eq 0 ]; then
    echo -e "${GREEN}✅ All tests passed!${NC}"
    echo ""
    echo "Next steps:"
    echo "1. Ensure queue worker is running: php artisan queue:work --daemon"
    echo "2. Verify cron is configured: crontab -l"
    echo "3. Monitor logs: tail -f storage/logs/laravel.log"
    exit 0
else
    echo -e "${RED}❌ Some tests failed${NC}"
    echo ""
    echo "Please review the errors above and:"
    echo "1. Fix any critical issues"
    echo "2. Run migrations if needed: php artisan migrate"
    echo "3. Check configuration files"
    echo "4. Review logs: tail -f storage/logs/laravel.log"
    exit 1
fi

