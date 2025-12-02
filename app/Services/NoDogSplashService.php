<?php

namespace App\Services;

use App\Models\Device;
use Illuminate\Support\Facades\Log;

/**
 * NoDogSplash Service
 * 
 * This service handles captive portal redirects using NoDogSplash.
 * It configures NoDogSplash to redirect devices to the portal when their
 * time expires, and allows devices through after they complete quizzes/videos.
 * 
 * What is NoDogSplash?
 * - NoDogSplash is a captive portal solution for WiFi networks
 * - It intercepts HTTP requests and redirects them to a custom portal page
 * - Think of it like a "toll booth" on the internet highway - it stops
 *   devices and shows them a page before allowing them through
 * 
 * What is a Captive Portal Redirect?
 * - When a device tries to access any website, NoDogSplash intercepts the request
 * - Instead of showing the website, it redirects to our portal page
 * - The device sees our portal (quiz/video selection) instead of the internet
 * - This is how we "force" children to complete activities to earn internet time
 * 
 * How It Works:
 * - NoDogSplash reads configuration files to know which devices to redirect
 * - When device time expires, we configure NoDogSplash to redirect that device
 * - When device completes quiz/video, we remove the redirect configuration
 * - Device can then access internet normally (until time expires again)
 * 
 * Configuration Files:
 * - NoDogSplash uses config files (usually in /etc/nodogsplash/)
 * - We modify these files to add/remove device redirects via bash scripts
 * - Example: Add device MAC address to redirect list = device gets redirected
 * - Remove device MAC address from redirect list = device can access internet
 * 
 * Implementation:
 * - Uses ScriptExecutor to securely execute bash scripts for NoDogSplash operations
 * - Scripts handle config file modifications and service restarts
 * - Follows same security model as NetworkService (whitelisted scripts only)
 * 
 * Integration Points:
 * - Called by CheckTimeExpiration job when device time expires (to redirect)
 * - Called by TimeGrantingService when time is granted (to allow through)
 * - Works together with NetworkService for complete portal control
 * 
 * Usage Example:
 * ```php
 * $service = new NoDogSplashService($scriptExecutor);
 * $device = Device::find(1);
 * 
 * // Redirect device to portal when time expires
 * $service->redirectDeviceToPortal($device);
 * // All HTTP requests from this device now redirect to /portal?mac=XX:XX:XX:XX:XX:XX
 * 
 * // Allow device through after quiz/video completion
 * $service->allowDeviceThrough($device);
 * // Device can now access internet normally (redirect removed)
 * 
 * // Check if device is currently redirected
 * if ($service->isDeviceRedirected($device)) {
 *     echo "Device is being redirected to portal";
 * }
 * ```
 */
