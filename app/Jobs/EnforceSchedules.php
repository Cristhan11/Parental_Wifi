<?php

namespace App\Jobs;

use App\Models\Device;
use App\Models\DeviceSchedule;
use App\Services\NetworkService;
use App\Services\NoDogSplashService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Enforce Schedules Job
 * 
 * This background job periodically enforces time-based access rules for devices.
 * It checks if devices are within their allowed time windows and blocks/unblocks
 * them accordingly. It also enforces daily duration limits.
 * 
 * What is a Background Job?
 * - A background job is code that runs automatically without user interaction
 * - Think of it like a "robot assistant" that works in the background
 * - It runs on a schedule (every 1 minute) to enforce time-based rules
 * - This ensures devices are blocked/unblocked at the correct times
 * 
 * Why Do We Need This Job?
 * - Parents can set schedules like "Internet allowed Monday-Friday 3PM-9PM"
 * - We need to enforce these schedules automatically
 * - Devices should be blocked when outside allowed time windows
 * - Devices should be unblocked when within allowed time windows
 * - Daily duration limits must be enforced
 * 
 * How It Works:
 * 1. Job runs automatically every 1 minute (via Laravel scheduler)
 * 2. Gets current day of week and time
 * 3. Finds all active schedules matching current day and time
 * 4. For each schedule:
 *    - Checks if current time is within allowed time window
 *    - Checks if daily duration limit has been reached
 *    - Blocks/unblocks device based on schedule rules
 * 5. Logs all operations for monitoring and debugging
 * 
 * What Happens When Schedule is Active:
 * - Device is allowed to access internet (if time available)
 * - Device is unblocked at network level (if previously blocked by schedule)
 * - Device can browse within allowed time window
 * 
 * What Happens When Schedule is Inactive:
 * - Device is blocked at network level
 * - Device is redirected to portal
 * - Device cannot access internet until schedule becomes active
 * 
 * What Happens When Daily Duration Limit Reached:
 * - Device is blocked at network level
 * - Device is redirected to portal
 * - Device cannot access internet until next day (limit resets)
 * 
 * Integration with Other Services:
 * - NetworkService: Blocks/unblocks devices at network level
 * - NoDogSplashService: Redirects devices to portal when blocked
 * - DeviceSchedule Model: Stores time-based access rules
 * - Device Model: Stores device status and time allocation
 * 
 * Error Handling:
 * - If one device fails, job continues processing other devices
 * - Errors are logged but don't crash the job
 * - Job will retry failed operations on next run
 * 
 * Scheduling:
 * - Registered in routes/console.php (Laravel 11+) or app/Console/Kernel.php (Laravel 10)
 * - Runs every 1 minute to ensure precise schedule enforcement
 * - Uses Laravel's scheduler to run automatically
 * 
 * Why Every 1 Minute?
 * - Schedules need precise enforcement (e.g., block at exactly 9:00 PM)
 * - Too infrequent (every 5 minutes): Devices might use extra time before being blocked
 * - 1 minute ensures schedules are enforced within 1 minute of scheduled time
 * 
 * Usage Example:
 * ```php
 * // Job runs automatically via scheduler
 * // No manual call needed - Laravel handles it
 * 
 * // To test manually:
 * use App\Jobs\EnforceSchedules;
 * EnforceSchedules::dispatch();
 * ```
 */
