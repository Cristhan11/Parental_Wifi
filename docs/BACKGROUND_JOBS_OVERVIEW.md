# Background Jobs Overview

## What Are Background Jobs?

Background jobs are pieces of code that run automatically in the background without requiring user interaction. Think of them as "robot assistants" that work continuously to keep the system running smoothly.

In Laravel, background jobs are classes that implement the `ShouldQueue` interface. They are scheduled to run at specific intervals (every minute, every 5 minutes, etc.) and handle important tasks like:

- Tracking device internet usage
- Monitoring network connections
- Enforcing time-based rules
- Parsing network logs

## Why Do We Need Background Jobs?

The parental WiFi control system needs to continuously monitor and manage devices. Without background jobs, we would need to:

- Manually check device status
- Manually track time usage
- Manually enforce schedules
- Manually parse network logs

Background jobs automate all of these tasks, ensuring the system works smoothly 24/7 without human intervention.

## How Background Jobs Work

### 1. Scheduling

Background jobs are scheduled in `routes/console.php` (Laravel 11+) or `app/Console/Kernel.php` (Laravel 10). The scheduler runs every minute and checks which jobs need to run.

Example:
```php
Schedule::job(new TrackActiveSessions)
    ->everyFiveMinutes()
    ->name('track-active-sessions')
    ->withoutOverlapping();
```

### 2. Execution

When a job is scheduled to run:

1. Laravel's scheduler dispatches the job to the queue
2. A queue worker picks up the job
3. The job's `handle()` method is executed
4. Results are logged for monitoring

### 3. Error Handling

If a job fails:

1. The error is logged
2. Laravel can automatically retry the job
3. The system continues to function (other jobs still run)

## Our Background Jobs

The system has 5 background jobs that work together:

### 1. CheckTimeExpiration

**Purpose**: Checks for devices whose internet time has expired and blocks them.

**Frequency**: Every 2 minutes

**What It Does**:
- Finds devices with `remaining_time_minutes <= 0`
- Blocks devices at network level
- Redirects devices to portal

**See**: [BACKGROUND_JOBS_CHECK_TIME_EXPIRATION.md](./BACKGROUND_JOBS_CHECK_TIME_EXPIRATION.md) (if exists)

### 2. TrackActiveSessions

**Purpose**: Tracks active internet sessions and deducts time from devices.

**Frequency**: Every 5 minutes

