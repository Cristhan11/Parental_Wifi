# Enforce Schedules Job - Complete Guide

## What Is This Job?

The `EnforceSchedules` job is a background job that periodically enforces time-based access rules for devices. It checks if devices are within their allowed time windows and blocks/unblocks them accordingly. It also enforces daily duration limits.

**File Location**: `app/Jobs/EnforceSchedules.php`

## Why Do We Need This Job?

Parents can set schedules like "Internet allowed Monday-Friday 3PM-9PM". We need to:

1. **Enforce Time Windows**: Block devices outside allowed time windows
2. **Enforce Daily Limits**: Block devices that have reached daily duration limits
3. **Automate Blocking**: Automatically block/unblock devices based on schedules
4. **Precise Timing**: Ensure schedules are enforced at the correct times

Without this job, schedules would be set but never enforced, and devices could access internet at any time.

## How Does It Work?

### Step-by-Step Workflow

1. **Job Runs**: Every 1 minute, Laravel's scheduler runs this job
2. **Get Current Time**: Gets current day of week and time
3. **Find Active Schedules**: Finds all active schedules matching current day
4. **Check Time Window**: For each schedule, checks if current time is within allowed window
5. **Check Daily Limit**: Checks if daily duration limit has been reached
6. **Block/Unblock**: Blocks or unblocks device based on schedule rules
7. **Log Results**: Logs all operations for monitoring

### Example Scenario

**Schedule**: "Internet allowed Monday-Friday 3PM-9PM"

1. **2:59 PM Monday**: Device is blocked (outside time window)
2. **3:00 PM Monday**: Job runs, detects time window active, unblocks device
3. **8:59 PM Monday**: Device is active (within time window)
4. **9:00 PM Monday**: Job runs, detects time window ended, blocks device
5. **9:01 PM Monday**: Device is blocked (outside time window)

## Code Structure

### Class Definition

```php
class EnforceSchedules implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
}
```

### Main Method: `handle()`

The `handle()` method is the main entry point:

```php
public function handle(
    NetworkService $networkService,
    NoDogSplashService $noDogSplashService
): void {
    // 1. Get current day and time
    $currentDay = strtolower(now()->format('l'));
    $currentTime = now()->format('H:i:s');
    
    // 2. Find active schedules
    $activeSchedules = DeviceSchedule::where('is_active', true)
        ->where('day_of_week', $currentDay)
        ->get();
    
    // 3. Process each schedule
    foreach ($activeSchedules as $schedule) {
        // Check time window
        // Check daily limit
        // Block/unblock device
    }
}
```

**Parameters**:
- `NetworkService $networkService`: Blocks/unblocks devices at network level
- `NoDogSplashService $noDogSplashService`: Redirects devices to portal

## Key Concepts

### Time Windows

A time window defines when internet access is allowed. It has:
- **Start Time**: When access begins (e.g., 15:00 = 3:00 PM)
- **End Time**: When access ends (e.g., 21:00 = 9:00 PM)

**Example**:
```php
$schedule->start_time = '15:00:00'; // 3:00 PM
$schedule->end_time = '21:00:00';   // 9:00 PM

// Check if current time is within window
$isWithinWindow = $currentTime >= $scheduleStartTime 
               && $currentTime <= $scheduleEndTime;
```

### Daily Duration Limits

A daily duration limit restricts how much time a device can use per day (e.g., 120 minutes = 2 hours).

**Example**:
```php
$schedule->duration_limit_minutes = 120; // 2 hours per day

// Check if limit reached
if ($device->remaining_time_minutes <= 0) {
    $dailyLimitReached = true;
}
```

**Note**: Full implementation would track daily usage per schedule, not just device time.

### Day of Week

Schedules are defined per day of week:
- `monday`, `tuesday`, `wednesday`, `thursday`, `friday`, `saturday`, `sunday`

**Example**:
```php
$schedule->day_of_week = 'monday'; // Only applies on Mondays

// Get current day
$currentDay = strtolower(now()->format('l')); // "monday"
```

### Schedule States

A schedule can be:
- **Active**: `is_active = true` (schedule is enforced)
- **Inactive**: `is_active = false` (schedule is ignored)

## Integration Points

### NetworkService

The job uses `NetworkService` to:
- Block devices: `blockDevice($device)`
- Unblock devices: `unblockDevice($device)`

### NoDogSplashService

The job uses `NoDogSplashService` to:
- Redirect to portal: `redirectDeviceToPortal($device)`
- Allow through: `allowDeviceThrough($device)`

### DeviceSchedule Model