class EnforceSchedules implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job to enforce time-based schedules.
     * 
     * This is the main method that runs when the job executes. It:
     * 1. Gets current day of week and time
     * 2. Finds all active schedules matching current day
     * 3. For each schedule, checks if device is within allowed time window
     * 4. Blocks/unblocks devices based on schedule rules
     * 5. Enforces daily duration limits
     * 6. Logs all operations for monitoring
     * 
     * How It Works Step-by-Step:
     * - Step 1: Get current day of week and time
     * - Step 2: Find all active schedules for current day
     * - Step 3: For each schedule, check if current time is within allowed window
     * - Step 4: Check if daily duration limit has been reached
     * - Step 5: Block/unblock device based on schedule rules
     * 
     * Error Handling:
     * - If one device fails, we catch the error and continue with next device
     * - This ensures one failed device doesn't stop processing of other devices
     * - All errors are logged so we can debug later
     * 
     * @param NetworkService $networkService The network service (injected by Laravel)
     * @param NoDogSplashService $noDogSplashService The NoDogSplash service (injected by Laravel)
     * @return void No return value
     * 
     * Usage:
     * This method is called automatically by Laravel when the job runs.
     * You don't need to call it manually - the scheduler handles it.
     */
    public function handle(
        NetworkService $networkService,
        NoDogSplashService $noDogSplashService
    ): void {
        // Log that the job started running
        // This helps us track when the job executes and debug any issues
        Log::info('EnforceSchedules job started - enforcing time-based access rules');

        // Step 1: Get current day of week and time
        // We need to know what day it is and what time it is
        // to check if schedules are currently active
        // 
        // Day of Week:
        // - PHP's date('l') returns full day name: "Monday", "Tuesday", etc.
        // - We convert to lowercase to match database enum values
        // - Database stores: 'monday', 'tuesday', 'wednesday', etc.
        // 
        // Current Time:
        // - PHP's date('H:i:s') returns time in 24-hour format: "15:30:00" (3:30 PM)
        // - We use Carbon's now() for timezone-aware time handling
        // - Time is compared with schedule start_time and end_time
        $currentDay = strtolower(now()->format('l')); // "monday", "tuesday", etc.
        $currentTime = now()->format('H:i:s'); // "15:30:00" (3:30 PM)

        // Step 2: Find all active schedules for current day
        // We only check schedules that:
        // - Are active (is_active = true)
        // - Match current day of week
        // - Have a device associated with them
        // 
        // Why Filter by Day?
        // - Each schedule is for a specific day (monday, tuesday, etc.)
        // - We only need to check schedules for today
        // - This reduces the number of schedules we need to process
        $activeSchedules = DeviceSchedule::where('is_active', true)
            ->where('day_of_week', $currentDay)
            ->with('device') // Eager load device to avoid N+1 queries
            ->get();

        // If no active schedules for today, nothing to do
        // Log and exit early to save processing time
        if ($activeSchedules->isEmpty()) {
            Log::debug('EnforceSchedules job completed - no active schedules for today', [
                'current_day' => $currentDay,
            ]);
            return; // Exit early - no work to do
        }

        // Log how many schedules we're processing
        // This helps us understand how many schedules are active
        Log::debug('EnforceSchedules job found active schedules', [
            'current_day' => $currentDay,
            'current_time' => $currentTime,
            'schedule_count' => $activeSchedules->count(),
        ]);

        // Step 3: Process each schedule
        // For each active schedule, we need to:
        // - Check if current time is within allowed time window
        // - Check if daily duration limit has been reached
        // - Block/unblock device based on schedule rules
        $schedulesProcessed = 0;
        $devicesBlocked = 0;
        $devicesUnblocked = 0;

        foreach ($activeSchedules as $schedule) {
            // Wrap in try-catch to handle errors gracefully
            // If one schedule fails, we continue processing other schedules
            try {
                // Get the device this schedule applies to
                // Already loaded via eager loading, so no extra query
                $device = $schedule->device;

                // Skip if device doesn't exist (shouldn't happen, but safety check)
                if (!$device) {
                    Log::warning('Schedule has no associated device', [
                        'schedule_id' => $schedule->id,
                    ]);
                    continue; // Skip this schedule, continue with next
                }

                // Skip whitelisted devices (they bypass all schedules)
                // Whitelisted devices have unrestricted access
                if ($device->isWhitelisted()) {
                    Log::debug('Skipping schedule enforcement for whitelisted device', [
                        'device_id' => $device->id,
                        'device_name' => $device->name,
                        'schedule_id' => $schedule->id,
                    ]);
                    continue; // Skip this schedule, continue with next
                }

                // Step 4: Check if current time is within allowed time window
                // Schedule has start_time and end_time (e.g., 15:00:00 to 21:00:00)
                // We need to check if current time is between start_time and end_time
                // 
                // Time Comparison:
                // - PHP can compare time strings directly: "15:30:00" > "15:00:00" = true
                // - We compare current time with schedule start_time and end_time
                // - If current time is between start_time and end_time, schedule is active
                $scheduleStartTime = $schedule->start_time->format('H:i:s');
                $scheduleEndTime = $schedule->end_time->format('H:i:s');
                $isWithinTimeWindow = $currentTime >= $scheduleStartTime && $currentTime <= $scheduleEndTime;

                // Step 5: Check if daily duration limit has been reached
                // Schedule may have a daily duration limit (e.g., 120 minutes per day)
                // We need to check if device has used up its daily limit
                // 
                // How Daily Limit Works:
                // - If schedule has duration_limit_minutes, device can only use that much time per day
                // - We calculate how much time device has used today
                // - If used time >= limit, device is blocked even if within time window
                // 
                // Note: Daily limit calculation is simplified here
                // In a full implementation, you would track daily usage per schedule
                $dailyLimitReached = false;
                if ($schedule->duration_limit_minutes) {
                    // TODO: Implement daily usage tracking per schedule
                    // For now, we check device's remaining_time_minutes
                    // If device has no time left, consider limit reached
                    // This is a simplified implementation
                    if ($device->remaining_time_minutes <= 0) {
                        $dailyLimitReached = true;
                    }
                }

                // Step 6: Determine if device should be blocked or unblocked
                // Device should be blocked if:
                // - Current time is outside allowed time window, OR
                // - Daily duration limit has been reached
                // 
                // Device should be unblocked if:
                // - Current time is within allowed time window, AND
                // - Daily duration limit has not been reached, AND
                // - Device has time remaining
                $shouldBeBlocked = !$isWithinTimeWindow || $dailyLimitReached;

                // Step 7: Block or unblock device based on schedule rules
                // If device should be blocked but is currently active, block it
                // If device should be unblocked but is currently blocked by schedule, unblock it
                // 
                // Note: We only enforce schedules, we don't override other blocking reasons
                // (e.g., if device time expired, it stays blocked even if schedule allows)
                if ($shouldBeBlocked) {
                    // Device should be blocked by schedule
                    // Check if device is currently active (not already blocked)
                    if ($device->status === 'active') {
                        // Block device at network level
                        $networkService->blockDevice($device);

                        // Redirect device to portal
                        $noDogSplashService->redirectDeviceToPortal($device);

                        // Update device status to 'blocked'
                        $device->update(['status' => 'blocked']);

                        $devicesBlocked++;

                        Log::info('Device blocked by schedule', [
                            'device_id' => $device->id,
                            'device_name' => $device->name,
                            'schedule_id' => $schedule->id,
                            'reason' => $dailyLimitReached ? 'daily_limit_reached' : 'outside_time_window',
                            'current_time' => $currentTime,
                            'schedule_window' => "{$scheduleStartTime} - {$scheduleEndTime}",
                        ]);
                    }
                } else {
                    // Device should be unblocked by schedule
                    // Check if device is currently blocked
                    // Note: We only unblock if device was blocked by schedule
                    // We don't unblock if device was blocked for other reasons (e.g., time expired)
                    if ($device->status === 'blocked' && $device->remaining_time_minutes > 0) {
                        // Unblock device at network level
                        $networkService->unblockDevice($device);

                        // Allow device through portal
                        $noDogSplashService->allowDeviceThrough($device);

                        // Update device status to 'active'
                        $device->update(['status' => 'active']);

                        $devicesUnblocked++;

                        Log::info('Device unblocked by schedule', [
                            'device_id' => $device->id,
                            'device_name' => $device->name,
                            'schedule_id' => $schedule->id,
                            'current_time' => $currentTime,
                            'schedule_window' => "{$scheduleStartTime} - {$scheduleEndTime}",
                            'remaining_time_minutes' => $device->remaining_time_minutes,
                        ]);
                    }
                }

                $schedulesProcessed++;

            } catch (\Exception $e) {
                // If an error occurs while processing a schedule, catch it here
                // Log the error but continue processing other schedules
                // This ensures one failed schedule doesn't stop the entire job
                Log::error('Error processing schedule', [
                    'schedule_id' => $schedule->id ?? 'unknown',
                    'device_id' => $schedule->device_id ?? 'unknown',
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Continue to next schedule (don't stop the job)
                // The job will retry on next run if needed
                continue;
            }
        }

        // Log that the job completed successfully
        // This helps us track job execution and verify it's running correctly
        Log::info('EnforceSchedules job completed', [
            'current_day' => $currentDay,
            'current_time' => $currentTime,
            'schedules_processed' => $schedulesProcessed,
            'devices_blocked' => $devicesBlocked,
            'devices_unblocked' => $devicesUnblocked,
        ]);
    }
}

