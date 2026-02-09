<?php

namespace App\Jobs;

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
 * Monitor Device Connections Job
 * 
 * This background job periodically monitors the network to detect new devices
 * connecting to the WiFi access point and devices that have disconnected. It
 * updates device IP addresses, ends sessions for disconnected devices, and logs
 * new device connections for parent review.
 * 
 * What is a Background Job?
 * - A background job is code that runs automatically without user interaction
 * - Think of it like a "robot assistant" that works in the background
 * - It runs on a schedule (every 2 minutes) to monitor device connections
 * - This ensures we always know which devices are connected to the network
 * 
 * Why Do We Need This Job?
 * - Devices connect and disconnect from the WiFi network constantly
 * - We need to know which devices are currently connected
 * - We need to update device IP addresses when they connect
 * - We need to end sessions for devices that disconnected
 * - We need to detect new devices (not in database) for parent review
 * 
 * How It Works:
 * 1. Job runs automatically every 2 minutes (via Laravel scheduler)
 * 2. Calls NetworkService::getConnectedDevices() to get current device list
 * 3. Compares connected devices with database devices
 * 4. For each connected device:
 *    - Updates device IP address if changed
 *    - Updates device last_seen_at timestamp
 * 5. For devices in database but not connected:
 *    - Ends active sessions (via TimeTrackingService)
 *    - Clears IP address
 * 6. For new devices (connected but not in database):
 *    - Logs new device connection for parent review
 * 
 * What Happens When Device Connects:
 * - Device IP address is updated in database
 * - Device last_seen_at timestamp is updated
 * - If device is approved, session can be started
 * 
 * What Happens When Device Disconnects:
 * - Active sessions are ended (time is deducted)
 * - Device IP address is cleared
 * - Device last_seen_at timestamp is not updated (shows last connection time)
 * 
 * What Happens When New Device Connects:
 * - New device is logged for parent review
 * - Parent can add device to system later
 * - Device is not automatically added (security measure)
 * 
 * Integration with Other Services:
 * - NetworkService: Gets list of connected devices (getConnectedDevices())
 * - TimeTrackingService: Ends sessions for disconnected devices (endSession())
 * - Device Model: Stores IP address and last_seen_at timestamp
 * 
 * Error Handling:
 * - If NetworkService fails, error is logged but job doesn't crash
 * - If individual device update fails, error is logged but job continues
 * - Job will retry on next run if needed
 * 
 * Scheduling:
 * - Registered in routes/console.php (Laravel 11+) or app/Console/Kernel.php (Laravel 10)
 * - Runs every 2 minutes to balance detection speed and performance
 * - Uses Laravel's scheduler to run automatically
 * 
 * Why Every 2 Minutes?
 * - Too frequent (every 30 seconds): Wastes server resources, may cause conflicts
 * - Too infrequent (every 5 minutes): Device connections/disconnections detected slowly
 * - 2 minutes is a good balance: Fast detection without overloading server
 * 
 * Usage Example:
 * ```php
 * // Job runs automatically via scheduler
 * // No manual call needed - Laravel handles it
 * 
 * // To test manually:
 * use App\Jobs\MonitorDeviceConnections;
 * MonitorDeviceConnections::dispatch();
 * ```
 */
