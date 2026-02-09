<?php

namespace App\Services;

use App\Models\Device;
use App\Models\DeviceSession;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
 
/**
 * Time Tracking Service
 * 
 * This service is the CRITICAL FOUNDATION for the captive portal system.
 * It monitors device internet time, calculates remaining time accurately, detects expiration,
 * and tracks active sessions to deduct time as devices browse the internet.
 * 
 * Key Responsibilities:
 * 1. Calculate accurate remaining time per device (considering active sessions)
 * 2. Detect when device time has expired (triggers portal redirect)
 * 3. Track active internet sessions (when device is browsing)
 * 4. Deduct time from devices based on active session duration
 * 5. Skip time tracking for whitelisted devices (unrestricted access)
 * 6. Handle security: Log unauthorized device attempts
 * 
 * How It Works:
 * - Devices start with initial time allocation (e.g., 15 minutes)
 * - When device is approved by parent, a session is created via startSession()
 * - Background job (TrackActiveSessions) periodically calls trackActiveSessions()
 * - Time is deducted from remaining_time_minutes based on session duration
 * - When remaining_time_minutes reaches 0, device is blocked and redirected to portal
 * - After quiz/video completion, time is granted and device is unblocked
 * 
 * Time Calculation Accuracy:
 * - Uses formula: remaining_time_minutes - active_session_duration_minutes
 * - This ensures time is accurate even if background job hasn't run yet
 * - Example: Device has 30 min in DB, session running 5 min = 25 min remaining
 * 
 * Whitelisted Devices:
 * - Skip ALL time tracking (never deduct time, never expire)
 * - Can still have sessions (for monitoring) but time never deducted
 * - calculateRemainingTime() returns 999999 (unlimited)
 * 
 * Security:
 * - If unapproved device tries to start session, logs unauthorized attempt
 * - MAC blocking will be handled by NetworkService (separate concern)
 * 
 * Usage Example:
 * ```php
 * $service = new TimeTrackingService();
 * 
 * // Check if device time expired
 * if ($service->hasTimeExpired($device)) {
 *     // Block device and redirect to portal
 * }
 * 
 * // Track active sessions (called by background job)
 * $service->trackActiveSessions();
 * 
 * // Start a new session when device is approved
 * $session = $service->startSession($device);
 * ```
 */
class TimeTrackingService
{
    /**
     * Calculate the accurate remaining time for a device.
     * 
     * This method calculates the most accurate remaining time by considering:
     * 1. The remaining_time_minutes stored in the database
     * 2. Active sessions that haven't been deducted yet
     * 
     * Why this is important:
     * - If a device has been browsing for 5 minutes but time hasn't been deducted yet,
     *   we need to account for that 5 minutes in our calculation
     * - This ensures time is as accurate as possible
     * - Example: Device has 30 min in DB, session running 5 min = 25 min remaining
     * 
     * Whitelisted devices:
     * - Return 999999 (unlimited) since they bypass all time tracking
     * 
     * @param Device $device The device to calculate remaining time for
     * @return int Remaining time in minutes (0 or positive number, or 999999 for whitelisted)
     * 
     * Usage Example:
     * ```php
     * $device = Device::find(1);
     * $service = new TimeTrackingService();
     * $remaining = $service->calculateRemainingTime($device);
     * echo "Device has {$remaining} minutes remaining";
     * // Output: "Device has 25 minutes remaining"
     * ```
     */
    public function calculateRemainingTime(Device $device): int
    {
        // If device is whitelisted, return a large number (effectively unlimited)
        // Whitelisted devices bypass all time tracking and restrictions
        if ($device->isWhitelisted()) {
            return 999999; // Large number to represent "unlimited"
        }

        // Get the base remaining time from database
        // This is the time stored in remaining_time_minutes column
        $baseRemaining = $device->remaining_time_minutes ?? 0;

        // Get active session for this device (if any)
        // activeSession() returns the most recent session that hasn't ended (ended_at is NULL)
        $activeSession = $device->activeSession();

        // If no active session, return base remaining time
        // No session means device is not currently browsing, so use stored value
        if (!$activeSession) {
            // max(0, ...) ensures we never return negative numbers
            return max(0, $baseRemaining);
        }

        // Calculate how long the active session has been running (in minutes)
        // This is time that hasn't been deducted yet by the background job
        // getDurationMinutes() calculates from started_at to now() for active sessions
        $sessionDurationMinutes = $activeSession->getDurationMinutes();

        // Accurate remaining time = base remaining - session duration not yet deducted
        // Example: Device has 30 minutes in DB, session running for 5 minutes
        // Accurate remaining = 30 - 5 = 25 minutes
        $accurateRemaining = $baseRemaining - $sessionDurationMinutes;

        // Return 0 or positive number (never negative)
        // floor() rounds down to ensure we don't overestimate remaining time
        // max(0, ...) ensures we never return negative
        return max(0, (int) floor($accurateRemaining));
    }

