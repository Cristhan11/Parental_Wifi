<?php

namespace App\Services;

use App\Models\Device;
use Illuminate\Support\Facades\Log;

/**
 * Network Service
 * 
 * This service handles network-level blocking and unblocking of devices.
 * It manages firewall rules (iptables/nftables) to control which devices
 * can access the internet at the network level.
 * 
 * What is Network-Level Blocking?
 * - Network-level blocking means using the firewall (iptables/nftables) to
 *   physically prevent a device from accessing the internet
 * - This is different from database status - even if database says "blocked",
 *   the device can still access internet unless blocked at network level
 * - Think of it like a security guard: database status is the "list" of who
 *   should be blocked, but network blocking is the actual "gate" that stops them
 * 
 * Why We Need Both Database and Network Blocking:
 * - Database status: Tracks device state in our application (for UI, reports, etc.)
 * - Network blocking: Actually enforces the blocking at the firewall level
 * - Both must be in sync for the system to work correctly
 * 
 * Current Implementation:
 * - This service integrates with shell scripts via ScriptExecutor for network-level operations
 * - ScriptExecutor provides secure script execution with validation and error handling
 * - All network operations (block, unblock, whitelist) are performed via shell scripts
 * - Scripts use iptables to modify firewall rules at the system level
 * 
 * How It Works:
 * - blockDevice(): Executes block_device.sh script to block device's MAC address
 * - unblockDevice(): Executes unblock_device.sh script to remove blocking rules
 * - isDeviceBlocked(): Checks iptables rules directly to see if device is blocked
 * - whitelistDevice(): Executes whitelist_device.sh script to bypass all restrictions
 * - getConnectedDevices(): Executes get_connected_devices.sh to get device list
 * - getTrafficStats(): Executes monitor_traffic.sh to get traffic statistics
 * 
 * MAC Address Usage:
 * - Each device has a unique MAC address (like a fingerprint)
 * - MAC address is used to identify devices at the network level
 * - Example: AA:BB:CC:DD:EE:FF is a MAC address format
 * 
 * Integration Points:
 * - Called by CheckTimeExpiration job when device time expires
 * - Called by TimeGrantingService when time is granted (to unblock device)
 * - Works together with NoDogSplashService for complete portal control
 * 
 * Usage Example:
 * ```php
 * $service = new NetworkService(); // ScriptExecutor is automatically injected
 * $device = Device::find(1);
 * 
 * // Block device at network level
 * $service->blockDevice($device);
 * // Device can no longer access internet (iptables rule added via block_device.sh)
 * 
 * // Unblock device at network level
 * $service->unblockDevice($device);
 * // Device can access internet again (iptables rule removed via unblock_device.sh)
 * 
 * // Check if device is blocked
 * if ($service->isDeviceBlocked($device)) {
 *     echo "Device is blocked at network level";
 * }
 * 
 * // Whitelist device to bypass all restrictions
 * $service->whitelistDevice($device);
 * 
 * // Get connected devices
 * $devices = $service->getConnectedDevices();
 * 
 * // Get traffic statistics
 * $stats = $service->getTrafficStats();
 * ```
 */
class NetworkService
{
    /**
     * ScriptExecutor instance for secure script execution.
     * 
     * ScriptExecutor provides a secure wrapper for executing shell scripts.
     * It validates scripts, sanitizes arguments, and handles errors safely.
     * 
     * Why Dependency Injection?
     * - Makes the service testable (can inject mock ScriptExecutor in tests)
     * - Follows Laravel's dependency injection pattern
     * - Allows easy swapping of implementations if needed
     * - Promotes loose coupling between NetworkService and script execution
     * 
     * How Laravel Resolves This:
     * - Laravel's service container automatically resolves ScriptExecutor
     * - When NetworkService is instantiated, Laravel creates ScriptExecutor
     * - No manual instantiation needed - Laravel handles it automatically
     * 
     * @var ScriptExecutor
     */
    protected ScriptExecutor $scriptExecutor;

    /**
     * Constructor - Initialize NetworkService with ScriptExecutor.
     * 
     * This constructor uses Laravel's dependency injection to automatically
     * receive a ScriptExecutor instance. Laravel's service container will
     * automatically create and inject the ScriptExecutor when NetworkService
     * is instantiated.
     * 
     * Why Dependency Injection?
     * - **Testability**: Can inject mock ScriptExecutor in unit tests
     * - **Flexibility**: Can swap ScriptExecutor implementation if needed
     * - **Loose Coupling**: NetworkService doesn't create ScriptExecutor directly
     * - **Laravel Pattern**: Follows Laravel's dependency injection conventions
     * 
     * How It Works:
     * - When NetworkService is created (e.g., via service container or constructor),
     *   Laravel automatically resolves ScriptExecutor from the service container
     * - If ScriptExecutor is not bound in container, Laravel creates a new instance
     * - The ScriptExecutor instance is stored as a property for use in methods
     * 
     * Usage:
     * - NetworkService is typically instantiated by Laravel automatically
     * - Other services (TimeGrantingService, CheckTimeExpiration job) receive
     *   NetworkService via dependency injection
     * - No manual instantiation needed - Laravel handles everything
     * 
     * @param ScriptExecutor $scriptExecutor The script executor service (injected by Laravel)
     */
    public function __construct(ScriptExecutor $scriptExecutor)
    {
        // Store ScriptExecutor instance for use in methods
        // This allows all methods in NetworkService to execute scripts securely
        // ScriptExecutor handles validation, sanitization, and error handling
        $this->scriptExecutor = $scriptExecutor;
    }

