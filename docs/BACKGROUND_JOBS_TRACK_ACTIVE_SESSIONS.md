# Track Active Sessions Job - Complete Guide

## What Is This Job?

The `TrackActiveSessions` job is a background job that periodically tracks all active internet sessions and deducts time from devices based on how long they've been browsing. It ensures that devices are accurately charged for their internet usage time.

**File Location**: `app/Jobs/TrackActiveSessions.php`

## Why Do We Need This Job?

When a device browses the internet, it uses up its allocated time. We need to:

1. **Track Active Sessions**: Know which devices are currently online
2. **Calculate Usage**: Determine how long each device has been browsing
3. **Deduct Time**: Subtract used time from device's `remaining_time_minutes`
4. **Update Timestamps**: Update when device was last seen online

Without this job, devices would never have their time deducted, and they could browse indefinitely.

## How Does It Work?

### Step-by-Step Workflow

1. **Job Runs**: Every 5 minutes, Laravel's scheduler runs this job
2. **Call Service**: Job calls `TimeTrackingService::trackActiveSessions()`
3. **Find Active Sessions**: Service finds all sessions where `ended_at` is NULL
4. **Calculate Duration**: For each session, calculates how long it's been running
5. **Deduct Time**: Subtracts time from device's `remaining_time_minutes`
6. **Update Timestamp**: Updates device's `last_seen_at` timestamp
7. **Log Results**: Logs all operations for monitoring

### Example Scenario

Let's say a device has 30 minutes of internet time:

1. **10:00 AM**: Device starts browsing (session created)
2. **10:05 AM**: Job runs, calculates 5 minutes used, deducts 5 minutes → 25 minutes remaining
3. **10:10 AM**: Job runs, calculates 10 minutes used, deducts 5 more minutes → 20 minutes remaining
4. **10:30 AM**: Job runs, calculates 30 minutes used, deducts remaining time → 0 minutes remaining
5. **10:32 AM**: `CheckTimeExpiration` job blocks device (time expired)

## Code Structure

### Class Definition

```php
class TrackActiveSessions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
}
```

**Key Components**:
- `ShouldQueue`: Makes this a queued job (runs in background)
- `Dispatchable`: Allows job to be dispatched
- `InteractsWithQueue`: Allows job to interact with queue
- `Queueable`: Provides queue configuration
- `SerializesModels`: Handles model serialization

### Main Method: `handle()`

The `handle()` method is the main entry point for the job:

```php
public function handle(TimeTrackingService $timeTrackingService): void
{
    // 1. Log job start
    Log::info('TrackActiveSessions job started');
    
    // 2. Call service to track sessions
    $timeTrackingService->trackActiveSessions();
    
    // 3. Log completion
    Log::info('TrackActiveSessions job completed');
}
```

**Parameters**:
- `TimeTrackingService $timeTrackingService`: Injected by Laravel's dependency injection

**Return Value**: `void` (no return value)

## Key Concepts

### Active Sessions

An active session is a `DeviceSession` record where `ended_at` is NULL. This means the session is still ongoing.

**Example**:
```php
// Active session
$session = DeviceSession::whereNull('ended_at')->first();
// $session->ended_at = NULL (session is active)

// Ended session
$session = DeviceSession::whereNotNull('ended_at')->first();
// $session->ended_at = '2024-01-15 10:30:00' (session has ended)
```

### Time Deduction

Time is deducted from `remaining_time_minutes` based on session duration:

```php
// Calculate session duration
$durationMinutes = $session->getDurationMinutes(); // e.g., 5.2 minutes

// Deduct time (rounded up)
$minutesToDeduct = (int) ceil($durationMinutes); // 6 minutes

// Deduct from device
$device->deductTime($minutesToDeduct);
```

**Important**: Time is only deducted if session duration >= 1 minute (prevents over-deduction).

### Whitelisted Devices

Whitelisted devices skip time tracking entirely. They have unrestricted access and never have time deducted.

```php
if ($device->isWhitelisted()) {
    continue; // Skip this device
}
```

## Integration Points

### TimeTrackingService

The job delegates all logic to `TimeTrackingService::trackActiveSessions()`. This service:

1. Finds all active sessions
2. Calculates session duration
3. Deducts time from devices
4. Updates timestamps
5. Logs operations