    /**
     * Check if a device's time has expired.
     * 
     * Returns true if the device has no remaining time (0 or negative).
     * This is used to trigger portal redirect and device blocking.
     * 
     * Important Notes:
     * - Whitelisted devices NEVER expire (always returns false)
     * - Uses calculateRemainingTime() for accurate calculation
     * - This method is called by CheckTimeExpiration background job
     * 
     * @param Device $device The device to check
     * @return bool True if time expired (should redirect to portal), false if still has time
     * 
     * Usage Example:
     * ```php
     * $device = Device::find(1);
     * $service = new TimeTrackingService();
     * 
     * if ($service->hasTimeExpired($device)) {
     *     // Time expired! Block device and redirect to portal
     *     $device->update(['status' => 'blocked']);
     *     redirect()->route('portal.landing', ['mac' => $device->mac_address]);
     * }
     * ```
     */
    public function hasTimeExpired(Device $device): bool
    {
        // Whitelisted devices never expire (unrestricted access)
        // They bypass all time limits and restrictions
        if ($device->isWhitelisted()) {
            return false;
        }

        // Calculate accurate remaining time
        // This considers both stored time and active session duration
        $remaining = $this->calculateRemainingTime($device);

        // Time expired if remaining is 0 or less
        // <= 0 means device has used all allocated time
        return $remaining <= 0;
    }

    /**
     * Get all devices that have expired (time ran out).
     * 
     * This method is used by the background job (CheckTimeExpiration) to find
     * all devices that need to be blocked and redirected to the portal.
     * 
     * How it works:
     * - Gets all devices with status 'active' (not already blocked, not whitelisted)
     * - Filters devices where hasTimeExpired() returns true
     * - Returns collection of expired devices
     * 
     * @return Collection Collection of Device models that have expired
     * 
     * Usage Example:
     * ```php
     * $service = new TimeTrackingService();
     * $expiredDevices = $service->getExpiredDevices();
     * 
     * foreach ($expiredDevices as $device) {
     *     // Block device at network level (via NetworkService)
     *     // Redirect to portal (via NoDogSplash)
     *     $device->update(['status' => 'blocked']);
     * }
     * ```
     */
    public function getExpiredDevices(): Collection
    {
        // Get all active devices (not blocked, not whitelisted)
        // We only check active devices because:
        // - Blocked devices are already blocked (no need to check again)
        // - Whitelisted devices never expire (skip them)
        $activeDevices = Device::where('status', 'active')->get();

        // Filter devices to find those that have expired
        // filter() keeps only items where callback returns true
        // hasTimeExpired() checks if device time has run out
        $expiredDevices = $activeDevices->filter(function (Device $device) {
            return $this->hasTimeExpired($device);
        });

        return $expiredDevices;
    }

