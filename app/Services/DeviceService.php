<?php

namespace App\Services;

use App\Models\Device;
use Illuminate\Support\Facades\Log;

/**
 * Device Service
 * 
 * This service centralizes device management logic, providing helper methods
 * for MAC address normalization, validation, and device statistics. It separates
 * business logic from controllers, making code more organized and testable.
 * 
 * What is a Service?
 * - A service is a class that contains business logic (not HTTP handling)
 * - Services are reusable across different parts of the application
 * - They make controllers cleaner by moving complex logic out
 * - They're easier to test than controllers
 * 
 * Why Do We Need This Service?
 * - MAC address normalization logic is used in multiple places
 * - Device statistics calculation is complex and reusable
 * - Keeps DeviceController focused on HTTP request/response handling
 * - Makes code more maintainable and testable
 * 
 * Key Responsibilities:
 * 1. MAC address normalization (convert to standard format)
 * 2. MAC address validation (check format)
 * 3. MAC address existence checking (check if MAC already exists)
 * 4. Device status synchronization (sync database with network status)
 * 5. Device statistics calculation (sessions, logs, etc.)
 * 
 * Integration Points:
 * - Used by DeviceController for MAC address handling
 * - Used by form requests for validation
 * - Can be used by other services that need device management utilities
 * 
 * Usage Example:
 * ```php
 * $service = new DeviceService();
 * 
 * // Normalize MAC address
 * $normalized = $service->normalizeMacAddress('aa-bb-cc-dd-ee-ff');
 * // Returns: "AA:BB:CC:DD:EE:FF"
 * 
 * // Check if MAC exists
 * if ($service->checkMacExists('AA:BB:CC:DD:EE:FF')) {
 *     echo "MAC address already registered";
 * }
 * 
 * // Get device statistics
 * $stats = $service->getDeviceStats($device);
 * // Returns: ['sessions_count' => 10, 'logs_count' => 50, ...]
 * ```
 */
class DeviceService
{
    /**
     * Normalize MAC address to standard format.
     * 
     * This method converts MAC addresses from any valid format to the standard
     * format used by the system: XX:XX:XX:XX:XX:XX (uppercase, colon separators).
     * 
     * Accepted Input Formats:
     * - Colon format: "AA:BB:CC:DD:EE:FF" (already in correct format)
     * - Hyphen format: "AA-BB-CC-DD-EE-FF" (will be converted)
     * - Mixed case: "aa:bb:cc:dd:ee:ff" (will be converted to uppercase)
     * 
     * Output Format:
     * - Always returns: "AA:BB:CC:DD:EE:FF" (uppercase, colon separators)
     * 
     * Why Normalize?
     * - Ensures consistent format in database
     * - Makes MAC address matching easier (no need to handle multiple formats)
     * - Required for network scripts (block_device.sh expects colon format)
     * - Prevents duplicate entries with different formats
     * 
     * @param string $mac The MAC address to normalize (any valid format)
     * @return string Normalized MAC address in format XX:XX:XX:XX:XX:XX (uppercase)
     * 
     * Usage Example:
     * ```php
     * $service = new DeviceService();
     * 
     * // Normalize hyphen format
     * $normalized = $service->normalizeMacAddress('AA-BB-CC-DD-EE-FF');
     * // Returns: "AA:BB:CC:DD:EE:FF"
     * 
     * // Normalize lowercase
     * $normalized = $service->normalizeMacAddress('aa:bb:cc:dd:ee:ff');
     * // Returns: "AA:BB:CC:DD:EE:FF"
     * 
     * // Already normalized (no change)
     * $normalized = $service->normalizeMacAddress('AA:BB:CC:DD:EE:FF');
     * // Returns: "AA:BB:CC:DD:EE:FF"
     * ```
     */
    public function normalizeMacAddress(string $mac): string
    {
        // Step 1: Convert hyphens to colons
        // MAC addresses can use either colons or hyphens as separators
        // We standardize to colons because that's what network scripts expect
        // str_replace() replaces all hyphens with colons
        $normalized = str_replace('-', ':', $mac);

        // Step 2: Convert to uppercase
        // MAC addresses are case-insensitive, but we store them in uppercase
        // for consistency and easier matching
        // strtoupper() converts all lowercase letters to uppercase
        $normalized = strtoupper($normalized);

        // Step 3: Return normalized MAC address
        // Format: XX:XX:XX:XX:XX:XX (uppercase, colon separators)
        return $normalized;
    }