The job uses `DeviceSchedule` to:
- Get schedule rules
- Check time windows
- Check daily limits

### Device Model

The job interacts with `Device` to:
- Check device status
- Update device status
- Check remaining time

## Scheduling

The job is scheduled in `routes/console.php`:

```php
Schedule::job(new EnforceSchedules)
    ->everyMinute()
    ->name('enforce-schedules')
    ->withoutOverlapping();
```

**Schedule Details**:
- **Frequency**: Every 1 minute
- **Name**: `enforce-schedules`
- **Without Overlapping**: Prevents multiple instances

**Why Every 1 Minute?**
- Schedules need precise enforcement (e.g., block at exactly 9:00 PM)
- Too infrequent (every 5 minutes): Devices might use extra time
- 1 minute ensures schedules enforced within 1 minute of scheduled time

## Error Handling

The job uses try-catch blocks to handle errors:

```php
foreach ($activeSchedules as $schedule) {
    try {
        // Process schedule
    } catch (\Exception $e) {
        Log::error('Error processing schedule', [...]);
        continue; // Continue with next schedule
    }
}
```

**Error Handling Strategy**:
1. Log error for debugging
2. Continue processing other schedules
3. Don't crash the job

## Logging

The job logs important events:

### Info Level
- Job start: `EnforceSchedules job started`
- Device blocked: `Device blocked by schedule`
- Device unblocked: `Device unblocked by schedule`
- Job completion: `EnforceSchedules job completed`

### Debug Level
- Schedules found: `EnforceSchedules job found active schedules`
- Skipped device: `Skipping schedule enforcement for whitelisted device`

### Error Level
- Job failure: `EnforceSchedules job failed`
- Schedule error: `Error processing schedule`

## Testing

### Manual Testing

```php
use App\Jobs\EnforceSchedules;

// Dispatch job immediately
EnforceSchedules::dispatch();
```

### Testing Scenarios

1. **Within Time Window**: Set schedule, set time within window, verify device unblocked
2. **Outside Time Window**: Set schedule, set time outside window, verify device blocked
3. **Daily Limit Reached**: Set limit, use up time, verify device blocked
4. **Whitelisted Device**: Set schedule, verify whitelisted device skipped
5. **Inactive Schedule**: Set inactive schedule, verify not enforced

## Troubleshooting

### Schedules Not Enforcing

**Problem**: Schedules are set but not being enforced.

**Solutions**:
1. Check if schedules are active: `DeviceSchedule::where('is_active', true)->count()`
2. Check day of week matches: `$schedule->day_of_week === $currentDay`
3. Check time window: Verify current time is within window
4. Check logs for errors

### Devices Not Blocking/Unblocking

**Problem**: Job runs but devices don't change status.

**Solutions**:
1. Check device status: `$device->status`
2. Check NetworkService: Verify `blockDevice()` / `unblockDevice()` work
3. Check NoDogSplashService: Verify redirect/allow methods work
4. Check logs for errors

### Time Window Issues

**Problem**: Devices blocked/unblocked at wrong times.

**Solutions**:
1. Check timezone: Verify server timezone is correct
2. Check time format: Verify `H:i:s` format matches schedule
3. Check time comparison: Verify comparison logic
4. Check logs for time values

## Code Examples

### Basic Usage

```php
// Job runs automatically via scheduler
// No manual call needed
```

### Manual Dispatch

```php
use App\Jobs\EnforceSchedules;

// Dispatch immediately
EnforceSchedules::dispatch();
```

### Creating a Schedule

```php
use App\Models\DeviceSchedule;

$schedule = DeviceSchedule::create([
    'device_id' => 1,
    'day_of_week' => 'monday',
    'start_time' => '15:00:00', // 3:00 PM
    'end_time' => '21:00:00',   // 9:00 PM
    'duration_limit_minutes' => 120, // 2 hours
    'is_active' => true,
]);
```

### Checking Schedule Status

```php
$schedule = DeviceSchedule::find(1);

// Check if schedule is active today
$currentDay = strtolower(now()->format('l'));
$isActiveToday = $schedule->is_active 
              && $schedule->day_of_week === $currentDay;

// Check if within time window
$currentTime = now()->format('H:i:s');
$isWithinWindow = $currentTime >= $schedule->start_time->format('H:i:s')
               && $currentTime <= $schedule->end_time->format('H:i:s');
```

## Summary

The `EnforceSchedules` job is essential for time-based access control. It:

- Runs every 1 minute
- Enforces time windows
- Enforces daily duration limits
- Blocks/unblocks devices automatically
- Logs all operations

Without this job, schedules would be set but never enforced, and devices could access internet at any time.