**What It Does**:
- Finds all active sessions (sessions that haven't ended)
- Calculates how long each session has been running
- Deducts time from device's `remaining_time_minutes`
- Updates device's `last_seen_at` timestamp

**See**: [BACKGROUND_JOBS_TRACK_ACTIVE_SESSIONS.md](./BACKGROUND_JOBS_TRACK_ACTIVE_SESSIONS.md)

### 3. MonitorDeviceConnections

**Purpose**: Monitors network to detect new devices and disconnected devices.

**Frequency**: Every 2 minutes

**What It Does**:
- Gets list of currently connected devices from network
- Updates device IP addresses
- Ends sessions for disconnected devices
- Logs new device connections for parent review

**See**: [BACKGROUND_JOBS_MONITOR_DEVICE_CONNECTIONS.md](./BACKGROUND_JOBS_MONITOR_DEVICE_CONNECTIONS.md)

### 4. EnforceSchedules

**Purpose**: Enforces time-based access rules (e.g., "Internet allowed 3PM-9PM").

**Frequency**: Every 1 minute

**What It Does**:
- Gets current day of week and time
- Finds active schedules matching current day
- Checks if devices are within allowed time windows
- Blocks/unblocks devices based on schedule rules
- Enforces daily duration limits

**See**: [BACKGROUND_JOBS_ENFORCE_SCHEDULES.md](./BACKGROUND_JOBS_ENFORCE_SCHEDULES.md)

### 5. ParseNetworkLogs

**Purpose**: Parses network traffic logs to extract browsing history.

**Frequency**: Every 10 minutes

**What It Does**:
- Reads network log files (tcpdump/iptables)
- Parses log entries to extract HTTP requests
- Matches requests to devices by MAC address
- Creates BrowsingLog records in database

**See**: [BACKGROUND_JOBS_PARSE_NETWORK_LOGS.md](./BACKGROUND_JOBS_PARSE_NETWORK_LOGS.md)

## How Jobs Work Together

The background jobs work together to create a complete monitoring and control system:

```
┌─────────────────────────────────────────────────────────────┐
│                    Background Jobs System                    │
└─────────────────────────────────────────────────────────────┘

1. MonitorDeviceConnections (every 2 min)
   └─> Detects new/disconnected devices
       └─> Updates IP addresses
       └─> Ends sessions for disconnected devices

2. TrackActiveSessions (every 5 min)
   └─> Tracks active sessions
       └─> Deducts time from devices
       └─> Updates remaining_time_minutes

3. CheckTimeExpiration (every 2 min)
   └─> Checks if time expired
       └─> Blocks expired devices
       └─> Redirects to portal

4. EnforceSchedules (every 1 min)
   └─> Checks schedule rules
       └─> Blocks/unblocks based on schedule
       └─> Enforces daily limits

5. ParseNetworkLogs (every 10 min)
   └─> Parses network logs
       └─> Creates browsing history
       └─> Stores in database
```

## Key Concepts

### Queue System

Laravel uses a queue system to manage background jobs. Jobs are added to a queue and processed by queue workers.

**Queue Drivers**:
- **Database**: Jobs stored in database (recommended for Raspberry Pi)
- **Redis**: Jobs stored in Redis (faster, requires Redis)
- **Sync**: Jobs run immediately (for testing only)

### Job Scheduling

Jobs are scheduled using Laravel's scheduler. The scheduler runs every minute and checks which jobs need to run.

**Scheduling Methods**:
- `everyMinute()` - Run every minute
- `everyFiveMinutes()` - Run every 5 minutes
- `everyTenMinutes()` - Run every 10 minutes
- `hourly()` - Run every hour
- `daily()` - Run once per day

### Job Execution

When a job runs:

1. Laravel dispatches the job to the queue
2. Queue worker picks up the job
3. Job's `handle()` method is executed
4. Results are logged

### Error Handling

If a job fails:

1. Error is logged to `storage/logs/laravel.log`
2. Laravel can automatically retry the job
3. Other jobs continue to run normally

## Testing Background Jobs

### Manual Testing

You can test jobs manually using Artisan commands:

```bash
# Run a specific job
php artisan queue:work

# Test scheduler
php artisan schedule:test

# List scheduled jobs
php artisan schedule:list
```

### Testing Individual Jobs

You can dispatch jobs manually in code:

```php
use App\Jobs\TrackActiveSessions;

// Dispatch job immediately
TrackActiveSessions::dispatch();
```

## Monitoring Background Jobs

### Logs

All jobs log their execution to `storage/logs/laravel.log`. Check logs to:

- See when jobs run
- Debug errors
- Monitor job performance

### Queue Status

Check queue status:

```bash
# See queue status
php artisan queue:monitor

# See failed jobs
php artisan queue:failed
```

## Troubleshooting

### Jobs Not Running

**Problem**: Jobs are scheduled but not running.

**Solutions**:
1. Check if scheduler is running: `php artisan schedule:list`
2. Check if queue worker is running: `php artisan queue:work`
3. Check logs for errors: `tail -f storage/logs/laravel.log`
4. Verify cron is set up: `* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1`

### Jobs Running Too Slowly

**Problem**: Jobs take too long to execute.

**Solutions**:
1. Check database indexes (ensure queries are fast)
2. Reduce job frequency (if appropriate)
3. Optimize job code (reduce database queries)
4. Use Redis queue driver (faster than database)

### Jobs Failing

**Problem**: Jobs are failing with errors.

**Solutions**:
1. Check logs: `storage/logs/laravel.log`
2. Verify dependencies (services, models exist)
3. Check database connectivity
4. Verify file permissions (for log parsing jobs)

## Best Practices

1. **Log Everything**: Always log job execution for debugging
2. **Handle Errors**: Wrap code in try-catch blocks
3. **Use Queues**: Always use queue system (don't run jobs synchronously)
4. **Monitor Performance**: Track job execution time
5. **Test Thoroughly**: Test jobs in development before deploying

## Summary

Background jobs are essential for the parental WiFi control system. They automate critical tasks like:

- Time tracking
- Device monitoring
- Schedule enforcement
- Log parsing

All jobs work together to create a complete, automated system that runs 24/7 without human intervention.

