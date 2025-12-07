<?php

namespace App\Http\Requests;

use App\Rules\ValidMacAddress;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Device Request
 * 
 * This form request handles validation for updating existing devices.
 * It validates all input data before the device is updated in the database.
 * 
 * What is a Form Request?
 * - A form request is Laravel's way of validating form data
 * - It runs automatically before the controller method is called
 * - If validation fails, user is redirected back with error messages
 * - If validation passes, controller method receives validated data
 * 
 * How It Works:
 * 1. User submits form to update device
 * 2. Laravel creates UpdateDeviceRequest instance
 * 3. rules() method is called to get validation rules
 * 4. Laravel validates all fields against rules
 * 5. If valid: controller method is called with validated data
 * 6. If invalid: user is redirected back with error messages
 * 
 * Differences from StoreDeviceRequest:
 * - MAC address unique check excludes current device (allows keeping same MAC)
 * - All other validation rules are the same
 * 
 * Validation Rules Explained:
 * - name: Required, must be a string, max 255 characters
 * - mac_address: Required, must be valid MAC format, must be unique (except current device)
 * - status: Required, must be one of: active, blocked, whitelisted
 * - remaining_time_minutes: Optional, must be integer between 0 and 9999
 * - total_time_allocated: Optional, must be integer between 0 and 9999
 * 
 * Usage Example:
 * ```php
 * // In DeviceController::update()
 * public function update(UpdateDeviceRequest $request, Device $device)
 * {
 *     // At this point, all data is already validated
 *     // $request->validated() returns only validated data
 *     $validated = $request->validated();
 *     // $validated = [
 *     //     'name' => 'John\'s iPhone',
 *     //     'mac_address' => 'AA:BB:CC:DD:EE:FF',
 *     //     'status' => 'active',
 *     //     ...
 *     // ]
 * }
 * ```
 */
class UpdateDeviceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 
     * This method checks if the user has permission to update this device.
     * Authorization is handled by DevicePolicy, which checks if the device
     * belongs to the current user.
     * 
     * What This Checks:
     * - User is authenticated (logged in)
     * - User owns the device (checked via DevicePolicy)
     * 
     * Note: Device ownership is checked via DevicePolicy::update() method.
     * This method just returns true because authorization is handled by the policy.
     * 
     * @return bool True if user can update devices, false otherwise
     * 
     * Usage:
     * This method is called automatically by Laravel before validation.
     * If it returns false, Laravel returns 403 Forbidden error.
     * 
     * Note: The actual ownership check happens in DeviceController using
     * $this->authorize('update', $device), which calls DevicePolicy::update().
     */
    public function authorize(): bool
    {
        // Authorization is handled by DevicePolicy in the controller
        // This method just needs to return true to allow validation to proceed
        // The controller will call $this->authorize('update', $device) to check ownership
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     * 
     * This method returns an array of validation rules for each field.
     * Laravel will validate all fields against these rules before allowing
     * the controller method to be called.
     * 
     * Key Difference from StoreDeviceRequest:
     * - MAC address unique check excludes the current device being updated
     * - This allows keeping the same MAC address when updating other fields
     * - Uses Rule::unique()->ignore() to exclude current device from uniqueness check
     * 
     * Validation Rules:
     * 
     * 1. name (required):
     *    - 'required': Field must be provided (cannot be empty)
     *    - 'string': Value must be a string (not array or object)
     *    - 'max:255': Maximum length is 255 characters (database column limit)
     *    - Purpose: Device name for identification (e.g., "John's iPhone")
     * 
     * 2. mac_address (required):
     *    - 'required': Field must be provided (cannot be empty)
     *    - 'string': Value must be a string
     *    - ValidMacAddress: Custom rule validates MAC format (XX:XX:XX:XX:XX:XX or XX-XX-XX-XX-XX-XX)
     *    - Rule::unique()->ignore(): MAC address must be unique, but ignore current device
     *    - Purpose: Unique identifier for device (used for network blocking)
     *    - Note: Allows keeping same MAC address when updating other fields
     * 
     * 3. status (required):
     *    - 'required': Field must be provided
     *    - 'in:active,blocked,whitelisted': Value must be one of these three options
     *    - Purpose: Device status (active=normal, blocked=no internet, whitelisted=unrestricted)
     * 
     * 4. remaining_time_minutes (optional):
     *    - 'nullable': Field is optional (can be empty)
     *    - 'integer': Value must be a whole number (not decimal)
     *    - 'min:0': Minimum value is 0 (cannot be negative)
     *    - 'max:9999': Maximum value is 9999 (prevents unreasonably large values)
     *    - Purpose: Current time left for device
     * 
     * 5. total_time_allocated (optional):
     *    - 'nullable': Field is optional (can be empty)
     *    - 'integer': Value must be a whole number
     *    - 'min:0': Minimum value is 0
     *    - 'max:9999': Maximum value is 9999
     *    - Purpose: Total time allocated for tracking/reporting
     * 
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     *         Array of validation rules, keyed by field name
     * 
     * Usage:
     * This method is called automatically by Laravel during validation.
     * You don't call it directly.
     */
    public function rules(): array
    {
        // Get the device being updated from route parameter
        // Route model binding automatically resolves Device from route {device} parameter
        // $this->route('device') gets the Device model instance from the route
        $device = $this->route('device');

        return [
            // Device name - required, string, max 255 characters
            // This is the human-readable name for the device (e.g., "John's iPhone")
            'name' => [
                'required',           // Field must be provided
                'string',             // Value must be a string
                'max:255',            // Maximum 255 characters (database column limit)
            ],

            // MAC address - required, valid format, unique (except current device)
            // This is the unique identifier for the device's network interface
            // Format: XX:XX:XX:XX:XX:XX or XX-XX-XX-XX-XX-XX
            // Must be unique in the devices table, but we ignore the current device
            // This allows keeping the same MAC address when updating other fields
            'mac_address' => [
                'required',                                                    // Field must be provided
                'string',                                                      // Value must be a string
                new ValidMacAddress(),                                         // Custom rule: validates MAC format
                Rule::unique('devices', 'mac_address')->ignore($device->id),  // Unique, but ignore current device
            ],

            // Device role - required, must be one of: child, guest, parent
            // - child: Device subject to time limits (default)
            // - guest: Temporary access device
            // - parent: Unrestricted access device
            'role' => [
                'required',                  // Field must be provided
                'string',                    // Value must be a string
                'in:child,guest,parent',     // Value must be one of these three
            ],

            // Device status - required, must be one of: active, blocked, whitelisted
            // - active: Device can access internet (subject to time limits)
            // - blocked: Device is blocked from internet access
            // - whitelisted: Device bypasses all restrictions (unlimited access)
            'status' => [
                'required',                          // Field must be provided
                'in:active,blocked,whitelisted',     // Value must be one of these three
            ],

            // Remaining time in minutes - optional, integer, 0-9999
            // This is the current time left for the device
            'remaining_time_minutes' => [
                'nullable',    // Field is optional (can be empty)
                'integer',     // Value must be a whole number
                'min:0',       // Minimum value is 0 (cannot be negative)
                'max:9999',    // Maximum value is 9999 (prevents unreasonably large values)
            ],

            // Total time allocated - optional, integer, 0-9999
            // This is the total time allocated for tracking/reporting purposes
            'total_time_allocated' => [
                'nullable',    // Field is optional (can be empty)
                'integer',     // Value must be a whole number
                'min:0',       // Minimum value is 0
                'max:9999',    // Maximum value is 9999
            ],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     * 
     * This method allows us to provide custom error messages that are more
     * user-friendly than Laravel's default messages.
     * 
     * @return array<string, string> Array of custom error messages, keyed by field.rule
     * 
     * Usage:
     * This method is called automatically by Laravel when validation fails.
     * You don't call it directly.
     */
    public function messages(): array
    {
        return [
            // Custom error messages for better user experience
            'name.required' => 'Device name is required.',
            'name.max' => 'Device name cannot exceed 255 characters.',
            
            'mac_address.required' => 'MAC address is required.',
            'mac_address.unique' => 'This MAC address is already registered to another device.',
            
            'role.required' => 'Device role is required.',
            'role.in' => 'Device role must be one of: child, guest, or parent.',
            
            'status.required' => 'Device status is required.',
            'status.in' => 'Device status must be one of: active, blocked, or whitelisted.',
            
            'remaining_time_minutes.integer' => 'Remaining time must be a whole number.',
            'remaining_time_minutes.min' => 'Remaining time cannot be negative.',
            'remaining_time_minutes.max' => 'Remaining time cannot exceed 9999 minutes.',
            
            'total_time_allocated.integer' => 'Total time allocated must be a whole number.',
            'total_time_allocated.min' => 'Total time allocated cannot be negative.',
            'total_time_allocated.max' => 'Total time allocated cannot exceed 9999 minutes.',
        ];
    }
}