    /**
     * Start a new active session for a device.
     * 
     * When a device is approved by parent/admin and starts browsing, we create
     * a session to track how long it's been online. This session is used to
     * deduct time from the device's remaining_time_minutes.
     * 
     * Important:
     * - Only creates session if device is approved (status is 'active' or 'whitelisted')
     * - Only one active session per device at a time
     * - If device already has an active session, returns that session instead
     * - If device not approved, returns null and logs unauthorized attempt (security)
     * 
     * Security:
     * - If unapproved device tries to start session, logs attempt with MAC address
     * - MAC blocking will be handled by NetworkService (to be created later)
     * 
     * @param Device $device The device to start a session for
     * @return DeviceSession|null The created or existing active session, or null if not approved
     * 
     * Usage Example:
     * ```php
     * $device = Device::find(1);
     * $service = new TimeTrackingService();
     * 
     * // When device is approved and tries to access internet
     * $session = $service->startSession($device);
     * if ($session) {
     *     echo "Session started at: {$session->started_at}";
     * } else {
     *     echo "Device not approved - session not created";
     * }
     * ```
     */
    public function startSession(Device $device): ?DeviceSession
    {
        // Check if device is approved (status is 'active' or 'whitelisted')
        // Only approved devices can have sessions
        $isApproved = $device->status === 'active' || $device->status === 'whitelisted';

        // If device is NOT approved, handle security
        if (!$isApproved) {
            // Log unauthorized attempt for security monitoring
            // This helps identify potential hackers or unauthorized devices
            Log::warning("Unauthorized device attempted to start session", [
                'device_id' => $device->id,
                'device_name' => $device->name,
                'mac_address' => $device->mac_address,
                'status' => $device->status,
                'ip_address' => $device->ip_address,
                'timestamp' => now(),
            ]);

            // Return null - no session created
            // MAC blocking will be handled by NetworkService (separate concern)
            return null;
        }

        // Check if device already has an active session
        // activeSession() returns the most recent session that hasn't ended
        $existingSession = $device->activeSession();

        // If active session exists, return it (don't create duplicate)
        // This prevents multiple active sessions for the same device
        if ($existingSession) {
            return $existingSession;
        }

        // Create new session record
        // create() saves to database automatically
        $session = $device->sessions()->create([
            'started_at' => now(),           // Current timestamp (when session started)
            'ended_at' => null,              // NULL means session is active (hasn't ended)
            'duration_seconds' => null,      // NULL until session ends (will be calculated)
            'total_bytes_sent' => 0,        // Will be updated by network monitoring (future feature)
            'total_bytes_received' => 0,     // Will be updated by network monitoring (future feature)
        ]);

        // Log session start for debugging and monitoring
        Log::info("Session started for device {$device->name} (ID: {$device->id})", [
            'device_id' => $device->id,
            'device_name' => $device->name,
            'session_id' => $session->id,
            'mac_address' => $device->mac_address,
            'status' => $device->status,
            'started_at' => $session->started_at,
        ]);

        return $session;
    }

    /**
     * End an active session and deduct time from device.
     * 
     * When a device disconnects or stops browsing, we end the session:
     * 1. Calculate how long the session lasted
     * 2. Deduct that time from device's remaining_time_minutes
     * 3. Update session record with end time and duration
     * 
     * Important:
     * - Time is only deducted if device is NOT whitelisted
     * - Session duration is calculated in minutes (rounded up)
     * - Prevents negative remaining_time_minutes
     * - Updates device's last_seen_at timestamp
     * 
     * @param DeviceSession $session The session to end
     * @return void No return value
     * 
     * Usage Example:
     * ```php
     * $session = DeviceSession::find(1);
     * $service = new TimeTrackingService();
     * 
     * // When device disconnects
     * $service->endSession($session);
     * // Time has been deducted from device
     * // Session is now marked as ended
     * ```
     */
    public function endSession(DeviceSession $session): void
    {
        // If session already ended, do nothing
        // isActive() returns false if ended_at is not NULL
        if (!$session->isActive()) {
            return;
        }

        // Get the device this session belongs to
        // device is a relationship, so we can access it directly
        $device = $session->device;

        // Set end time to now
        // This marks the session as ended
        $session->ended_at = now();

        // Calculate duration and save
        // calculateDuration() uses started_at and ended_at to calculate duration_seconds
        $session->calculateDuration();

        // Get duration in minutes (for time deduction)
        // getDurationMinutes() converts seconds to minutes
        $durationMinutes = $session->getDurationMinutes();

        // Only deduct time if device is NOT whitelisted
        // Whitelisted devices have unrestricted access (no time tracking)
        // Also check duration > 0 to avoid deducting 0 time
        if (!$device->isWhitelisted() && $durationMinutes > 0) {
            // Deduct time from device
            // deductTime() method prevents negative values
            // ceil() rounds up (e.g., 5.1 minutes = 6 minutes) to ensure fair deduction
            $device->deductTime((int) ceil($durationMinutes));

            // Log time deduction for debugging and monitoring
            Log::info("Time deducted for device {$device->name}", [
                'device_id' => $device->id,
                'device_name' => $device->name,
                'session_id' => $session->id,
                'duration_minutes' => $durationMinutes,
                'minutes_deducted' => (int) ceil($durationMinutes),
                'remaining_time_minutes' => $device->fresh()->remaining_time_minutes, // fresh() reloads from database
            ]);
        }

        // Update device's last_seen_at timestamp
        // This tracks when device was last active
        $device->update(['last_seen_at' => now()]);
    }