    /**
     * Block a device at the network level using firewall rules.
     * 
     * This method blocks a device's MAC address using iptables/nftables,
     * preventing the device from accessing the internet at the network level.
     * 
     * What Happens:
     * 1. Gets device's MAC address (unique identifier)
     * 2. Executes block_device.sh script via ScriptExecutor
     * 3. Script adds iptables DROP rules to block the MAC address
     * 4. Device can no longer access internet (all traffic blocked)
     * 5. Updates database status to 'blocked'
     * 6. Logs the operation for debugging and audit trail
     * 
     * Current Implementation:
     * - Executes block_device.sh script via ScriptExecutor
     * - Script validates MAC address format and normalizes it
     * - Script adds iptables DROP rules to both INPUT and FORWARD chains:
     *   - `iptables -A INPUT -i wlan0 -m mac --mac-source AA:BB:CC:DD:EE:FF -j DROP`
     *   - `iptables -A FORWARD -i wlan0 -m mac --mac-source AA:BB:CC:DD:EE:FF -j DROP`
     * - Updates database status to 'blocked'
     * - Device is physically unable to access internet
     * 
     * Why Block at Network Level?
     * - Even if database says "blocked", device can still access internet
     * - Network-level blocking physically prevents access
     * - This is the "real" enforcement of the blocking
     * 
     * Error Handling:
     * - If iptables command fails, logs error but doesn't crash
     * - Database status is still updated (partial success)
     * - System continues to function even if network blocking fails
     * 
     * @param Device $device The device to block at network level
     * @return bool True if blocking was successful (or logged), false on error
     * 
     * Usage Example:
     * ```php
     * $device = Device::find(1);
     * $service = new NetworkService();
     * 
     * // Block device when time expires
     * if ($service->blockDevice($device)) {
     *     echo "Device blocked successfully";
     *     // Device can no longer access internet
     * } else {
     *     echo "Blocking failed - check logs";
     * }
     * ```
     */
    public function blockDevice(Device $device): bool
    {
        // Step 1: Get device's MAC address (unique identifier)
        // MAC address is like a fingerprint - each device has a unique one
        // Format: XX:XX:XX:XX:XX:XX (6 pairs of hexadecimal characters)
        // Example: "AA:BB:CC:DD:EE:FF"
        // 
        // Why MAC Address?
        // - MAC address is the device's network interface identifier
        // - It's unique to each network adapter (can't be easily changed)
        // - iptables can block traffic by MAC address
        // - More reliable than IP address (IPs can change via DHCP)
        $macAddress = $device->mac_address;

        // Step 2: Validate MAC address exists (safety check)
        // If device doesn't have MAC address, we can't block it at network level
        // This prevents errors when trying to execute the blocking script
        // 
        // Why This Check?
        // - Devices must have MAC address to be blocked
        // - Missing MAC address indicates data integrity issue
        // - Better to fail early with clear error than execute script with empty MAC
        if (empty($macAddress)) {
            // MAC address is missing - log error and return failure
            // This is a data integrity issue that should be investigated
            Log::error('Cannot block device: MAC address is missing', [
                'device_id' => $device->id,
                'device_name' => $device->name,
            ]);

            return false; // Can't block without MAC address
        }

        // Step 3: Execute block_device.sh script via ScriptExecutor
        // This is where the actual network-level blocking happens
        // 
        // What block_device.sh Does:
        // - Validates MAC address format
        // - Normalizes MAC address to standard format (colons, uppercase)
        // - Adds iptables DROP rules to INPUT and FORWARD chains
        // - Blocks device on both chains (traffic to Pi and traffic through Pi)
        // 
        // Why Use ScriptExecutor?
        // - Provides security validation (whitelist, path validation)
        // - Handles error cases gracefully
        // - Logs execution for audit trail
        // - Sanitizes arguments (prevents command injection)
        // 
        // Script Execution:
        // - Script is executed with sudo (required for iptables)
        // - MAC address is passed as argument
        // - Script validates and normalizes MAC address internally
        // - Script adds iptables rules to block the device
        $result = $this->scriptExecutor->execute('block_device.sh', [$macAddress]);

        // Step 4: Check if script execution was successful
        // Script returns exit code 0 on success, non-zero on error
        // We check the 'success' flag from ScriptExecutor result
        // 
        // Why Check Success?
        // - Script might fail due to permission issues, invalid MAC, or iptables errors
        // - We need to know if blocking actually happened at network level
        // - Database status update happens regardless (partial success)
        $scriptSuccess = $result['success'];

        // Step 5: Update device status in database to 'blocked'
        // This tracks the blocking state in our application
        // 
        // Why Update Database Even If Script Fails?
        // - Records the intent to block (useful for audit trail)
        // - UI can show device as "blocked" even if network blocking failed
        // - Allows retry of network blocking later
        // - Partial success is better than no record at all
        // 
        // Note: Database status and network status might be out of sync
        // if script execution fails. This is acceptable - we log the failure
        // and can retry or investigate later.
        $device->update(['status' => 'blocked']);

        // Step 6: Log the blocking operation for debugging and audit trail
        // This helps us track when devices were blocked and why
        // 
        // What We Log:
        // - Device information (ID, name, MAC address)
        // - Script execution result (success/failure)
        // - Script output (for debugging)
        // - Return code (for error diagnosis)
        if ($scriptSuccess) {
            // Script executed successfully - device is blocked at network level
            Log::info('Device blocked at network level', [
                'device_id' => $device->id,
                'device_name' => $device->name,
                'mac_address' => $macAddress,
                'status' => 'blocked',
                'script_output' => $result['output'],
                'return_code' => $result['return_code'],
            ]);
        } else {
            // Script execution failed - log error but database status is still updated
            // This is a partial success: database says blocked, but network might not be
            // 
            // Why Log as Warning, Not Error?
            // - Database status was updated successfully (partial success)
            // - System continues to function (doesn't crash)
            // - Can retry network blocking later
            // - Error level would be too severe for partial failure
            Log::warning('Device blocking: database updated but network blocking may have failed', [
                'device_id' => $device->id,
                'device_name' => $device->name,
                'mac_address' => $macAddress,
                'status' => 'blocked',
                'script_error' => $result['error'],
                'script_output' => $result['output'],
                'return_code' => $result['return_code'],
                'command' => $result['command'],
            ]);
        }

        // Step 7: Return success status
        // We return true if script executed successfully
        // We return false if script failed (even though database was updated)
        // 
        // Why Return Script Success Status?
        // - Callers can check if network blocking actually happened
        // - Allows callers to handle partial failures appropriately
        // - Useful for retry logic or error reporting
        // 
        // Note: Database status is always updated (partial success)
        // Return value indicates network-level blocking success
        return $scriptSuccess;
    }