    /**
     * Validate MAC address format.
     * 
     * This method checks if a MAC address matches the required format.
     * It validates the format but doesn't check if the MAC exists in the database.
     * 
     * Valid Formats:
     * - Colon format: "AA:BB:CC:DD:EE:FF"
     * - Hyphen format: "AA-BB-CC-DD-EE-FF"
     * - Case insensitive: "aa:bb:cc:dd:ee:ff" is valid
     * 
     * Invalid Formats:
     * - Wrong length: "AA:BB:CC:DD:EE" (only 5 pairs)
     * - Wrong separator: "AA BB CC DD EE FF" (spaces not accepted)
     * - Invalid characters: "AA:BB:CC:DD:EE:GG" (GG is not hexadecimal)
     * 
     * @param string $mac The MAC address to validate
     * @return bool True if MAC address format is valid, false otherwise
     * 
     * Usage Example:
     * ```php
     * $service = new DeviceService();
     * 
     * // Valid formats
     * $service->validateMacAddress('AA:BB:CC:DD:EE:FF');  // Returns: true
     * $service->validateMacAddress('AA-BB-CC-DD-EE-FF');  // Returns: true
     * $service->validateMacAddress('aa:bb:cc:dd:ee:ff');  // Returns: true
     * 
     * // Invalid formats
     * $service->validateMacAddress('AA:BB:CC:DD:EE');     // Returns: false (only 5 pairs)
     * $service->validateMacAddress('AA BB CC DD EE FF');   // Returns: false (space separator)
     * $service->validateMacAddress('AA:BB:CC:DD:EE:GG'); // Returns: false (GG not hex)
     * ```
     */
    public function validateMacAddress(string $mac): bool
    {
        // Regex pattern to validate MAC address format
        // Pattern matches: 6 pairs of 2 hexadecimal characters separated by colons or hyphens
        // 
        // Pattern breakdown:
        // - ^ = Start of string
        // - ([0-9A-Fa-f]{2}) = First pair of 2 hex characters (0-9, A-F, a-f)
        // - [:-] = Separator (colon OR hyphen)
        // - ([0-9A-Fa-f]{2}[:-]){5} = Repeat pattern 5 times (pairs 2-6 with separators)
        // - ([0-9A-Fa-f]{2}) = Final pair (no separator after last pair)
        // - $ = End of string
        $pattern = '/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/';

        // preg_match() returns 1 if pattern matches, 0 if no match, false on error
        // We check if result is exactly 1 (pattern matched)
        return preg_match($pattern, $mac) === 1;
    }

    /**
     * Check if a MAC address already exists in the database.
     * 
     * This method checks if a MAC address is already registered to a device.
     * It's useful for preventing duplicate device registrations.
     * 
     * What This Checks:
     * - Queries devices table for MAC address
     * - Optionally excludes a specific device (useful when updating)
     * - Returns true if MAC exists, false if not found
     * 
     * @param string $mac The MAC address to check
     * @param int|null $excludeDeviceId Optional device ID to exclude from check (useful when updating)
     * @return bool True if MAC address exists, false otherwise
     * 
     * Usage Example:
     * ```php
     * $service = new DeviceService();
     * 
     * // Check if MAC exists (for new device)
     * if ($service->checkMacExists('AA:BB:CC:DD:EE:FF')) {
     *     echo "MAC address already registered";
     *     // Show error to user
     * }
     * 
     * // Check if MAC exists (excluding current device - for update)
     * $device = Device::find(1);
     * if ($service->checkMacExists('AA:BB:CC:DD:EE:FF', $device->id)) {
     *     echo "MAC address already registered to another device";
     *     // Show error to user
     * }
     * ```
     */
    public function checkMacExists(string $mac, ?int $excludeDeviceId = null): bool
    {
        // Normalize MAC address to standard format before checking
        // This ensures we find duplicates even if formats differ
        // Example: "AA-BB-CC-DD-EE-FF" will match "AA:BB:CC:DD:EE:FF"
        $normalizedMac = $this->normalizeMacAddress($mac);

        // Build query to check if MAC address exists
        // where('mac_address', $normalizedMac) finds devices with this MAC
        $query = Device::where('mac_address', $normalizedMac);

        // If excludeDeviceId is provided, exclude that device from the check
        // This is useful when updating a device - we want to check if MAC exists
        // on OTHER devices, but allow keeping the same MAC on the current device
        if ($excludeDeviceId !== null) {
            // where('id', '!=', $excludeDeviceId) excludes the current device
            $query->where('id', '!=', $excludeDeviceId);
        }

        // Check if any devices match
        // exists() returns true if at least one device found, false if none found
        return $query->exists();
    }