    /**
     * Track all active sessions and deduct time periodically.
     * 
     * This is the MAIN method called by the background job (TrackActiveSessions).
     * It processes all active sessions and deducts time from devices based on
     * how long they've been browsing.
     * 
     * How it works:
     * 1. Find all active sessions (ended_at is NULL)
     * 2. For each session, calculate how long it's been running
     * 3. Deduct that time from device's remaining_time_minutes
     * 4. Update device's last_seen_at timestamp
     * 5. If time reaches 0, device will be blocked by CheckTimeExpiration job
     * 
     * Important:
     * - Called periodically (every 1-5 minutes) by background job
     * - Only deducts time for non-whitelisted devices
     * - Only deducts if session duration >= 1 minute (prevents over-deduction)
     * - Rounds up time deduction to ensure fair calculation
     * 
     * @return void No return value
     * 
     * Usage Example:
     * ```php
     * // In background job (TrackActiveSessions)
     * $service = new TimeTrackingService();
     * $service->trackActiveSessions();
     * // All active sessions have been processed, time deducted
     * ```
     */
    public function trackActiveSessions(): void
    {
        // Get all active sessions (sessions that haven't ended)
        // whereNull('ended_at') = ended_at is NULL (session is still active)
        // with('device') = eager load device relationship (prevents N+1 queries)
        $activeSessions = DeviceSession::whereNull('ended_at')
            ->with('device') // Eager load device to avoid N+1 queries (performance optimization)
            ->get();

        // If no active sessions, nothing to do
        if ($activeSessions->isEmpty()) {
            return;
        }

        // Process each active session
        foreach ($activeSessions as $session) {
            // Get the device this session belongs to
            // Already loaded via eager loading, so no extra query
            $device = $session->device;

            // Skip whitelisted devices (no time tracking)
            // Whitelisted devices have unrestricted access
            if ($device->isWhitelisted()) {
                continue; // Skip to next session
            }

            // Calculate how long this session has been running (in minutes)
            // getDurationMinutes() calculates from started_at to now() for active sessions
            $sessionDurationMinutes = $session->getDurationMinutes();

            // Only deduct if session has been running for at least 1 minute
            // This prevents deducting time for sessions that just started
            // Also ensures we don't over-deduct time
            if ($sessionDurationMinutes >= 1) {
                // Calculate how much time to deduct
                // We deduct the full duration since the session's started_at (rounded up)
                // ceil() rounds up: 5.1 minutes = 6 minutes (ensures fair deduction)
                $minutesToDeduct = (int) ceil($sessionDurationMinutes);

                // Deduct time from device
                // deductTime() prevents negative values (won't go below 0)
                $device->deductTime($minutesToDeduct);

                // BUG FIX: Reset session's started_at to now() after deducting time.
                // Previously, started_at was never updated, so getDurationMinutes() always
                // returned the TOTAL duration from the original session start. This meant
                // each run of trackActiveSessions() would deduct cumulative time instead of
                // only the incremental time since the last deduction.
                //
                // Example of the bug (job runs every 5 minutes):
                //   Run 1 (t=5min):  duration=5  → deducted 5  (correct)
                //   Run 2 (t=10min): duration=10 → deducted 10 (should be 5, over-deducted by 5)
                //   Run 3 (t=15min): duration=15 → deducted 15 (should be 5, over-deducted by 10)
                //   Total deducted: 30 minutes instead of correct 15 minutes!
                //
                // Fix: By resetting started_at to now() after each deduction, the next run
                // only calculates duration from this reset point, ensuring only the incremental
                // time is deducted:
                //   Run 1 (t=5min):  duration=5 → deduct 5 → reset started_at to t=5min
                //   Run 2 (t=10min): duration=5 → deduct 5 → reset started_at to t=10min
                //   Run 3 (t=15min): duration=5 → deduct 5 → reset started_at to t=15min
                //   Total deducted: 15 minutes (correct!)
                $session->update(['started_at' => now()]);

                // Update device's last_seen_at (device is currently active)
                // This tracks when device was last seen online
                $device->update(['last_seen_at' => now()]);

                // Log for debugging and monitoring
                Log::debug("Time deducted from active session", [
                    'device_id' => $device->id,
                    'device_name' => $device->name,
                    'session_id' => $session->id,
                    'session_duration_minutes' => $sessionDurationMinutes,
                    'minutes_deducted' => $minutesToDeduct,
                    'remaining_time_minutes' => $device->fresh()->remaining_time_minutes, // fresh() reloads from database
                ]);
            }
        }
    }