    /**
     * Unblock a device at the network level by removing firewall rules.
     * 
     * This method removes the iptables/nftables rule that was blocking a device,
     * allowing the device to access the internet again at the network level.
     * 
     * What Happens:
     * 1. Gets device's MAC address (unique identifier)
     * 2. Executes unblock_device.sh script via ScriptExecutor
     * 3. Script removes iptables DROP rules for the MAC address
     * 4. Device can now access internet again (traffic allowed)
     * 5. Updates database status to 'active'
     * 6. Logs the operation for debugging and audit trail
     * 
     * Current Implementation:
     * - Executes unblock_device.sh script via ScriptExecutor
     * - Script validates MAC address format and normalizes it
     * - Script removes iptables DROP rules from both INPUT and FORWARD chains:
     *   - `iptables -D INPUT -i wlan0 -m mac --mac-source AA:BB:CC:DD:EE:FF -j DROP`
     *   - `iptables -D FORWARD -i wlan0 -m mac --mac-source AA:BB:CC:DD:EE:FF -j DROP`
     * - Updates database status to 'active'
     * - Device can access internet again
     * 
     * When Is This Called?
     * - After child completes quiz/video and earns time
     * - When parent manually unblocks a device
     * - When device time is granted via TimeGrantingService
     * 
     * Error Handling:
     * - If iptables command fails, logs error but doesn't crash
     * - Database status is still updated (partial success)
     * - System continues to function even if network unblocking fails
     * 
     * @param Device $device The device to unblock at network level
     * @return bool True if unblocking was successful (or logged), false on error
     * 
     * Usage Example:
     * ```php
     * $device = Device::find(1);
     * $service = new NetworkService();
     * 
     * // Unblock device after time is granted
     * if ($service->unblockDevice($device)) {
     *     echo "Device unblocked successfully";
     *     // Device can access internet again
     * } else {
     *     echo "Unblocking failed - check logs";
     * }
     * ```
     */
    public function unblockDevice(Device $device): bool
    {
        // Step 1: Get device's MAC address (unique identifier)
        // MAC address is like a fingerprint - each device has a unique one
        // Format: XX:XX:XX:XX:XX:XX (6 pairs of hexadecimal characters)
        // Example: "AA:BB:CC:DD:EE:FF"
        // 
        // Why MAC Address?
        // - MAC address identifies the device at network level
        // - iptables rules are based on MAC address
        // - We need MAC address to remove the blocking rules
        $macAddress = $device->mac_address;

        // Step 2: Validate MAC address exists (safety check)
        // If device doesn't have MAC address, we can't unblock it at network level
        // This prevents errors when trying to execute the unblocking script
        // 
        // Why This Check?
        // - Devices must have MAC address to be unblocked
        // - Missing MAC address indicates data integrity issue
        // - Better to fail early with clear error than execute script with empty MAC
        if (empty($macAddress)) {
            // MAC address is missing - log error and return failure
            // This is a data integrity issue that should be investigated
            Log::error('Cannot unblock device: MAC address is missing', [
                'device_id' => $device->id,
                'device_name' => $device->name,
            ]);

            return false; // Can't unblock without MAC address
        }

        // Step 3: Execute unblock_device.sh script via ScriptExecutor
        // This is where the actual network-level unblocking happens
        // 
        // What unblock_device.sh Does:
        // - Validates MAC address format
        // - Normalizes MAC address to standard format (colons, uppercase)
        // - Removes iptables DROP rules from INPUT and FORWARD chains
        // - Removes all blocking rules for this MAC address (idempotent)
        // 
        // Why Use ScriptExecutor?
        // - Provides security validation (whitelist, path validation)
        // - Handles error cases gracefully
        // - Logs execution for audit trail
        // - Sanitizes arguments (prevents command injection)
        // 
        // Script Execution:
        // - Script is executed with sudo (required for iptables)
        // - MAC address is passed as argument
        // - Script validates and normalizes MAC address internally
        // - Script removes iptables rules that block the device
        $result = $this->scriptExecutor->execute('unblock_device.sh', [$macAddress]);

        // Step 4: Check if script execution was successful
        // Script returns exit code 0 on success, non-zero on error
        // We check the 'success' flag from ScriptExecutor result
        // 
        // Why Check Success?
        // - Script might fail due to permission issues, invalid MAC, or iptables errors
        // - We need to know if unblocking actually happened at network level
        // - Database status update happens regardless (partial success)
        $scriptSuccess = $result['success'];

        // Step 5: Update device status in database to 'active'
        // This tracks the unblocking state in our application
        // 
        // Why Update Database Even If Script Fails?
        // - Records the intent to unblock (useful for audit trail)
        // - UI can show device as "active" even if network unblocking failed
        // - Allows retry of network unblocking later
        // - Partial success is better than no record at all
        // 
        // Note: Database status and network status might be out of sync
        // if script execution fails. This is acceptable - we log the failure
        // and can retry or investigate later.
        $device->update(['status' => 'active']);

        // Step 6: Log the unblocking operation for debugging and audit trail
        // This helps us track when devices were unblocked and why
        // 
        // What We Log:
        // - Device information (ID, name, MAC address)
        // - Remaining time (how much time device has left)
        // - Script execution result (success/failure)
        // - Script output (for debugging)
        // - Return code (for error diagnosis)
        if ($scriptSuccess) {
            // Script executed successfully - device is unblocked at network level
            Log::info('Device unblocked at network level', [
                'device_id' => $device->id,
                'device_name' => $device->name,
                'mac_address' => $macAddress,
                'status' => 'active',
                'remaining_time_minutes' => $device->remaining_time_minutes,
                'script_output' => $result['output'],
                'return_code' => $result['return_code'],
            ]);
        } else {
            // Script execution failed - log warning but database status is still updated
            // This is a partial success: database says active, but network might still be blocked
            // 
            // Why Log as Warning, Not Error?
            // - Database status was updated successfully (partial success)
            // - System continues to function (doesn't crash)
            // - Can retry network unblocking later
            // - Error level would be too severe for partial failure
            Log::warning('Device unblocking: database updated but network unblocking may have failed', [
                'device_id' => $device->id,
                'device_name' => $device->name,
                'mac_address' => $macAddress,
                'status' => 'active',
                'remaining_time_minutes' => $device->remaining_time_minutes,
                'script_error' => $result['error'],
                'script_output' => $result['output'],
                'return_code' => $result['return_code'],
                'command' => $result['command'],
            ]);
        }

        // Step 7: Return success status
        // We return true if script executed successfully
        // We return false if script failed (even though database was updated)
        // 
        // Why Return Script Success Status?
        // - Callers can check if network unblocking actually happened
        // - Allows callers to handle partial failures appropriately
        // - Useful for retry logic or error reporting
        // 
        // Note: Database status is always updated (partial success)
        // Return value indicates network-level unblocking success
        return $scriptSuccess;
    }

