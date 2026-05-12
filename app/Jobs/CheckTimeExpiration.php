<?php

namespace App\Jobs;

use App\Events\TimeExpired;
use App\Models\Device;
use App\Services\NetworkService;
use App\Services\NoDogSplashService;
use App\Services\TimeTrackingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Check Time Expiration Job
 * 
 * This background job periodically checks for devices whose internet time has expired
 * and automatically blocks them at the network level and redirects them to the portal.
 * 
 * What is a Background Job?
 * - A background job is code that runs automatically without user interaction
 * - Think of it like a "robot assistant" that works in the background
 * - It runs on a schedule (every 1-2 minutes) to check for expired devices
 * - This ensures devices are blocked immediately when time runs out
 * 
 * Why Do We Need This Job?
 * - Devices use internet time as they browse (time is deducted)
 * - When time reaches 0, device should be blocked and redirected to portal
 * - We can't wait for the device to make a request - we need to proactively check
 * - This job runs automatically every 1-2 minutes to catch expired devices quickly
 * 
 * How It Works:
 * 1. Job runs automatically every 1-2 minutes (via Laravel scheduler)
 * 2. Uses TimeTrackingService to find all devices whose time has expired
 * 3. For each expired device:
 *    - Updates device status to 'blocked' in database
 *    - Calls NetworkService to block device at network level (iptables)
 *    - Calls NoDogSplashService to redirect device to portal
 * 4. Logs all operations for debugging and audit trail
 * 
 * What Happens When Device Expires:
 * - Device's remaining_time_minutes reaches 0 (or negative)
 * - This job detects the expiration
 * - Device is blocked at network level (can't access internet)
 * - Device is redirected to portal (all HTTP requests go to portal page)
 * - Child sees portal page with quiz/video options to earn more time
 * 
 * Integration with Other Services:
 * - TimeTrackingService: Finds expired devices (getExpiredDevices())
 * - NetworkService: Blocks device at network level (blockDevice())
 * - NoDogSplashService: Redirects device to portal (redirectDeviceToPortal())
 * 
 * Error Handling:
 * - If one device fails, job continues processing other devices
 * - Errors are logged but don't crash the job
 * - Job will retry failed operations on next run
 * 
 * Scheduling:
 * - Registered in routes/console.php (Laravel 11+) or app/Console/Kernel.php (Laravel 10)
 * - Runs every 1-2 minutes to catch expired devices quickly
 * - Uses Laravel's scheduler to run automatically
 * 
 * Usage Example:
 * ```php
 * // Job runs automatically via scheduler
 * // No manual call needed - Laravel handles it
 * 
 * // To test manually:
 * use App\Jobs\CheckTimeExpiration;
 * CheckTimeExpiration::dispatch();
 * ```
 */
class CheckTimeExpiration implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job to check for expired devices and block them.
     * 
     * This is the main method that runs when the job executes. It:
     * 1. Finds all devices whose time has expired
     * 2. Blocks each expired device at network level
     * 3. Redirects each expired device to portal
     * 4. Logs all operations for debugging
     * 
     * How It Works Step-by-Step:
     * - Step 1: Get expired devices using TimeTrackingService
     * - Step 2: Loop through each expired device
     * - Step 3: Update device status to 'blocked' in database
     * - Step 4: Block device at network level (via NetworkService)
     * - Step 5: Redirect device to portal (via NoDogSplashService)
     * - Step 6: Log the operation for debugging
     * 
     * Error Handling:
     * - If one device fails, we catch the error and continue with next device
     * - This ensures one failed device doesn't stop processing of other devices
     * - All errors are logged so we can debug later
     * 
     * @return void No return value
     * 
     * Usage:
     * This method is called automatically by Laravel when the job runs.
     * You don't need to call it manually - the scheduler handles it.
     */
    public function handle(
        TimeTrackingService $timeTrackingService,
        NetworkService $networkService,
        NoDogSplashService $noDogSplashService
    ): void {
        // Log that the job started running
        // This helps us track when the job executes and debug any issues
        Log::info('CheckTimeExpiration job started - checking for expired devices');

        // Sync incremental deductions first so remaining_time matches active sessions, and
        // sessions can be closed as soon as quota hits 0 inside trackActiveSessions().
        $timeTrackingService->trackActiveSessions();

        // Step 1: Find all devices whose time has expired
        // TimeTrackingService::getExpiredDevices() returns a collection of Device models
        // where remaining_time_minutes <= 0 (time has run out)
        // 
        // How it finds expired devices:
        // - Gets all devices with status 'active' (not already blocked)
        // - Filters devices where hasTimeExpired() returns true
        // - Returns collection of expired devices
        $expiredDevices = $timeTrackingService->getExpiredDevices();

        if ($expiredDevices->isEmpty()) {
            Log::debug('CheckTimeExpiration: no expired devices (still syncing clients with time below)');
        } else {
            // Log how many devices expired (for monitoring and debugging)
            Log::info('CheckTimeExpiration job found expired devices', [
                'count' => $expiredDevices->count(),
            ]);

            // Step 2: Process each expired device
            // Loop through the collection of expired devices
            // For each device, we need to:
            // - Update status to 'blocked' in database
            // - Block at network level (iptables)
            // - Redirect to portal (NoDogSplash)
            foreach ($expiredDevices as $device) {
            // Wrap in try-catch to handle errors gracefully
            // If one device fails, we continue processing other devices
            // This ensures one error doesn't stop the entire job
            try {
                // Log which device we're processing
                // This helps us track which devices are being blocked
                Log::info('Processing expired device', [
                    'device_id' => $device->id,
                    'device_name' => $device->name,
                    'mac_address' => $device->mac_address,
                    'remaining_time_minutes' => $device->remaining_time_minutes,
                ]);

                // Close open DeviceSession rows so dashboard "time usage" reflects granted
                // internet time only — not idle WiFi after the quota is gone.
                $device->refresh();
                foreach ($device->sessions()->whereNull('ended_at')->get() as $openSession) {
                    $timeTrackingService->closeOpenSessionWhenInternetTimeExpired($openSession);
                }

                // Step 3: Update device status to 'blocked' in database
                // This marks the device as blocked in our application
                // Status change: 'active' → 'blocked'
                // 
                // Why update database first?
                // - Database status is the "source of truth" for our application
                // - Other parts of the system check database status
                // - Even if network blocking fails, we record the intent
                $device->update(['status' => 'blocked']);

                // Step 4: Block device at network level using NetworkService
                // This actually blocks the device using iptables/firewall rules
                // 
                // What NetworkService::blockDevice() does:
                // - Gets device's MAC address
                // - Executes iptables command to block that MAC address
                // - Device can no longer access internet (physically blocked)
                // 
                // Current implementation (stub):
                // - Only updates database status (already done above)
                // - Logs the operation
                // - Actual iptables blocking will be implemented in TODO #12
                $networkBlocked = $networkService->blockDevice($device);

                // Step 5: Redirect device to portal using NoDogSplashService
                // This configures NoDogSplash to redirect all HTTP requests to portal
                // 
                // What NoDogSplashService::redirectDeviceToPortal() does:
                // - Gets device's MAC address
                // - Configures NoDogSplash to redirect this MAC address to portal
                // - All HTTP requests from device now redirect to /portal?mac=XX:XX:XX:XX:XX:XX
                // 
                // Current implementation (stub):
                // - Only logs the operation
                // - Actual NoDogSplash config will be implemented in TODO #15
                $redirectConfigured = $noDogSplashService->redirectDeviceToPortal($device);

                // Step 6: Log successful processing
                // This creates an audit trail of which devices were blocked and when
                // Helps with debugging and monitoring system health
                Log::info('Expired device processed successfully', [
                    'device_id' => $device->id,
                    'device_name' => $device->name,
                    'mac_address' => $device->mac_address,
                    'status' => 'blocked',
                    'network_blocked' => $networkBlocked,
                    'redirect_configured' => $redirectConfigured,
                ]);

                if ($device->user_id) {
                    // Notify the owning parent in real time that this device hit time limit.
                    // This connects backend enforcement (block + redirect) to immediate
                    // dashboard visibility so parents understand why internet stopped.
                    event(new TimeExpired(
                        userId: $device->user_id,
                        deviceId: $device->id,
                        deviceName: $device->name,
                        macAddress: $device->mac_address
                    ));
                }
            } catch (\Exception $e) {
                // If an error occurs while processing a device, catch it here
                // Log the error but continue processing other devices
                // This ensures one failed device doesn't stop the entire job
                Log::error('Error processing expired device', [
                    'device_id' => $device->id ?? 'unknown',
                    'device_name' => $device->name ?? 'unknown',
                    'mac_address' => $device->mac_address ?? 'unknown',
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Continue to next device (don't stop the job)
                // The job will retry on next run if needed
                continue;
            }
            }
        }

        // Step 3: Authenticate devices with time remaining that are still blocked
        // This handles the case where devices have time but are still in Preauthenticated state
        // OR have status='blocked' in database but time remaining (should be unblocked)
        // 
        // How it works:
        // - Get all devices (active OR blocked) with time remaining
        // - For each device, unblock at network level AND authenticate in NoDogSplash
        // - Update status to 'active' if it was 'blocked'
        // - allowDeviceThrough() and unblockDevice() are idempotent - safe to call multiple times
        $devicesWithTime = Device::whereIn('status', ['active', 'blocked'])
            ->where('remaining_time_minutes', '>', 0)
            ->get();

        $authenticatedCount = 0;
        $unblockedCount = 0;
        foreach ($devicesWithTime as $device) {
            // Skip whitelisted devices (handled by MonitorDeviceConnections)
            if ($device->isWhitelisted()) {
                continue;
            }

            // Step 1: Unblock device at network level (iptables)
            // This removes any blocking rules that might prevent internet access
            try {
                $unblocked = $networkService->unblockDevice($device);
                if ($unblocked) {
                    $unblockedCount++;
                    Log::debug('Unblocked device with time remaining at network level', [
                        'device_id' => $device->id,
                        'device_name' => $device->name,
                        'mac_address' => $device->mac_address,
                        'remaining_time_minutes' => $device->remaining_time_minutes,
                    ]);
                }
            } catch (\Exception $e) {
                Log::debug('Could not unblock device at network level', [
                    'device_id' => $device->id ?? 'unknown',
                    'mac_address' => $device->mac_address ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }

            // Step 2: Authenticate device in NoDogSplash (allows internet access)
            // This is idempotent - if already authenticated, it will skip
            try {
                $authenticated = $noDogSplashService->allowDeviceThrough($device);
                if ($authenticated) {
                    $authenticatedCount++;
                    Log::debug('Authenticated device with time remaining', [
                        'device_id' => $device->id,
                        'device_name' => $device->name,
                        'mac_address' => $device->mac_address,
                        'remaining_time_minutes' => $device->remaining_time_minutes,
                    ]);
                }
            } catch (\Exception $e) {
                Log::debug('Could not authenticate device with time (may not be in NoDogSplash yet)', [
                    'device_id' => $device->id ?? 'unknown',
                    'mac_address' => $device->mac_address ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
                continue;
            }

            // Step 3: Update status to 'active' if it was 'blocked'
            // This ensures the device is marked as active in the database
            if ($device->status === 'blocked') {
                $device->update(['status' => 'active']);
                Log::debug('Updated device status from blocked to active', [
                    'device_id' => $device->id,
                    'device_name' => $device->name,
                    'mac_address' => $device->mac_address,
                ]);
            }
        }

        // Log that the job completed successfully
        Log::info('CheckTimeExpiration job completed', [
            'expired_devices_processed' => $expiredDevices->count(),
            'devices_authenticated' => $authenticatedCount,
            'devices_unblocked' => $unblockedCount,
        ]);
    }
}
