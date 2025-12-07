<?php

namespace App\Jobs;

use App\Services\TimeTrackingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Track Active Sessions Job
 * 
 * This background job periodically tracks all active internet sessions and deducts
 * time from devices based on how long they've been browsing. It ensures that devices
 * are accurately charged for their internet usage time.
 * 
 * What is a Background Job?
 * - A background job is code that runs automatically without user interaction
 * - Think of it like a "robot assistant" that works in the background
 * - It runs on a schedule (every 5 minutes) to track active sessions
 * - This ensures time is deducted accurately as devices browse the internet
 * 
 * Why Do We Need This Job?
 * - Devices browse the internet and use up their allocated time
 * - We need to track how long each device has been online
 * - Time must be deducted from device's remaining_time_minutes
 * - This job runs automatically every 5 minutes to update time usage
 * 
 * How It Works:
 * 1. Job runs automatically every 5 minutes (via Laravel scheduler)
 * 2. Calls TimeTrackingService::trackActiveSessions() to process all active sessions
 * 3. Service finds all active sessions (sessions that haven't ended)
 * 4. For each active session:
 *    - Calculates how long the session has been running
 *    - Deducts that time from device's remaining_time_minutes
 *    - Updates device's last_seen_at timestamp
 * 5. Logs results for monitoring and debugging
 * 
 * What Happens When Time is Deducted:
 * - Device's remaining_time_minutes decreases based on session duration
 * - Example: Device has 30 minutes, session running 5 minutes → 25 minutes remaining
 * - When remaining_time_minutes reaches 0, CheckTimeExpiration job blocks the device
 * - Device is then redirected to portal to earn more time
 * 
 * Integration with Other Services:
 * - TimeTrackingService: Handles all time tracking logic (trackActiveSessions())
 * - Device Model: Stores remaining_time_minutes and total_time_allocated
 * - DeviceSession Model: Tracks active sessions with started_at timestamps
 * - CheckTimeExpiration Job: Blocks devices when time reaches 0
 * 
 * Error Handling:
 * - If service method fails, error is logged but job doesn't crash
 * - Job will retry on next run if needed
 * - TimeTrackingService handles all error cases internally
 * 
 * Scheduling:
 * - Registered in routes/console.php (Laravel 11+) or app/Console/Kernel.php (Laravel 10)
 * - Runs every 5 minutes to balance accuracy and performance
 * - Uses Laravel's scheduler to run automatically
 * 
 * Why Every 5 Minutes?
 * - Too frequent (every 1 minute): Wastes server resources, may cause conflicts
 * - Too infrequent (every 10 minutes): Time tracking becomes less accurate
 * - 5 minutes is a good balance: Accurate time tracking without overloading server
 * 
 * Usage Example:
 * ```php
 * // Job runs automatically via scheduler
 * // No manual call needed - Laravel handles it
 * 
 * // To test manually:
 * use App\Jobs\TrackActiveSessions;
 * TrackActiveSessions::dispatch();
 * ```
 */
class TrackActiveSessions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job to track active sessions and deduct time.
     * 
     * This is the main method that runs when the job executes. It:
     * 1. Calls TimeTrackingService to track all active sessions
     * 2. Service handles all time deduction logic internally
     * 3. Logs results for monitoring and debugging
     * 
     * How It Works Step-by-Step:
     * - Step 1: Log that the job started running
     * - Step 2: Call TimeTrackingService::trackActiveSessions()
     * - Step 3: Service processes all active sessions and deducts time
     * - Step 4: Log completion for monitoring
     * 
     * Error Handling:
     * - If service method fails, exception is caught and logged
     * - Job doesn't crash - errors are logged for debugging
     * - Job will retry on next run if needed
     * 
     * @param TimeTrackingService $timeTrackingService The time tracking service (injected by Laravel)
     * @return void No return value
     * 
     * Usage:
     * This method is called automatically by Laravel when the job runs.
     * You don't need to call it manually - the scheduler handles it.
     */
    public function handle(TimeTrackingService $timeTrackingService): void
    {
        // Log that the job started running
        // This helps us track when the job executes and debug any issues
        // Logging at info level so we can monitor job execution in production
        Log::info('TrackActiveSessions job started - tracking active sessions and deducting time');

        // Wrap in try-catch to handle errors gracefully
        // If the service method fails, we catch the error and log it
        // This ensures the job doesn't crash and can retry on next run
        try {
            // Step 1: Call TimeTrackingService to track all active sessions
            // This service method does all the heavy lifting:
            // - Finds all active sessions (sessions that haven't ended)
            // - For each active session:
            //   - Calculates how long the session has been running
            //   - Deducts that time from device's remaining_time_minutes
            //   - Updates device's last_seen_at timestamp
            // - Skips whitelisted devices (they don't have time deducted)
            // - Only deducts if session duration >= 1 minute (prevents over-deduction)
            // 
            // Why Delegate to Service?
            // - Service contains all the business logic for time tracking
            // - Keeps job code simple and focused on scheduling
            // - Service can be tested independently
            // - Service can be called from other places if needed
            // 
            // What trackActiveSessions() Does:
            // 1. Gets all active sessions (DeviceSession where ended_at is NULL)
            // 2. For each session, calculates duration from started_at to now()
            // 3. Deducts time from device's remaining_time_minutes
            // 4. Updates device's last_seen_at timestamp
            // 5. Logs each deduction for debugging
            $timeTrackingService->trackActiveSessions();

            // Step 2: Log successful completion
            // This helps us verify the job is running correctly
            // We log at info level so we can monitor job execution
            Log::info('TrackActiveSessions job completed successfully - active sessions tracked and time deducted');

        } catch (\Exception $e) {
            // If an error occurs, catch it here
            // Log the error but don't crash the job
            // This ensures the job can retry on next run
            // 
            // Why Catch Exceptions?
            // - Service method might throw exceptions (database errors, etc.)
            // - We want to log the error for debugging
            // - We don't want the job to crash - it should retry on next run
            // - Laravel's queue system will handle retries automatically
            Log::error('TrackActiveSessions job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw the exception so Laravel's queue system can handle retries
            // Laravel will automatically retry the job based on queue configuration
            // This ensures the job will eventually succeed if the error is temporary
            throw $e;
        }
    }
}