    /**
     * Get all active sessions, optionally filtered by device.
     * 
     * Returns a collection of DeviceSession models that are currently active
     * (ended_at is NULL). Used for monitoring and dashboard display.
     * 
     * @param Device|null $device Optional: Filter by specific device
     * @return Collection Collection of DeviceSession models
     * 
     * Usage Example:
     * ```php
     * $service = new TimeTrackingService();
     * 
     * // Get all active sessions
     * $allSessions = $service->getActiveSessions();
     * 
     * // Get active sessions for specific device
     * $device = Device::find(1);
     * $deviceSessions = $service->getActiveSessions($device);
     * ```
     */
    public function getActiveSessions(?Device $device = null): Collection
    {
        // Start query for active sessions
        // whereNull('ended_at') = only sessions that haven't ended
        $query = DeviceSession::whereNull('ended_at')
            ->with('device') // Eager load device relationship (performance optimization)
            ->orderBy('started_at', 'desc'); // Newest first (most recent sessions first)

        // If device specified, filter by device
        // This allows getting sessions for a specific device
        if ($device) {
            $query->where('device_id', $device->id);
        }

        return $query->get();
    }

    /**
     * Get the duration of a session in minutes.
     * 
     * Calculates how long a session has been running or ran.
     * Handles both active sessions (still running) and ended sessions.
     * 
     * This is a helper method that wraps the model's getDurationMinutes() method
     * and converts it to an integer (rounded up).
     * 
     * @param DeviceSession $session The session to get duration for
     * @return int Duration in minutes (rounded up)
     * 
     * Usage Example:
     * ```php
     * $session = DeviceSession::find(1);
     * $service = new TimeTrackingService();
     * $duration = $service->getSessionDuration($session);
     * echo "Session duration: {$duration} minutes";
     * ```
     */
    public function getSessionDuration(DeviceSession $session): int
    {
        // Use the model's method which handles both active and ended sessions
        // getDurationMinutes() returns float, we convert to int
        // ceil() rounds up to ensure we don't underestimate duration
        return (int) ceil($session->getDurationMinutes());
    }

    /**
     * Check if a device should have its time tracked.
     * 
     * Determines whether time tracking applies to a device.
     * Whitelisted devices skip all time tracking (unrestricted access).
     * 
     * This is a helper method used throughout the service to check
     * if time tracking should apply to a device.
     * 
     * @param Device $device The device to check
     * @return bool True if time should be tracked, false if whitelisted
     * 
     * Usage Example:
     * ```php
     * $device = Device::find(1);
     * $service = new TimeTrackingService();
     * 
     * if ($service->shouldTrackTime($device)) {
     *     // Track time, deduct time, check expiration
     * } else {
     *     // Skip time tracking (whitelisted device)
     * }
     * ```
     */
    public function shouldTrackTime(Device $device): bool
    {
        // Whitelisted devices skip all time tracking
        // They have unrestricted access, no time limits
        // isWhitelisted() checks if device status is 'whitelisted'
        return !$device->isWhitelisted();
    }

    /**
     * Handle device disconnection - automatically end active session.
     * 
     * This method is called when a device disconnects from the WiFi network.
     * It automatically ends any active session for the device, deducts the
     * final time, and clears the device's IP address.
     * 
     * This prevents time from being wasted when a device is in standby mode
     * or disconnected but still has an active session running.
     * 
     * How it works:
     * 1. Find device by MAC address
     * 2. Find active session for the device
     * 3. End the session (deducts time automatically)
     * 4. Clear device's IP address (set to null)
     * 5. Log the disconnection
     * 
     * Important:
     * - Safe to call multiple times (won't double-deduct time)
     * - Only ends active sessions (already ended sessions are skipped)
     * - Works with whitelisted devices (ends session but doesn't deduct time)
     * 
     * @param string $macAddress The MAC address of the disconnected device
     * @return bool True if session was ended, false if no active session found
     * 
     * Usage Example:
     * ```php
     * // In MonitorDeviceConnections background job
     * $service = new TimeTrackingService();
     * 
     * // When network monitoring detects device disconnected
     * $macAddress = 'AA:BB:CC:DD:EE:FF';
     * $sessionEnded = $service->handleDeviceDisconnection($macAddress);
     * 
     * if ($sessionEnded) {
     *     Log::info("Device {$macAddress} disconnected, session ended");
     * } else {
     *     Log::debug("Device {$macAddress} disconnected, but no active session found");
     * }
     * ```
     */
    public function handleDeviceDisconnection(string $macAddress): bool
    {
        // Find device by MAC address
        // MAC address is unique identifier for each device
        $device = Device::where('mac_address', $macAddress)->first();

        // If device not found, nothing to do
        // This can happen if device was never registered in the system
        if (!$device) {
            Log::debug("Device disconnection detected for unknown MAC address: {$macAddress}");
            return false;
        }

        // Get active session for this device
        // activeSession() returns the most recent session that hasn't ended
        $activeSession = $device->activeSession();

        // If no active session, nothing to end
        // Device might have disconnected after session already ended
        if (!$activeSession) {
            // Still clear IP address if it exists (device is disconnected)
            if ($device->ip_address) {
                $device->update(['ip_address' => null]);
                Log::debug("Device {$device->name} ({$macAddress}) disconnected, but no active session to end. IP address cleared.");
            }
            return false;
        }

        // End the active session
        // This will:
        // - Calculate session duration
        // - Deduct time from device (if not whitelisted)
        // - Mark session as ended
        // - Update device's last_seen_at
        $this->endSession($activeSession);

        // Clear device's IP address (device is no longer connected)
        // ip_address is set when device connects, cleared when it disconnects
        $device->update(['ip_address' => null]);

        // Log the disconnection for monitoring
        Log::info("Device disconnected and session ended", [
            'device_id' => $device->id,
            'device_name' => $device->name,
            'mac_address' => $macAddress,
            'session_id' => $activeSession->id,
            'session_duration_minutes' => $activeSession->getDurationMinutes(),
            'remaining_time_minutes' => $device->fresh()->remaining_time_minutes,
        ]);

        return true;
    }