    /**
     * Check if a device is currently blocked at the network level.
     * 
     * This method checks the firewall (iptables/nftables) rules to see if
     * a device's MAC address is currently blocked at the network level.
     * 
     * What This Checks:
     * - Queries iptables rules directly to find blocking rules for the device's MAC address
     * - Checks both FORWARD and INPUT chains for DROP rules
     * - Returns true if blocking rule exists, false if no rule found
     * - This is the "real" check - database status might say "blocked"
     *   but this checks if it's actually blocked at network level
     * 
     * Current Implementation:
     * - Queries iptables FORWARD chain: `iptables -L FORWARD -n -v | grep -i MAC_ADDRESS`
     * - If not found in FORWARD, also checks INPUT chain: `iptables -L INPUT -n -v | grep -i MAC_ADDRESS`
     * - Normalizes MAC address to uppercase with colons for consistent searching
     * - Returns true if MAC address found in any blocking rule, false otherwise
     * - This gives the actual network-level blocking status, not just database status
     * 
     * Why Check Network Level?
     * - Database status might be out of sync with actual network state
     * - This gives us the "real" status from the firewall
     * - Useful for debugging and verification
     * 
     * @param Device $device The device to check
     * @return bool True if device is blocked at network level, false otherwise
     * 
     * Usage Example:
     * ```php
     * $device = Device::find(1);
     * $service = new NetworkService();
     * 
     * // Check if device is actually blocked at network level
     * if ($service->isDeviceBlocked($device)) {
     *     echo "Device is blocked at network level (iptables rule exists)";
     * } else {
     *     echo "Device is not blocked at network level";
     * }
     * ```
     */
    public function isDeviceBlocked(Device $device): bool
    {
        // Step 1: Get device's MAC address (unique identifier)
        // MAC address is like a fingerprint - each device has a unique one
        // Format: XX:XX:XX:XX:XX:XX (6 pairs of hexadecimal characters)
        // Example: "AA:BB:CC:DD:EE:FF"
        // 
        // Why MAC Address?
        // - MAC address is used in iptables rules to identify devices
        // - We need MAC address to search for blocking rules
        // - More reliable than IP address (IPs can change via DHCP)
        $macAddress = $device->mac_address;

        // Step 2: Validate MAC address exists (safety check)
        // If device doesn't have MAC address, we can't check blocking status
        // This prevents errors when trying to check iptables rules
        // 
        // Why This Check?
        // - Devices must have MAC address to check blocking status
        // - Missing MAC address indicates data integrity issue
        // - Better to fail early with clear error than check with empty MAC
        if (empty($macAddress)) {
            // MAC address is missing - log warning and return false
            // We return false (not blocked) because we can't verify blocking
            // This is a conservative approach - assume not blocked if we can't check
            Log::warning('Cannot check blocking status: MAC address is missing', [
                'device_id' => $device->id,
                'device_name' => $device->name,
            ]);

            return false; // Can't check without MAC address - assume not blocked
        }

        // Step 3: Normalize MAC address to standard format
        // iptables rules may have MAC addresses in different formats
        // We normalize to uppercase with colons for consistent searching
        // 
        // Why Normalize?
        // - MAC addresses can be in different formats (AA:BB:CC or aa:bb:cc)
        // - iptables may store rules with different formatting
        // - Normalizing ensures we find the rule even if format differs
        // 
        // Normalization:
        // - Convert to uppercase (AA:BB:CC instead of aa:bb:cc)
        // - Ensure colons are used (not dashes)
        // - This matches the format used by block_device.sh script
        $normalizedMac = strtoupper(str_replace('-', ':', $macAddress));

        // Step 4: Check iptables FORWARD chain for blocking rules
        // The FORWARD chain is where traffic through the Pi is blocked
        // This is the main chain used for blocking devices from accessing internet
        // 
        // Why FORWARD Chain?
        // - FORWARD chain handles traffic passing through the Pi (to internet)
        // - INPUT chain handles traffic to the Pi itself
        // - For parental control, we primarily care about FORWARD chain
        // - block_device.sh adds rules to both chains, but FORWARD is primary
        // 
        // Command Explanation:
        // - sudo iptables: Execute iptables with root privileges
        // - -L FORWARD: List all rules in FORWARD chain
        // - -n: Numeric output (don't resolve hostnames - faster)
        // - -v: Verbose (show more details including MAC addresses)
        // - | grep: Search for MAC address in the output
        // - -i: Case-insensitive search (handles different MAC formats)
        // 
        // What This Finds:
        // - DROP rules that block the MAC address
        // - Any rule containing the MAC address in FORWARD chain
        // - Returns true if MAC address found, false if not found
        $command = "sudo iptables -L FORWARD -n -v | grep -i " . escapeshellarg($normalizedMac);

        // Execute command and capture output
        // We use exec() to execute the command and capture output
        // 
        // Why exec() Instead of ScriptExecutor?
        // - This is a read-only operation (no security risk)
        // - Simpler than creating a script for a simple grep
        // - Faster execution (no script overhead)
        // - Direct iptables query is safe (no modifications)
        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        // Step 5: Check if MAC address was found in iptables rules
        // If grep found the MAC address, output array will contain lines
        // If grep didn't find it, output array will be empty
        // 
        // Why Check Output Array?
        // - grep returns exit code 0 if match found, 1 if not found
        // - But we also check output array for safety
        // - If output has lines, MAC address was found in rules
        $isBlocked = !empty($output);

        // Step 6: Also check INPUT chain for completeness
        // block_device.sh adds rules to both INPUT and FORWARD chains
        // We check both to be thorough, but FORWARD is the primary check
        // 
        // Why Check INPUT Too?
        // - Some blocking rules might be in INPUT chain
        // - Provides complete picture of blocking status
        // - Matches what block_device.sh actually does
        if (!$isBlocked) {
            // Check INPUT chain if not found in FORWARD chain
            $inputCommand = "sudo iptables -L INPUT -n -v | grep -i " . escapeshellarg($normalizedMac);
            $inputOutput = [];
            exec($inputCommand, $inputOutput, $returnCode);

            // If found in INPUT chain, device is blocked
            $isBlocked = !empty($inputOutput);
        }

        // Step 7: Log the check for debugging (optional - can be removed if too verbose)
        // This helps us track when blocking status is checked
        // Only log if device is blocked (to reduce log noise)
        // 
        // Why Log?
        // - Helps debug blocking issues
        // - Provides audit trail of status checks
        // - Can identify when database and network status are out of sync
        if ($isBlocked) {
            Log::debug('Device blocking status checked (network level)', [
                'device_id' => $device->id,
                'device_name' => $device->name,
                'mac_address' => $macAddress,
                'normalized_mac' => $normalizedMac,
                'is_blocked' => true,
                'database_status' => $device->status,
                'note' => 'Device is blocked at network level (iptables rule found)',
            ]);
        }

        // Step 8: Return blocking status
        // Returns true if blocking rule found in iptables, false otherwise
        // 
        // Note: This is the "real" network-level status
        // Database status might say "blocked" but network might not be blocked
        // (if script execution failed previously)
        // This method gives us the actual network-level status
        return $isBlocked;
    }