**Why Delegate?**
- Keeps job code simple
- Service can be tested independently
- Service can be called from other places

### Device Model

The job interacts with the `Device` model to:

- Get device information
- Update `remaining_time_minutes`
- Update `last_seen_at` timestamp

### DeviceSession Model

The job uses `DeviceSession` to:

- Find active sessions
- Calculate session duration
- Track session information

## Scheduling

The job is scheduled in `routes/console.php`:

```php
Schedule::job(new TrackActiveSessions)
    ->everyFiveMinutes()
    ->name('track-active-sessions')
    ->withoutOverlapping();
```

**Schedule Details**:
- **Frequency**: Every 5 minutes
- **Name**: `track-active-sessions` (for logging)
- **Without Overlapping**: Prevents multiple instances running simultaneously

**Why Every 5 Minutes?**
- Too frequent (every 1 minute): Wastes server resources
- Too infrequent (every 10 minutes): Time tracking becomes less accurate
- 5 minutes is a good balance

## Error Handling

The job uses try-catch blocks to handle errors gracefully:

```php
try {
    $timeTrackingService->trackActiveSessions();
} catch (\Exception $e) {
    Log::error('TrackActiveSessions job failed', [
        'error' => $e->getMessage(),
    ]);
    throw $e; // Re-throw for queue retry
}
```

**Error Handling Strategy**:
1. Log error for debugging
2. Re-throw exception for queue retry
3. Don't crash the system

## Logging

The job logs important events:

### Info Level
- Job start: `TrackActiveSessions job started`
- Job completion: `TrackActiveSessions job completed successfully`

### Error Level
- Job failure: `TrackActiveSessions job failed` (with error details)

### Debug Level
- Time deduction: `Time deducted from active session` (logged by service)

**Log Location**: `storage/logs/laravel.log`

## Testing

### Manual Testing

You can test the job manually:

```php
use App\Jobs\TrackActiveSessions;

// Dispatch job immediately
TrackActiveSessions::dispatch();
```

### Testing with Artisan

```bash
# Run queue worker
php artisan queue:work

# Test scheduler
php artisan schedule:test
```

### Testing Scenarios

1. **Active Session**: Create active session, run job, verify time deducted
2. **No Active Sessions**: Run job, verify no errors
3. **Whitelisted Device**: Create active session for whitelisted device, verify time not deducted
4. **Short Session**: Create session < 1 minute, verify time not deducted

## Troubleshooting

### Job Not Running

**Problem**: Job is scheduled but not running.

**Solutions**:
1. Check scheduler: `php artisan schedule:list`
2. Check queue worker: `php artisan queue:work`
3. Check logs: `tail -f storage/logs/laravel.log`
4. Verify cron: `* * * * * cd /path && php artisan schedule:run`

### Time Not Being Deducted

**Problem**: Job runs but time is not deducted.

**Solutions**:
1. Check if sessions are active: `DeviceSession::whereNull('ended_at')->count()`
2. Check session duration: `$session->getDurationMinutes()`
3. Check if device is whitelisted: `$device->isWhitelisted()`
4. Check logs for errors

### Job Running Too Slowly

**Problem**: Job takes too long to execute.

**Solutions**:
1. Check number of active sessions (too many sessions = slow)
2. Optimize database queries (add indexes)
3. Reduce job frequency (if appropriate)

## Code Examples

### Basic Usage

```php
// Job runs automatically via scheduler
// No manual call needed
```

### Manual Dispatch

```php
use App\Jobs\TrackActiveSessions;

// Dispatch immediately
TrackActiveSessions::dispatch();

// Dispatch with delay (5 minutes)
TrackActiveSessions::dispatch()->delay(now()->addMinutes(5));
```

### Checking Job Status

```php
// Check if job is in queue
$job = TrackActiveSessions::dispatch();
$jobId = $job->getJobId();

// Check queue status
php artisan queue:monitor
```

## Summary

The `TrackActiveSessions` job is essential for accurate time tracking. It:

- Runs every 5 minutes
- Tracks active sessions
- Deducts time from devices
- Updates timestamps
- Logs operations

Without this job, devices would never have their time deducted, breaking the entire time-based control system.

