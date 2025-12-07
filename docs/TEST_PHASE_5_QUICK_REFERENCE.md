# Test Phase 5 - Quick Reference Guide

This is a quick reference guide for testing background jobs and queue system on Raspberry Pi. For detailed procedures, see [TEST_PHASE_5_RESULTS.md](./TEST_PHASE_5_RESULTS.md).

## Quick Start

### 1. Run Automated Test Script
```bash
cd /var/www/parental_wifi
./scripts/test-phase5.sh
```

This script automatically verifies:
- Queue configuration
- Job class existence
- Job scheduling
- Queue worker functionality
- Cron configuration

### 2. Manual Quick Tests

#### Test Queue Configuration
```bash
# Check queue connection
grep QUEUE_CONNECTION .env

# Verify queue tables exist
php artisan tinker
>>> Schema::hasTable('jobs');  // Should return true
>>> Schema::hasTable('failed_jobs');  // Should return true
```

#### Test Job Classes
```bash
# List all job files
ls -la app/Jobs/

# Test if classes can be loaded
php artisan tinker
>>> new App\Jobs\CheckTimeExpiration();
>>> new App\Jobs\TrackActiveSessions();
>>> new App\Jobs\MonitorDeviceConnections();
>>> new App\Jobs\EnforceSchedules();
>>> new App\Jobs\ParseNetworkLogs();
```

#### Test Job Scheduling
```bash
# List all scheduled jobs
php artisan schedule:list

# Test scheduler manually
php artisan schedule:test
```

#### Test Queue Worker
```bash
# Start queue worker (in one terminal)
php artisan queue:work --verbose

# Dispatch a test job (in another terminal)
php artisan tinker
>>> App\Jobs\CheckTimeExpiration::dispatch();

# Check queue status
php artisan queue:monitor
```

#### Test Cron Configuration
```bash
# Check crontab
crontab -l

# Manually run scheduler
php artisan schedule:run
```

## Background Jobs Overview

| Job Name | Frequency | Purpose |
|----------|-----------|---------|
| **CheckTimeExpiration** | Every 2 minutes | Blocks devices whose time has expired |
| **TrackActiveSessions** | Every 5 minutes | Deducts time from active browsing sessions |
| **MonitorDeviceConnections** | Every 2 minutes | Detects new/disconnected devices |
| **EnforceSchedules** | Every 1 minute | Enforces time-based access rules |
| **ParseNetworkLogs** | Every 10 minutes | Parses network logs for browsing history |

## Expected Behavior

### CheckTimeExpiration
- Finds devices with `remaining_time_minutes <= 0`
- Blocks devices at network level
- Redirects devices to portal
- **See:** [BACKGROUND_JOBS_OVERVIEW.md](./BACKGROUND_JOBS_OVERVIEW.md#1-checktimeexpiration)

### TrackActiveSessions
- Finds active sessions (`ended_at` is NULL)
- Calculates session duration
- Deducts time from device's `remaining_time_minutes`
- **See:** [BACKGROUND_JOBS_TRACK_ACTIVE_SESSIONS.md](./BACKGROUND_JOBS_TRACK_ACTIVE_SESSIONS.md)

### MonitorDeviceConnections
- Gets connected devices from network
- Updates device IP addresses
- Ends sessions for disconnected devices
- **See:** [BACKGROUND_JOBS_MONITOR_DEVICE_CONNECTIONS.md](./BACKGROUND_JOBS_MONITOR_DEVICE_CONNECTIONS.md)

### EnforceSchedules
- Checks current day and time
- Finds active schedules matching current day
- Blocks/unblocks devices based on schedule rules
- **See:** [BACKGROUND_JOBS_ENFORCE_SCHEDULES.md](./BACKGROUND_JOBS_ENFORCE_SCHEDULES.md)

### ParseNetworkLogs
- Reads network log files
- Parses log entries
- Creates BrowsingLog records
- **See:** [BACKGROUND_JOBS_PARSE_NETWORK_LOGS.md](./BACKGROUND_JOBS_PARSE_NETWORK_LOGS.md)

## Common Issues & Solutions

### Issue: Queue Worker Not Processing Jobs
```bash
# Check if worker is running
ps aux | grep "queue:work"

# Start worker if not running
php artisan queue:work --daemon

# Check queue for jobs
php artisan tinker
>>> DB::table('jobs')->count();
```

### Issue: Jobs Not Scheduled
```bash
# Verify routes/console.php exists and has job schedules
cat routes/console.php | grep "Schedule::job"

# Verify schedule list
php artisan schedule:list
```

### Issue: Cron Not Running
```bash
# Check crontab
crontab -l

# Add crontab entry if missing
crontab -e
# Add: * * * * * cd /var/www/parental_wifi && php artisan schedule:run >> /dev/null 2>&1

# Check cron service
systemctl status cron
```

### Issue: Failed Jobs
```bash
# List failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Check failed jobs table
php artisan tinker
>>> DB::table('failed_jobs')->get();
```

### Issue: Jobs Taking Too Long
```bash
# Check job execution time in logs
tail -f storage/logs/laravel.log | grep "job"

# Check queue worker memory
ps aux | grep "queue:work" | awk '{print "Memory: " $6/1024 " MB"}'
```

## Success Criteria

✅ **All tests pass when:**
- Queue system is configured correctly (database driver)
- All 5 job classes exist and are loadable
- All 5 jobs are scheduled in routes/console.php
- Queue worker can start and process jobs
- Crontab is configured for Laravel scheduler
- Jobs execute without errors
- Failed jobs are logged correctly

## Next Steps

After Test Phase 5 passes:

1. ✅ Background jobs are ready for production
2. ✅ Set up queue worker as a systemd service (recommended)
3. ✅ Monitor logs regularly
4. ✅ Proceed with next development phase

## References

- **Detailed Test Document:** [TEST_PHASE_5_RESULTS.md](./TEST_PHASE_5_RESULTS.md)
- **Background Jobs Overview:** [BACKGROUND_JOBS_OVERVIEW.md](./BACKGROUND_JOBS_OVERVIEW.md)
- **Individual Job Docs:**
  - [TrackActiveSessions](./BACKGROUND_JOBS_TRACK_ACTIVE_SESSIONS.md)
  - [MonitorDeviceConnections](./BACKGROUND_JOBS_MONITOR_DEVICE_CONNECTIONS.md)
  - [EnforceSchedules](./BACKGROUND_JOBS_ENFORCE_SCHEDULES.md)
  - [ParseNetworkLogs](./BACKGROUND_JOBS_PARSE_NETWORK_LOGS.md)
- **Project Scope:** [scope.md](./scope.md) - Todo #16 and #17

---

**Last Updated:** 2024-01-15  
**Version:** 1.0