    /**
     * Whitelist a device to bypass all restrictions.
     * 
     * This method adds a device to the whitelist, which means the device
     * can access the internet without any restrictions, regardless of time
     * limits or blocking rules.
     * 
     * What Happens:
     * 1. Gets device's MAC address (unique identifier)
     * 2. Executes whitelist_device.sh script to add whitelist rules
     * 3. Device can now access internet without restrictions
     * 4. Logs the operation for debugging and audit trail
     * 
     * What Whitelisting Does:
     * - Adds high-priority ACCEPT rules in iptables for the device's MAC address
     * - These rules are placed before blocking rules, so they take precedence
     * - Device bypasses all blocking, time limits, and restrictions
     * - Device can access internet even if time has expired
     * 
     * When Is This Called?
     * - When parent manually whitelists a device (admin feature)
     * - For trusted devices that should always have access
     * - For testing or troubleshooting purposes
     * 
     * Error Handling:
     * - If script execution fails, logs error but doesn't crash
     * - Returns false to indicate failure
     * - System continues to function even if whitelisting fails
     * 
     * @param Device $device The device to whitelist
     * @return bool True if whitelisting was successful, false on error
     * 
     * Usage Example:
     * ```php
     * $device = Device::find(1);
     * $service = new NetworkService();
     * 
     * // Whitelist device to bypass all restrictions
     * if ($service->whitelistDevice($device)) {
     *     echo "Device whitelisted successfully";
     *     // Device can now access internet without restrictions
     * } else {
     *     echo "Whitelisting failed - check logs";
     * }
     * ```
     */
    public function whitelistDevice(Device $device): bool
    {
        // Step 1: Get device's MAC address (unique identifier)
        // MAC address is like a fingerprint - each device has a unique one
        // Format: XX:XX:XX:XX:XX:XX (6 pairs of hexadecimal characters)
        // Example: "AA:BB:CC:DD:EE:FF"
        // 
        // Why MAC Address?
        // - MAC address identifies the device at network level
        // - iptables whitelist rules are based on MAC address
        // - We need MAC address to add whitelist rules
        $macAddress = $device->mac_address;

        // Step 2: Validate MAC address exists (safety check)
        // If device doesn't have MAC address, we can't whitelist it
        // This prevents errors when trying to execute the whitelisting script
        // 
        // Why This Check?
        // - Devices must have MAC address to be whitelisted
        // - Missing MAC address indicates data integrity issue
        // - Better to fail early with clear error than execute script with empty MAC
        if (empty($macAddress)) {
            // MAC address is missing - log error and return failure
            // This is a data integrity issue that should be investigated
            Log::error('Cannot whitelist device: MAC address is missing', [
                'device_id' => $device->id,
                'device_name' => $device->name,
            ]);

            return false; // Can't whitelist without MAC address
        }

        // Step 3: Execute whitelist_device.sh script via ScriptExecutor
        // This is where the actual network-level whitelisting happens
        // 
        // What whitelist_device.sh Does:
        // - Validates MAC address format
        // - Normalizes MAC address to standard format (colons, uppercase)
        // - Removes any existing blocking rules for this MAC address
        // - Adds high-priority ACCEPT rules to INPUT and FORWARD chains
        // - Ensures device can access internet without restrictions
        // 
        // Why Use ScriptExecutor?
        // - Provides security validation (whitelist, path validation)
        // - Handles error cases gracefully
        // - Logs execution for audit trail
        // - Sanitizes arguments (prevents command injection)
        // 
        // Script Execution:
        // - Script is executed with sudo (required for iptables)
        // - MAC address is passed as argument
        // - Script validates and normalizes MAC address internally
        // - Script adds iptables ACCEPT rules with high priority
        $result = $this->scriptExecutor->execute('whitelist_device.sh', [$macAddress]);

        // Step 4: Check if script execution was successful
        // Script returns exit code 0 on success, non-zero on error
        // We check the 'success' flag from ScriptExecutor result
        // 
        // Why Check Success?
        // - Script might fail due to permission issues, invalid MAC, or iptables errors
        // - We need to know if whitelisting actually happened at network level
        $scriptSuccess = $result['success'];

        // Step 5: Log the whitelisting operation for debugging and audit trail
        // This helps us track when devices were whitelisted and why
        // 
        // What We Log:
        // - Device information (ID, name, MAC address)
        // - Script execution result (success/failure)
        // - Script output (for debugging)
        // - Return code (for error diagnosis)
        if ($scriptSuccess) {
            // Script executed successfully - device is whitelisted at network level
            Log::info('Device whitelisted at network level', [
                'device_id' => $device->id,
                'device_name' => $device->name,
                'mac_address' => $macAddress,
                'script_output' => $result['output'],
                'return_code' => $result['return_code'],
            ]);
        } else {
            // Script execution failed - log error
            // Whitelisting failed - device is not whitelisted
            Log::error('Device whitelisting failed', [
                'device_id' => $device->id,
                'device_name' => $device->name,
                'mac_address' => $macAddress,
                'script_error' => $result['error'],
                'script_output' => $result['output'],
                'return_code' => $result['return_code'],
                'command' => $result['command'],
            ]);
        }

        // Step 6: Return success status
        // We return true if script executed successfully
        // We return false if script failed
        // 
        // Why Return Script Success Status?
        // - Callers can check if whitelisting actually happened
        // - Allows callers to handle failures appropriately
        // - Useful for error reporting or retry logic
        return $scriptSuccess;
    }