    /**
     * Sync device status between database and network.
     * 
     * This method ensures the device's database status matches its actual network status.
     * It checks the network status and updates the database if they don't match.
     * 
     * Why Do We Need This?
     * - Database status might be out of sync with network status
     * - Network blocking might have failed or been manually changed
     * - This ensures database accurately reflects network state
     * 
     * How It Works:
     * 1. Check network status using NetworkService
     * 2. Compare with database status
     * 3. If mismatch: Update database to match network status
     * 4. Log the synchronization
     * 
     * @param Device $device The device to sync
     * @return void No return value
     * 
     * Usage Example:
     * ```php
     * $service = new DeviceService();
     * $device = Device::find(1);
     * 
     * // Sync device status
     * $service->syncDeviceStatus($device);
     * // Database status now matches network status
     * ```
     */
    public function syncDeviceStatus(Device $device): void
    {
        // Get NetworkService to check network status
        // NetworkService has methods to check if device is blocked at network level
        $networkService = app(NetworkService::class);

        // Check if device is blocked at network level
        // isDeviceBlocked() queries iptables to see if device is actually blocked
        $isBlockedAtNetwork = $networkService->isDeviceBlocked($device);

        // Get current database status
        $databaseStatus = $device->status;

        // Compare network status with database status
        // If device is blocked at network but database says 'active', sync database
        if ($isBlockedAtNetwork && $databaseStatus !== 'blocked') {
            // Device is blocked at network but database says otherwise
            // Update database to match network status
            $device->update(['status' => 'blocked']);

            // Log the synchronization for debugging
            Log::info('Device status synced: database updated to match network status', [
                'device_id' => $device->id,
                'device_name' => $device->name,
                'mac_address' => $device->mac_address,
                'previous_status' => $databaseStatus,
                'new_status' => 'blocked',
                'reason' => 'Network status check showed device is blocked',
            ]);
        }
        // If device is not blocked at network but database says 'blocked', sync database
        elseif (!$isBlockedAtNetwork && $databaseStatus === 'blocked') {
            // Device is not blocked at network but database says blocked
            // Update database to match network status
            $device->update(['status' => 'active']);

            // Log the synchronization for debugging
            Log::info('Device status synced: database updated to match network status', [
                'device_id' => $device->id,
                'device_name' => $device->name,
                'mac_address' => $device->mac_address,
                'previous_status' => $databaseStatus,
                'new_status' => 'active',
                'reason' => 'Network status check showed device is not blocked',
            ]);
        }
        // If statuses match, no sync needed
    }

    /**
     * Get device statistics.
     * 
     * This method calculates and returns various statistics for a device,
     * including session counts, browsing log counts, and other metrics.
     * 
     * What Statistics Are Calculated:
     * - Total sessions count (all sessions, active and ended)
     * - Active sessions count (sessions that haven't ended)
     * - Browsing logs count (total websites visited)
     * - Access attempts count (blocked/flagged website attempts)
     * - Quiz attempts count (total quiz attempts)
     * - Video completions count (total videos completed)
     * 
     * @param Device $device The device to get statistics for
     * @return array<string, mixed> Array of statistics with keys: sessions_count, active_sessions_count, logs_count, etc.
     * 
     * Usage Example:
     * ```php
     * $service = new DeviceService();
     * $device = Device::find(1);
     * 
     * // Get device statistics
     * $stats = $service->getDeviceStats($device);
     * 
     * // Access statistics
     * echo "Total sessions: " . $stats['sessions_count'];
     * echo "Browsing logs: " . $stats['logs_count'];
     * echo "Quiz attempts: " . $stats['quiz_attempts_count'];
     * ```
     */
    public function getDeviceStats(Device $device): array
    {
        // Calculate total sessions count
        // sessions() is the relationship method that returns all sessions for this device
        // count() efficiently counts records without loading them all
        $sessionsCount = $device->sessions()->count();

        // Calculate active sessions count
        // whereNull('ended_at') finds sessions that haven't ended (still active)
        // count() counts how many active sessions exist
        $activeSessionsCount = $device->sessions()->whereNull('ended_at')->count();

        // Calculate browsing logs count
        // browsingLogs() is the relationship method that returns all browsing logs
        // count() counts total websites visited
        $logsCount = $device->browsingLogs()->count();

        // Calculate access attempts count
        // accessAttempts() is the relationship method that returns all access attempts
        // count() counts total blocked/flagged website attempts
        $accessAttemptsCount = $device->accessAttempts()->count();

        // Calculate quiz attempts count
        // quizAttempts() is the relationship method that returns all quiz attempts
        // count() counts total quiz attempts
        $quizAttemptsCount = $device->quizAttempts()->count();

        // Calculate video completions count
        // videoCompletions() is the relationship method that returns all video completions
        // count() counts total videos completed
        $videoCompletionsCount = $device->videoCompletions()->count();

        // Return array of statistics
        // This array can be used in views to display device statistics
        return [
            'sessions_count' => $sessionsCount,
            'active_sessions_count' => $activeSessionsCount,
            'logs_count' => $logsCount,
            'access_attempts_count' => $accessAttemptsCount,
            'quiz_attempts_count' => $quizAttemptsCount,
            'video_completions_count' => $videoCompletionsCount,
        ];
    }
}