class MonitorDeviceConnections implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job to monitor device connections.
     * 
     * This is the main method that runs when the job executes. It:
     * 1. Gets list of currently connected devices from network
     * 2. Compares with database devices to find changes
     * 3. Updates device IP addresses and timestamps
     * 4. Ends sessions for disconnected devices
     * 5. Logs new device connections for parent review
     * 
     * How It Works Step-by-Step:
     * - Step 1: Get connected devices from network (via NetworkService)
     * - Step 2: Get all devices from database
     * - Step 3: Process connected devices (update IP, update timestamp)
     * - Step 4: Process disconnected devices (end sessions, clear IP)
     * - Step 5: Detect new devices (not in database, log for review)
     * 
     * Error Handling:
     * - If NetworkService fails, error is logged but job continues
     * - If individual device update fails, error is logged but job continues
     * - Job doesn't crash - errors are logged for debugging
     * 
     * @param NetworkService $networkService The network service (injected by Laravel)
     * @param TimeTrackingService $timeTrackingService The time tracking service (injected by Laravel)
     * @return void No return value
     * 
     * Usage:
     * This method is called automatically by Laravel when the job runs.
     * You don't need to call it manually - the scheduler handles it.
     */
    public function handle(
        NetworkService $networkService,
        TimeTrackingService $timeTrackingService,
        NoDogSplashService $noDogSplashService
    ): void {
        // Log that the job started running
        // This helps us track when the job executes and debug any issues
        Log::info('MonitorDeviceConnections job started - monitoring device connections');

        // Wrap in try-catch to handle errors gracefully
        // If NetworkService fails, we catch the error and log it
        // This ensures the job doesn't crash and can retry on next run
        try {
            // Step 1: Get list of currently connected devices from network
            // NetworkService::getConnectedDevices() queries the network to find
            // all devices currently connected to the WiFi access point
            // 
            // What getConnectedDevices() Returns:
            // - Array of device information:
            //   [
            //     [
            //       'mac_address' => 'AA:BB:CC:DD:EE:FF',
            //       'ip_address' => '192.168.4.5',
            //       'hostname' => 'device-hostname'
            //     ],
            //     ...
            //   ]
            // - Empty array if no devices connected or if query fails
            // 
            // How It Works:
            // - Executes get_connected_devices.sh script via ScriptExecutor
            // - Script queries ARP table for wlan0 interface
            // - Maps IP addresses to MAC addresses
            // - Checks DHCP leases for hostname information
            // - Returns JSON array of connected devices
            $connectedDevices = $networkService->getConnectedDevices();

            // If no devices connected, log and exit early
            // This is normal if no devices are connected to the network
            if (empty($connectedDevices)) {
                Log::debug('MonitorDeviceConnections job completed - no devices connected');
                return; // Exit early - no work to do
            }

            // Step 2: Get all devices from database
            // We need to compare connected devices with database devices
            // to find which devices are new, which are connected, and which disconnected
            // 
            // Why Get All Devices?
            // - We need to check if each connected device exists in database
            // - We need to check if each database device is still connected
            // - This allows us to detect new devices and disconnected devices
            $databaseDevices = Device::all()->mapWithKeys(function ($device) {
                // Normalize MAC address to uppercase with colons for consistent lookup
                // This ensures we can match devices regardless of how MAC is stored in DB
                $normalizedMac = strtoupper(str_replace(['-', '_'], ':', $device->mac_address));
                return [$normalizedMac => $device];
            });
            // This creates a collection indexed by normalized MAC address (uppercase with colons)
            // Example: ['E6:6A:8F:19:BE:B1' => Device, '42:B8:77:AE:74:12' => Device]

            // Step 3: Process connected devices
            // For each device currently connected to the network:
            // - Update IP address if changed
            // - Update last_seen_at timestamp
            // - If device is in database, update it
            // - If device is not in database, log it as new device
            $connectedCount = 0;
            $newDevicesCount = 0;

            foreach ($connectedDevices as $connectedDevice) {
                // Wrap in try-catch to handle individual device errors
                // If one device fails, we continue processing other devices
                try {
                    // Extract device information from network query result
                    $macAddress = $connectedDevice['mac_address'] ?? null;
                    $ipAddress = $connectedDevice['ip_address'] ?? null;
                    $hostname = $connectedDevice['hostname'] ?? 'unknown';

                    // Validate MAC address exists
                    // MAC address is required to identify devices
                    if (!$macAddress) {
                        Log::warning('Connected device missing MAC address', [
                            'device_info' => $connectedDevice,
                        ]);
                        continue; // Skip this device, continue with next
                    }

                    // Normalize MAC address to uppercase with colons
                    // This ensures consistent format for comparison
                    // Example: "aa:bb:cc:dd:ee:ff" → "AA:BB:CC:DD:EE:FF"
                    $macAddress = strtoupper(str_replace(['-', '_'], ':', $macAddress));

                    // Check if device exists in database
                    // If device exists, update it
                    // If device doesn't exist, log it as new device
                    if (isset($databaseDevices[$macAddress])) {
                        // Device exists in database - update it
                        $device = $databaseDevices[$macAddress];

                        // Update IP address if changed
                        // IP addresses can change when device reconnects
                        if ($device->ip_address !== $ipAddress) {
                            $device->update(['ip_address' => $ipAddress]);
                        }

                        // Update last_seen_at timestamp
                        // This tracks when device was last seen online
                        $device->update(['last_seen_at' => now()]);

                        // Auto-authenticate whitelisted devices in NoDogSplash
                        // Whitelisted devices (parent devices, etc.) should bypass portal redirect
                        // This ensures they can access internet immediately without being redirected
                        // The allowDeviceThrough() method is idempotent - safe to call multiple times
                        // It checks if device is already authenticated and skips if so
                        if ($device->isWhitelisted()) {
                            try {
                                // Authenticate device in NoDogSplash (allows internet access)
                                // This puts device in Authenticated state, bypassing portal redirect
                                $noDogSplashService->allowDeviceThrough($device);

                                Log::debug('Auto-authenticated whitelisted device in NoDogSplash', [
                                    'device_id' => $device->id,
                                    'device_name' => $device->name,
                                    'mac_address' => $macAddress,
                                    'status' => $device->status,
                                    'role' => $device->role,
                                ]);
                            } catch (\Exception $e) {
                                // Log error but don't fail the job
                                // Device will still be updated, just NoDogSplash auth might have failed
                                // This can happen if device is not yet in NoDogSplash client list
                                Log::debug('Could not auto-authenticate whitelisted device (may not be in NoDogSplash yet)', [
                                    'device_id' => $device->id,
                                    'mac_address' => $macAddress,
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        }

                        // Also authenticate active devices with time remaining
                        // This ensures devices with time can access internet immediately
                        if ($device->status === 'active' && $device->remaining_time_minutes > 0) {
                            try {
                                // Authenticate device in NoDogSplash (allows internet access)
                                $noDogSplashService->allowDeviceThrough($device);

                                Log::debug('Auto-authenticated device with time remaining', [
                                    'device_id' => $device->id,
                                    'device_name' => $device->name,
                                    'mac_address' => $macAddress,
                                    'remaining_time_minutes' => $device->remaining_time_minutes,
                                ]);
                            } catch (\Exception $e) {
                                // Log error but don't fail the job
                                Log::debug('Could not auto-authenticate device with time (may not be in NoDogSplash yet)', [
                                    'device_id' => $device->id,
                                    'mac_address' => $macAddress,
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        }

                        // BUG FIX: Start a session for connected active devices so that
                        // TrackActiveSessions can deduct time from their remaining_time_minutes.
                        // Previously, startSession() was never called from anywhere in the codebase,
                        // which meant no DeviceSession records were created. Without active sessions,
                        // the TrackActiveSessions job found nothing to process and time was never
                        // deducted — causing remaining_time_minutes to stay the same indefinitely.
                        //
                        // startSession() is safe to call repeatedly because it checks for existing
                        // active sessions first and returns the existing one if found (prevents duplicates).
                        // Only creates a session if the device is approved (status 'active' or 'whitelisted').
                        if ($device->status === 'active') {
                            try {
                                $session = $timeTrackingService->startSession($device);
                                if ($session) {
                                    Log::debug('Session ensured for connected active device', [
                                        'device_id' => $device->id,
                                        'device_name' => $device->name,
                                        'mac_address' => $macAddress,
                                        'session_id' => $session->id,
                                        'session_started_at' => $session->started_at,
                                    ]);
                                }
                            } catch (\Exception $e) {
                                // Log error but don't fail the job — session will be retried on next run
                                Log::warning('Could not start session for active device', [
                                    'device_id' => $device->id,
                                    'mac_address' => $macAddress,
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        }

                        $connectedCount++;

                        Log::debug('Device connection updated', [
                            'device_id' => $device->id,
                            'device_name' => $device->name,
                            'mac_address' => $macAddress,
                            'ip_address' => $ipAddress,
                            'hostname' => $hostname,
                        ]);
                    } else {
                        // Device doesn't exist in database - new device detected
                        // Log it for parent review (parent can add device later)
                        // We don't automatically add devices (security measure)
                        $newDevicesCount++;

                        Log::info('New device detected on network', [
                            'mac_address' => $macAddress,
                            'ip_address' => $ipAddress,
                            'hostname' => $hostname,
                            'action' => 'logged_for_parent_review',
                        ]);
                    }
                } catch (\Exception $e) {
                    // If individual device update fails, log error but continue
                    // This ensures one failed device doesn't stop processing of other devices
                    Log::error('Error processing connected device', [
                        'device_info' => $connectedDevice ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);
                    continue; // Continue with next device
                }
            }

            // Step 4: Process disconnected devices
            // For each device in database that is not in connected devices list:
            // - End active sessions (deduct time)
            // - Clear IP address
            // - Note: We don't update last_seen_at (shows last connection time)
            $disconnectedCount = 0;

            foreach ($databaseDevices as $macAddress => $device) {
                // Wrap in try-catch to handle individual device errors
                try {
                    // Check if device is still connected
                    // If device is not in connected devices list, it has disconnected
                    $isConnected = false;
                    foreach ($connectedDevices as $connectedDevice) {
                        $connectedMac = strtoupper(str_replace(['-', '_'], ':', $connectedDevice['mac_address'] ?? ''));
                        if ($connectedMac === $macAddress) {
                            $isConnected = true;
                            break; // Found device, stop searching
                        }
                    }

                    // If device is not connected, handle disconnection
                    if (!$isConnected) {
                        // Check if device has active sessions
                        // If device has active sessions, we need to end them
                        $activeSessions = $device->sessions()->whereNull('ended_at')->get();

                        if ($activeSessions->isNotEmpty()) {
                            // Device has active sessions - end them
                            // This deducts time from device based on session duration
                            foreach ($activeSessions as $session) {
                                $timeTrackingService->endSession($session);
                            }

                            Log::info('Ended active sessions for disconnected device', [
                                'device_id' => $device->id,
                                'device_name' => $device->name,
                                'mac_address' => $macAddress,
                                'sessions_ended' => $activeSessions->count(),
                            ]);
                        }

                        // Clear IP address (device is no longer connected)
                        // Only clear if IP address is set (avoid unnecessary updates)
                        if ($device->ip_address) {
                            $device->update(['ip_address' => null]);
                        }

                        $disconnectedCount++;

                        Log::debug('Device disconnected', [
                            'device_id' => $device->id,
                            'device_name' => $device->name,
                            'mac_address' => $macAddress,
                        ]);
                    }
                } catch (\Exception $e) {
                    // If individual device disconnection handling fails, log error but continue
                    Log::error('Error processing disconnected device', [
                        'device_id' => $device->id ?? 'unknown',
                        'mac_address' => $macAddress ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);
                    continue; // Continue with next device
                }
            }

            // Step 5: Log job completion with summary
            // This helps us monitor job execution and understand system activity
            Log::info('MonitorDeviceConnections job completed', [
                'connected_devices_processed' => $connectedCount,
                'disconnected_devices_processed' => $disconnectedCount,
                'new_devices_detected' => $newDevicesCount,
            ]);
        } catch (\Exception $e) {
            // If NetworkService fails or other critical error occurs, catch it here
            // Log the error but don't crash the job
            // This ensures the job can retry on next run
            Log::error('MonitorDeviceConnections job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw the exception so Laravel's queue system can handle retries
            // Laravel will automatically retry the job based on queue configuration
            throw $e;
        }
    }
}