class NoDogSplashService
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
     * - Promotes loose coupling between NoDogSplashService and script execution
     * 
     * How Laravel Resolves This:
     * - Laravel's service container automatically resolves ScriptExecutor
     * - When NoDogSplashService is instantiated, Laravel creates ScriptExecutor
     * - No manual instantiation needed - Laravel handles it automatically
     * 
     * @var ScriptExecutor
     */
    protected ScriptExecutor $scriptExecutor;

    /**
     * Constructor - Initialize NoDogSplashService with ScriptExecutor.
     * 
     * This constructor uses Laravel's dependency injection to automatically
     * receive a ScriptExecutor instance. Laravel's service container will
     * automatically create and inject the ScriptExecutor when NoDogSplashService
     * is instantiated.
     * 
     * Why Dependency Injection?
     * - **Testability**: Can inject mock ScriptExecutor in unit tests
     * - **Flexibility**: Can swap ScriptExecutor implementation if needed
     * - **Loose Coupling**: NoDogSplashService doesn't create ScriptExecutor directly
     * - **Laravel Pattern**: Follows Laravel's dependency injection conventions
     * 
     * How It Works:
     * - When NoDogSplashService is created (e.g., via service container or constructor),
     *   Laravel automatically resolves ScriptExecutor from the service container
     * - If ScriptExecutor is not bound in container, Laravel creates a new instance
     * - The ScriptExecutor instance is stored as a property for use in methods
     * 
     * Usage:
     * - NoDogSplashService is typically instantiated by Laravel automatically
     * - Other services (CheckTimeExpiration job, TimeGrantingService) receive
     *   NoDogSplashService via dependency injection
     * - No manual instantiation needed - Laravel handles everything
     * 
     * @param ScriptExecutor $scriptExecutor The script executor service (injected by Laravel)
     */
    public function __construct(ScriptExecutor $scriptExecutor)
    {
        // Store ScriptExecutor instance for use in methods
        // This allows all methods in NoDogSplashService to execute scripts securely
        // ScriptExecutor handles validation, sanitization, and error handling
        $this->scriptExecutor = $scriptExecutor;
    }
    /**
     * Redirect a device to the portal page using NoDogSplash.
     * 
     * This method configures NoDogSplash to intercept all HTTP requests from
     * a device and redirect them to the portal page. This is how we "force"
     * children to see the portal when their time expires.
     * 
     * What Happens:
     * 1. Gets device's MAC address (unique identifier)
     * 2. Configures NoDogSplash to redirect this MAC address to portal
     * 3. All HTTP requests from device now redirect to /portal?mac=XX:XX:XX:XX:XX:XX
     * 4. Device sees portal page instead of requested websites
     * 5. Logs the operation for debugging and audit trail
     * 
     * How Redirect Works:
     * - Device tries to visit google.com
     * - NoDogSplash intercepts the request
     * - Instead of showing google.com, it redirects to /portal?mac=AA:BB:CC:DD:EE:FF
     * - Device sees our portal page (quiz/video selection)
     * - Device cannot access internet until redirect is removed
     * 
     * Current Implementation (Stub):
     * - Only logs the operation (so we can see what would happen)
     * - Returns success status
     * - Does NOT actually configure NoDogSplash yet
     * 
     * Future Implementation:
     * - Will modify NoDogSplash config file to add device MAC address
     * - Config file location: /etc/nodogsplash/nodogsplash.conf (or similar)
     * - Add redirect rule: Redirect MAC address AA:BB:CC:DD:EE:FF to /portal?mac=AA:BB:CC:DD:EE:FF
     * - Restart NoDogSplash service to apply changes
     * - Device will then be redirected to portal on next HTTP request
     * 
     * When Is This Called?
     * - When device time expires (via CheckTimeExpiration job)
     * - When parent manually blocks a device
     * - When device violates rules (accesses blocked website)
     * 
     * Error Handling:
     * - If config file modification fails, logs error but doesn't crash
     * - Operation is logged (partial success)
     * - System continues to function even if redirect fails
     * 
     * @param Device $device The device to redirect to portal
     * @return bool True if redirect was configured successfully (or logged), false on error
     * 
     * Usage Example:
     * ```php
     * $device = Device::find(1);
     * $service = new NoDogSplashService();
     * 
     * // Redirect device to portal when time expires
     * if ($service->redirectDeviceToPortal($device)) {
     *     echo "Device will be redirected to portal on next HTTP request";
     *     // Device tries to visit any website → sees portal instead
     * } else {
     *     echo "Redirect configuration failed - check logs";
     * }
     * ```
     */
    public function redirectDeviceToPortal(Device $device): bool
    {
        // Get device's MAC address (unique identifier)
        // MAC address is like a fingerprint - each device has a unique one
        // Example: "AA:BB:CC:DD:EE:FF"
        $macAddress = $device->mac_address;

        // Validate MAC address exists (safety check)
        // If device doesn't have MAC address, we can't redirect it
        if (empty($macAddress)) {
            Log::error('Cannot redirect device: MAC address is missing', [
                'device_id' => $device->id,
                'device_name' => $device->name,
            ]);

            return false; // Can't redirect without MAC address
        }

        // Build the portal URL with MAC address as query parameter
        // This is the URL that NoDogSplash will redirect to
        // Example: http://192.168.4.1/portal?mac=AA:BB:CC:DD:EE:FF
        // The MAC address in the URL tells our portal which device is accessing it
        // route() generates the full URL using Laravel's routing system
        $portalUrl = route('portal.landing', ['mac' => $macAddress]);

        // Execute the redirect_device_portal.sh script via ScriptExecutor
        // This script:
        // 1. Validates and normalizes the MAC address
        // 2. Adds device to NoDogSplash blocklist/redirect list in config file
        // 3. Restarts NoDogSplash service to apply changes
        // 4. Returns exit code 0 on success, non-zero on error
        //
        // Script arguments:
        // - First argument: MAC address (e.g., "AA:BB:CC:DD:EE:FF")
        // - Second argument: Portal URL (e.g., "http://192.168.4.1/portal?mac=AA:BB:CC:DD:EE:FF")
        $result = $this->scriptExecutor->execute('redirect_device_portal.sh', [
            $macAddress,
            $portalUrl,
        ]);

        // Check if script execution was successful
        // Script returns exit code 0 on success, non-zero on error
        if ($result['success']) {
            // Script executed successfully - device redirect is configured
            // Log the successful operation for debugging and audit trail
            Log::info('Device redirected to portal successfully', [
                'device_id' => $device->id,
                'device_name' => $device->name,
                'mac_address' => $macAddress,
                'portal_url' => $portalUrl,
                'script_output' => $result['output'],
            ]);

            // Return true to indicate redirect was configured successfully
            return true;
        } else {
            // Script execution failed - log error but don't crash
            // This allows the system to continue functioning even if redirect fails
            Log::error('Failed to redirect device to portal', [
                'device_id' => $device->id,
                'device_name' => $device->name,
                'mac_address' => $macAddress,
                'portal_url' => $portalUrl,
                'script_error' => $result['error'],
                'script_output' => $result['output'],
                'return_code' => $result['return_code'],
            ]);

            // Return false to indicate redirect configuration failed
            // Calling code can check return value and handle error appropriately
            return false;
        }
    }

    /**
     * Allow a device through by removing the portal redirect.
     * 
     * This method removes the NoDogSplash redirect configuration for a device,
     * allowing the device to access the internet normally again. This is called
     * after a child completes a quiz or video and earns additional time.
     * 
     * What Happens:
     * 1. Gets device's MAC address (unique identifier)
     * 2. Removes redirect configuration from NoDogSplash config file
     * 3. Restarts NoDogSplash service to apply changes
     * 4. Device can now access internet normally (redirect removed)
     * 5. Logs the operation for debugging and audit trail
     * 
     * How It Works:
     * - Device was previously redirected to portal (time expired)
     * - Child completes quiz/video and earns time
     * - We remove the redirect configuration
     * - Device's next HTTP request goes to the actual website (not portal)
     * - Device can browse internet normally again
     * 
     * Current Implementation (Stub):
     * - Only logs the operation (so we can see what would happen)
     * - Returns success status
     * - Does NOT actually modify NoDogSplash config yet
     * 
     * Future Implementation:
     * - Will read NoDogSplash config file
     * - Remove redirect rule for this device's MAC address
     * - Save config file
     * - Restart NoDogSplash service to apply changes
     * - Device will then be able to access internet normally
     * 
     * When Is This Called?
     * - After child completes quiz and earns time (via TimeGrantingService)
     * - After child completes video and earns time (via TimeGrantingService)
     * - When parent manually unblocks a device
     * - When device time is granted for any reason
     * 
     * Error Handling:
     * - If config file modification fails, logs error but doesn't crash
     * - Operation is logged (partial success)
     * - System continues to function even if redirect removal fails
     * 
     * @param Device $device The device to allow through (remove redirect)
     * @return bool True if redirect was removed successfully (or logged), false on error
     * 
     * Usage Example:
     * ```php
     * $device = Device::find(1);
     * $service = new NoDogSplashService();
     * 
     * // Allow device through after time is granted
     * if ($service->allowDeviceThrough($device)) {
     *     echo "Device can now access internet normally";
     *     // Device can browse websites again (redirect removed)
     * } else {
     *     echo "Redirect removal failed - check logs";
     * }
     * ```
     */
    public function allowDeviceThrough(Device $device): bool
    {
        // Get device's MAC address (unique identifier)
        // MAC address is like a fingerprint - each device has a unique one
        // Example: "AA:BB:CC:DD:EE:FF"
        $macAddress = $device->mac_address;

        // Validate MAC address exists (safety check)
        // If device doesn't have MAC address, we can't remove redirect
        if (empty($macAddress)) {
            Log::error('Cannot allow device through: MAC address is missing', [
                'device_id' => $device->id,
                'device_name' => $device->name,
            ]);

            return false; // Can't remove redirect without MAC address
        }

        // Execute the allow_device_through.sh script via ScriptExecutor
        // This script:
        // 1. Validates and normalizes the MAC address
        // 2. Removes device from NoDogSplash blocklist/redirect list in config file
        // 3. Restarts NoDogSplash service to apply changes
        // 4. Returns exit code 0 on success, non-zero on error
        //
        // Script arguments:
        // - First argument: MAC address (e.g., "AA:BB:CC:DD:EE:FF")
        $result = $this->scriptExecutor->execute('allow_device_through.sh', [
            $macAddress,
        ]);

        // Check if script execution was successful
        // Script returns exit code 0 on success, non-zero on error
        if ($result['success']) {
            // Script executed successfully - device redirect is removed
            // Log the successful operation for debugging and audit trail
            Log::info('Device allowed through successfully (redirect removed)', [
                'device_id' => $device->id,
                'device_name' => $device->name,
                'mac_address' => $macAddress,
                'remaining_time_minutes' => $device->remaining_time_minutes,
                'script_output' => $result['output'],
            ]);

            // Return true to indicate redirect removal was successful
            return true;
        } else {
            // Script execution failed - log error but don't crash
            // This allows the system to continue functioning even if redirect removal fails
            Log::error('Failed to allow device through (remove redirect)', [
                'device_id' => $device->id,
                'device_name' => $device->name,
                'mac_address' => $macAddress,
                'remaining_time_minutes' => $device->remaining_time_minutes,
                'script_error' => $result['error'],
                'script_output' => $result['output'],
                'return_code' => $result['return_code'],
            ]);

            // Return false to indicate redirect removal failed
            // Calling code can check return value and handle error appropriately
            return false;
        }
    }

    /**
     * Check if a device is currently being redirected to the portal.
     * 
     * This method checks the NoDogSplash configuration to see if a device's
     * MAC address is currently in the redirect list. This tells us if the
     * device will be redirected to the portal on its next HTTP request.
     * 
     * What This Checks:
     * - Looks for device's MAC address in NoDogSplash redirect configuration
     * - Returns true if redirect rule exists, false if no rule found
     * - This is the "real" check - database status might say "blocked"
     *   but this checks if device is actually being redirected
     * 
     * Current Implementation (Stub):
     * - Only checks database status (not actual NoDogSplash config)
     * - Returns true if device status is 'blocked' in database
     * - Does NOT actually check NoDogSplash config file yet
     * 
     * Future Implementation:
     * - Will read NoDogSplash config file: /etc/nodogsplash/nodogsplash.conf
     * - Search for redirect rule containing device's MAC address
     * - If rule found, device is redirected; if not found, device is not redirected
     * - Example: Search for "RedirectList AA:BB:CC:DD:EE:FF" in config file
     * 
     * Why Check NoDogSplash Config?
     * - Database status might be out of sync with actual NoDogSplash state
     * - This gives us the "real" status from NoDogSplash configuration
     * - Useful for debugging and verification
     * 
     * @param Device $device The device to check
     * @return bool True if device is being redirected to portal, false otherwise
     * 
     * Usage Example:
     * ```php
     * $device = Device::find(1);
     * $service = new NoDogSplashService();
     * 
     * // Check if device is actually being redirected
     * if ($service->isDeviceRedirected($device)) {
     *     echo "Device will be redirected to portal on next HTTP request";
     * } else {
     *     echo "Device can access internet normally";
     * }
     * ```
     */
    public function isDeviceRedirected(Device $device): bool
    {
        // Get device's MAC address (unique identifier)
        // MAC address is like a fingerprint - each device has a unique one
        // Example: "AA:BB:CC:DD:EE:FF"
        $macAddress = $device->mac_address;

        // Validate MAC address exists (safety check)
        // If device doesn't have MAC address, we can't check redirect status
        if (empty($macAddress)) {
            Log::warning('Cannot check redirect status: MAC address is missing', [
                'device_id' => $device->id,
                'device_name' => $device->name,
            ]);

            return false; // Can't check without MAC address
        }

        // Execute the check_device_redirected.sh script via ScriptExecutor
        // This script:
        // 1. Validates and normalizes the MAC address
        // 2. Checks NoDogSplash config file for device MAC address in blocklist
        // 3. Returns exit code 0 if device is redirected, 1 if not redirected
        //
        // Script arguments:
        // - First argument: MAC address (e.g., "AA:BB:CC:DD:EE:FF")
        $result = $this->scriptExecutor->execute('check_device_redirected.sh', [
            $macAddress,
        ]);

        // Determine redirect status based on script exit code
        // Script returns:
        // - Exit code 0 = Device is redirected (found in blocklist)
        // - Exit code 1 = Device is not redirected (not found in blocklist)
        //
        // Note: ScriptExecutor returns 'success' = true when exit code is 0
        // So if script returns exit code 0 (device is redirected), result['success'] = true
        // If script returns exit code 1 (device not redirected), result['success'] = false
        //
        // This seems backwards, but it's correct:
        // - Exit code 0 = "yes, device IS redirected" = success (check succeeded, positive result)
        // - Exit code 1 = "no, device is NOT redirected" = not success (check succeeded, negative result)
        $isRedirected = $result['success'];

        // Log the check for debugging (optional - can be removed if too verbose)
        // This helps us track when redirect status is checked and what the result was
        Log::debug('Device redirect status checked', [
            'device_id' => $device->id,
            'device_name' => $device->name,
            'mac_address' => $macAddress,
            'is_redirected' => $isRedirected,
            'script_output' => $result['output'],
            'return_code' => $result['return_code'],
        ]);

        // Return true if device is redirected (exit code 0), false if not redirected (exit code 1)
        // This gives us the actual redirect status from NoDogSplash configuration
        return $isRedirected;
    }
}