    /**
     * Handle multiple device disconnections at once.
     * 
     * This is a convenience method for processing multiple disconnected devices
     * in a single call. Useful when network monitoring detects multiple devices
     * disconnected at the same time.
     * 
     * @param array<string> $macAddresses Array of MAC addresses that disconnected
     * @return array<string, bool> Associative array: MAC address => session ended (true/false)
     * 
     * Usage Example:
     * ```php
     * $service = new TimeTrackingService();
     * 
     * // Multiple devices disconnected
     * $disconnectedMacs = ['AA:BB:CC:DD:EE:FF', '11:22:33:44:55:66'];
     * $results = $service->handleMultipleDeviceDisconnections($disconnectedMacs);
     * 
     * // $results = [
     * //     'AA:BB:CC:DD:EE:FF' => true,  // Session ended
     * //     '11:22:33:44:55:66' => false  // No active session
     * // ]
     * ```
     */
    public function handleMultipleDeviceDisconnections(array $macAddresses): array
    {
        $results = [];

        foreach ($macAddresses as $macAddress) {
            $results[$macAddress] = $this->handleDeviceDisconnection($macAddress);
        }

        return $results;
    }

    /**
     * Check for and end sessions of devices that appear to be disconnected.
     * 
     * This method checks all devices with active sessions and verifies if they
     * are still connected to the network. If a device has an active session but
     * no IP address (or IP address is stale), it ends the session.
     * 
     * This is useful as a safety mechanism to catch devices that disconnected
     * but the disconnection wasn't detected by network monitoring.
     * 
     * How it works:
     * 1. Get all devices with active sessions
     * 2. Check if device has IP address
     * 3. If no IP address, device is likely disconnected
     * 4. End the session automatically
     * 
     * Important:
     * - This is a fallback mechanism
     * - Should be called periodically by a background job
     * - Works alongside handleDeviceDisconnection() for redundancy
     * 
     * @return int Number of sessions ended
     * 
     * Usage Example:
     * ```php
     * // In background job (CheckDisconnectedDevices)
     * $service = new TimeTrackingService();
     * $sessionsEnded = $service->endSessionsForDisconnectedDevices();
     * 
     * Log::info("Ended {$sessionsEnded} sessions for disconnected devices");
     * ```
     */
    public function endSessionsForDisconnectedDevices(): int
    {
        // Get all active sessions with their devices
        $activeSessions = DeviceSession::whereNull('ended_at')
            ->with('device')
            ->get();

        $sessionsEnded = 0;

        foreach ($activeSessions as $session) {
            $device = $session->device;

            // If device has no IP address, it's likely disconnected
            // IP address is set when device connects, cleared when it disconnects
            if (!$device->ip_address) {
                // End the session (deducts time)
                $this->endSession($session);
                $sessionsEnded++;

                Log::info("Ended session for disconnected device (no IP address)", [
                    'device_id' => $device->id,
                    'device_name' => $device->name,
                    'mac_address' => $device->mac_address,
                    'session_id' => $session->id,
                ]);
            }
        }

        return $sessionsEnded;
    }
}