    /**
     * Get list of devices currently connected to the access point.
     * 
     * This method queries the network to find all devices currently connected
     * to the Raspberry Pi's WiFi access point. It returns information about
     * each connected device including MAC address, IP address, and hostname.
     * 
     * What This Does:
     * 1. Executes get_connected_devices.sh script
     * 2. Script queries ARP table and DHCP leases to find connected devices
     * 3. Parses JSON output from script
     * 4. Returns array of connected devices with their information
     * 
     * How It Works:
     * - Script queries ARP (Address Resolution Protocol) table for wlan0 interface
     * - ARP table maps IP addresses to MAC addresses for devices on the network
     * - Script also checks DHCP leases for hostname information
     * - Output is formatted as JSON for easy parsing
     * 
     * Return Format:
     * - Returns array of device information arrays
     * - Each device has: mac_address, ip_address, hostname
     * - Returns empty array if no devices found or on error
     * 
     * When Is This Called?
     * - When displaying list of connected devices in admin panel
     * - For device discovery and management
     * - For monitoring and reporting
     * 
     * Error Handling:
     * - If script execution fails, returns empty array
     * - If JSON parsing fails, returns empty array
     * - Errors are logged for debugging
     * - System continues to function even if query fails
     * 
     * @return array<int, array{mac_address: string, ip_address: string, hostname: string}>
     *         Array of connected devices, each with mac_address, ip_address, and hostname
     * 
     * Usage Example:
     * ```php
     * $service = new NetworkService();
     * 
     * // Get list of connected devices
     * $devices = $service->getConnectedDevices();
     * 
     * foreach ($devices as $device) {
     *     echo "Device: {$device['hostname']} ({$device['mac_address']}) at {$device['ip_address']}\n";
     * }
     * ```
     */
    public function getConnectedDevices(): array
    {
        // Step 1: Execute get_connected_devices.sh script via ScriptExecutor
        // This script queries the network to find connected devices
        // 
        // What get_connected_devices.sh Does:
        // - Queries ARP table for wlan0 interface (access point interface)
        // - Maps IP addresses to MAC addresses
        // - Checks DHCP leases for hostname information
        // - Outputs JSON with device information
        // 
        // Why Use ScriptExecutor?
        // - Provides security validation (whitelist, path validation)
        // - Handles error cases gracefully
        // - Logs execution for audit trail
        // - Sanitizes arguments (no arguments needed for this script)
        // 
        // Script Execution:
        // - Script is executed with sudo (required for ARP table access)
        // - No arguments needed (queries all connected devices)
        // - Script outputs JSON with device information
        $result = $this->scriptExecutor->execute('get_connected_devices.sh', []);

        // Step 2: Check if script execution was successful
        // Script returns exit code 0 on success, non-zero on error
        // We check the 'success' flag from ScriptExecutor result
        // 
        // Why Check Success?
        // - Script might fail due to permission issues or network errors
        // - We need to know if query was successful before parsing JSON
        if (!$result['success']) {
            // Script execution failed - log error and return empty array
            // This is not a critical error - system continues to function
            Log::warning('Failed to get connected devices', [
                'script_error' => $result['error'],
                'script_output' => $result['output'],
                'return_code' => $result['return_code'],
            ]);

            return []; // Return empty array on error
        }

        // Step 3: Parse JSON output from script
        // Script outputs JSON array of devices
        // We need to decode JSON string to PHP array
        // 
        // JSON Format:
        // [
        //   {
        //     "mac_address": "AA:BB:CC:DD:EE:FF",
        //     "ip_address": "192.168.4.5",
        //     "hostname": "device-hostname"
        //   },
        //   ...
        // ]
        // 
        // Why json_decode()?
        // - Converts JSON string to PHP array
        // - Handles JSON syntax validation
        // - Returns null on invalid JSON
        $output = trim($result['output']);

        // Check if output is empty (no devices found)
        // Empty output means no devices are connected
        if (empty($output)) {
            // No devices found - return empty array
            // This is normal if no devices are connected
            Log::debug('No connected devices found', [
                'script_output' => $output,
            ]);

            return []; // Return empty array if no devices
        }

        // Decode JSON output to PHP array
        // json_decode() converts JSON string to PHP array
        // We use associative arrays (true parameter)
        // 
        // Error Handling:
        // - json_decode() returns null on invalid JSON
        // - We check for null and return empty array on error
        $devices = json_decode($output, true);

        // Step 4: Validate JSON parsing result
        // json_decode() returns null on invalid JSON
        // We need to check if parsing was successful
        // 
        // Why Validate?
        // - Invalid JSON would cause errors in calling code
        // - Better to return empty array than invalid data
        // - Logs error for debugging
        if ($devices === null || !is_array($devices)) {
            // JSON parsing failed - log error and return empty array
            // This could happen if script output is malformed
            Log::error('Failed to parse connected devices JSON', [
                'script_output' => $output,
                'json_error' => json_last_error_msg(),
            ]);

            return []; // Return empty array on parsing error
        }

        // Step 5: Validate device data structure
        // Each device should have mac_address, ip_address, and hostname
        // We filter out any invalid entries
        // 
        // Why Validate?
        // - Ensures data integrity
        // - Prevents errors in calling code
        // - Handles malformed data gracefully
        $validDevices = [];
        foreach ($devices as $device) {
            // Check if device has required fields
            // Each device must have mac_address, ip_address, and hostname
            if (isset($device['mac_address']) && isset($device['ip_address'])) {
                // Device has required fields - add to valid devices
                // hostname is optional (may be empty if not available)
                $validDevices[] = [
                    'mac_address' => $device['mac_address'],
                    'ip_address' => $device['ip_address'],
                    'hostname' => $device['hostname'] ?? '', // Default to empty string if not set
                ];
            }
        }

        // Step 6: Log successful query (optional - can be removed if too verbose)
        // This helps with debugging and monitoring
        // Only log if devices were found (to reduce log noise)
        if (!empty($validDevices)) {
            Log::debug('Retrieved connected devices', [
                'device_count' => count($validDevices),
            ]);
        }

        // Step 7: Return array of connected devices
        // Returns array of device information arrays
        // Each device has: mac_address, ip_address, hostname
        // Returns empty array if no devices found or on error
        return $validDevices;
    }

    /**
     * Get network traffic statistics for devices.
     * 
     * This method retrieves network traffic statistics (bytes sent/received)
     * for devices connected to the access point. It can return statistics
     * for all devices or for a specific device by MAC address.
     * 
     * What This Does:
     * 1. Executes monitor_traffic.sh script
     * 2. Script queries iptables FORWARD chain for traffic statistics
     * 3. Parses JSON output from script
     * 4. Returns array of traffic statistics for devices
     * 
     * How It Works:
     * - Script queries iptables FORWARD chain statistics
     * - iptables tracks bytes sent/received for each rule
     * - Script aggregates statistics by MAC address
     * - Output is formatted as JSON for easy parsing
     * 
     * Return Format:
     * - Returns array of traffic statistics arrays
     * - Each entry has: mac_address, bytes_sent, bytes_received
     * - Returns empty array if no statistics found or on error
     * 
     * When Is This Called?
     * - When displaying traffic statistics in admin panel
     * - For monitoring device usage
     * - For reporting and analytics
     * 
     * Error Handling:
     * - If script execution fails, returns empty array
     * - If JSON parsing fails, returns empty array
     * - Errors are logged for debugging
     * - System continues to function even if query fails
     * 
     * @param string|null $macAddress Optional MAC address to get statistics for specific device.
     *                                 If null, returns statistics for all devices.
     * @return array<int, array{mac_address: string, bytes_sent: int, bytes_received: int}>
     *         Array of traffic statistics, each with mac_address, bytes_sent, and bytes_received
     * 
     * Usage Example:
     * ```php
     * $service = new NetworkService();
     * 
     * // Get traffic statistics for all devices
     * $allStats = $service->getTrafficStats();
     * 
     * // Get traffic statistics for specific device
     * $deviceStats = $service->getTrafficStats('AA:BB:CC:DD:EE:FF');
     * 
     * foreach ($deviceStats as $stat) {
     *     echo "Device {$stat['mac_address']}: {$stat['bytes_sent']} bytes sent, {$stat['bytes_received']} bytes received\n";
     * }
     * ```
     */
    public function getTrafficStats(?string $macAddress = null): array
    {
        // Step 1: Prepare script arguments
        // monitor_traffic.sh accepts optional MAC address argument
        // If MAC address provided, returns statistics for that device only
        // If not provided, returns statistics for all devices
        // 
        // Why Optional MAC Address?
        // - Allows querying statistics for specific device
        // - More efficient than getting all stats and filtering
        // - Useful for device-specific monitoring
        $args = [];
        if ($macAddress !== null) {
            // MAC address provided - add to arguments
            // Script will filter statistics for this device only
            $args[] = $macAddress;
        }

        // Step 2: Execute monitor_traffic.sh script via ScriptExecutor
        // This script queries iptables for traffic statistics
        // 
        // What monitor_traffic.sh Does:
        // - Queries iptables FORWARD chain statistics
        // - Aggregates bytes sent/received by MAC address
        // - Filters by MAC address if provided as argument
        // - Outputs JSON with traffic statistics
        // 
        // Why Use ScriptExecutor?
        // - Provides security validation (whitelist, path validation)
        // - Handles error cases gracefully
        // - Logs execution for audit trail
        // - Sanitizes arguments (MAC address validation)
        // 
        // Script Execution:
        // - Script is executed with sudo (required for iptables access)
        // - MAC address is passed as argument if provided
        // - Script outputs JSON with traffic statistics
        $result = $this->scriptExecutor->execute('monitor_traffic.sh', $args);

        // Step 3: Check if script execution was successful
        // Script returns exit code 0 on success, non-zero on error
        // We check the 'success' flag from ScriptExecutor result
        // 
        // Why Check Success?
        // - Script might fail due to permission issues or iptables errors
        // - We need to know if query was successful before parsing JSON
        if (!$result['success']) {
            // Script execution failed - log error and return empty array
            // This is not a critical error - system continues to function
            Log::warning('Failed to get traffic statistics', [
                'mac_address' => $macAddress,
                'script_error' => $result['error'],
                'script_output' => $result['output'],
                'return_code' => $result['return_code'],
            ]);

            return []; // Return empty array on error
        }

        // Step 4: Parse JSON output from script
        // Script outputs JSON array of traffic statistics
        // We need to decode JSON string to PHP array
        // 
        // JSON Format:
        // [
        //   {
        //     "mac_address": "AA:BB:CC:DD:EE:FF",
        //     "bytes_sent": 1234567,
        //     "bytes_received": 9876543
        //   },
        //   ...
        // ]
        // 
        // Why json_decode()?
        // - Converts JSON string to PHP array
        // - Handles JSON syntax validation
        // - Returns null on invalid JSON
        $output = trim($result['output']);

        // Check if output is empty (no statistics found)
        // Empty output means no traffic statistics available
        if (empty($output)) {
            // No statistics found - return empty array
            // This is normal if no traffic has occurred
            Log::debug('No traffic statistics found', [
                'mac_address' => $macAddress,
                'script_output' => $output,
            ]);

            return []; // Return empty array if no statistics
        }

        // Decode JSON output to PHP array
        // json_decode() converts JSON string to PHP array
        // We use associative arrays (true parameter)
        // 
        // Error Handling:
        // - json_decode() returns null on invalid JSON
        // - We check for null and return empty array on error
        $stats = json_decode($output, true);

        // Step 5: Validate JSON parsing result
        // json_decode() returns null on invalid JSON
        // We need to check if parsing was successful
        // 
        // Why Validate?
        // - Invalid JSON would cause errors in calling code
        // - Better to return empty array than invalid data
        // - Logs error for debugging
        if ($stats === null || !is_array($stats)) {
            // JSON parsing failed - log error and return empty array
            // This could happen if script output is malformed
            Log::error('Failed to parse traffic statistics JSON', [
                'mac_address' => $macAddress,
                'script_output' => $output,
                'json_error' => json_last_error_msg(),
            ]);

            return []; // Return empty array on parsing error
        }

        // Step 6: Validate statistics data structure
        // Each entry should have mac_address, bytes_sent, and bytes_received
        // We filter out any invalid entries and ensure proper types
        // 
        // Why Validate?
        // - Ensures data integrity
        // - Prevents errors in calling code
        // - Handles malformed data gracefully
        // - Ensures numeric values are integers
        $validStats = [];
        foreach ($stats as $stat) {
            // Check if entry has required fields
            // Each entry must have mac_address, bytes_sent, and bytes_received
            if (
                isset($stat['mac_address']) &&
                isset($stat['bytes_sent']) &&
                isset($stat['bytes_received'])
            ) {
                // Entry has required fields - add to valid statistics
                // Convert bytes to integers (may be strings from JSON)
                $validStats[] = [
                    'mac_address' => $stat['mac_address'],
                    'bytes_sent' => (int) $stat['bytes_sent'], // Ensure integer type
                    'bytes_received' => (int) $stat['bytes_received'], // Ensure integer type
                ];
            }
        }

        // Step 7: Log successful query (optional - can be removed if too verbose)
        // This helps with debugging and monitoring
        // Only log if statistics were found (to reduce log noise)
        if (!empty($validStats)) {
            Log::debug('Retrieved traffic statistics', [
                'mac_address' => $macAddress,
                'stat_count' => count($validStats),
            ]);
        }

        // Step 8: Return array of traffic statistics
        // Returns array of statistics arrays
        // Each entry has: mac_address, bytes_sent, bytes_received
        // Returns empty array if no statistics found or on error
        return $validStats;
    }
}
