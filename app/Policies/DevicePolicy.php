<?php

namespace App\Policies;

use App\Models\Device;
use App\Models\User;

/**
 * Device Policy
 *
 * This policy ensures that users (parents) can only manage their own devices.
 * It provides authorization checks for all device-related operations to prevent
 * parents from accessing or modifying devices that belong to other parents.
 *
 * What is a Policy?
 * - A policy is Laravel's way of organizing authorization logic
 * - It defines who can perform what actions on a model (Device in this case)
 * - Policies are automatically called by Laravel when using authorization methods
 *   like $this->authorize() in controllers
 *
 * How It Works:
 * - Each method in this policy checks if the user owns the device
 * - Ownership is determined by checking if device->user_id === user->id
 * - If user owns device: return true (allow action)
 * - If user doesn't own device: return false (deny action)
 *
 * Integration Points:
 * - Called automatically by Laravel when using $this->authorize() in DeviceController
 * - Can be used manually: Gate::allows('view', $device) or $user->can('view', $device)
 * - Works with route model binding to automatically check authorization
 *
 * Usage Example:
 * ```php
 * // In DeviceController
 * public function show(Device $device)
 * {
 *     // This automatically calls DevicePolicy::view()
 *     $this->authorize('view', $device);
 *
 *     // If user doesn't own device, Laravel throws 403 Forbidden
 *     // If user owns device, code continues here
 *     return view('devices.show', compact('device'));
 * }
 * ```
 */
class DevicePolicy
{
    /**
     * Determine if the user can view any devices.
     *
     * This method is called when checking if a user can view the device list.
     * In our system, all authenticated parents can view their own devices,
     * so this returns true for any authenticated user.
     *
     * What This Checks:
     * - User is authenticated (logged in)
     * - User has permission to view device list
     *
     * Note: This doesn't check ownership because the controller will filter
     * devices to only show the user's own devices. This just checks if they
     * have permission to view devices in general.
     *
     * @param  User  $user  The authenticated user (parent)
     * @return bool True if user can view device list, false otherwise
     *
     * Usage Example:
     * ```php
     * // In DeviceController::index()
     * if ($user->can('viewAny', Device::class)) {
     *     // User can view device list
     *     $devices = $user->devices; // Get only user's devices
     * }
     * ```
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users (parents) can view their device list
        // The controller will filter to show only their own devices
        // This just checks if they have general permission to view devices
        return true;
    }

    /**
     * Determine if the user can view the device.
     *
     * This method checks if a user can view a specific device. Users can only
     * view devices that belong to them (device->user_id === user->id).
     *
     * What This Checks:
     * - Device belongs to the user (device->user_id === user->id)
     * - If device belongs to user: allow (return true)
     * - If device doesn't belong to user: deny (return false)
     *
     * Security:
     * - Prevents parents from viewing other parents' devices
     * - Ensures data privacy and security
     *
     * @param  User  $user  The authenticated user (parent)
     * @param  Device  $device  The device to check
     * @return bool True if user owns the device, false otherwise
     *
     * Usage Example:
     * ```php
     * // In DeviceController::show()
     * $this->authorize('view', $device);
     * // If user doesn't own device, Laravel throws 403 Forbidden
     * // If user owns device, code continues
     * ```
     */
    public function view(User $user, Device $device): bool
    {
        // Check if device belongs to this user
        // device->user_id is the foreign key that links device to user
        // user->id is the authenticated user's ID
        // If they match, user owns the device and can view it
        return $device->user_id === $user->id;
    }

    /**
     * Determine if the user can create devices.
     *
     * This method checks if a user can create new devices. In our system,
     * all authenticated parents can create devices, so this returns true
     * for any authenticated user.
     *
     * What This Checks:
     * - User is authenticated (logged in)
     * - User has permission to create devices
     *
     * Note: This doesn't check ownership because new devices don't exist yet.
     * The controller will automatically assign the device to the current user
     * when creating it.
     *
     * @param  User  $user  The authenticated user (parent)
     * @return bool True if user can create devices, false otherwise
     *
     * Usage Example:
     * ```php
     * // In DeviceController::create()
     * $this->authorize('create', Device::class);
     * // If user can create, show create form
     * // If user can't create, Laravel throws 403 Forbidden
     * ```
     */
    public function create(User $user): bool
    {
        // All authenticated users (parents) can create devices
        // The controller will automatically assign device to current user
        return true;
    }

    /**
     * Determine if the user can update the device.
     *
     * This method checks if a user can update a specific device. Users can only
     * update devices that belong to them (device->user_id === user->id).
     *
     * What This Checks:
     * - Device belongs to the user (device->user_id === user->id)
     * - If device belongs to user: allow (return true)
     * - If device doesn't belong to user: deny (return false)
     *
     * Security:
     * - Prevents parents from modifying other parents' devices
     * - Ensures only device owner can change device settings
     *
     * @param  User  $user  The authenticated user (parent)
     * @param  Device  $device  The device to check
     * @return bool True if user owns the device, false otherwise
     *
     * Usage Example:
     * ```php
     * // In DeviceController::update()
     * $this->authorize('update', $device);
     * // If user doesn't own device, Laravel throws 403 Forbidden
     * // If user owns device, update proceeds
     * ```
     */
    public function update(User $user, Device $device): bool
    {
        // Check if device belongs to this user
        // Only device owner can update device settings
        return $device->user_id === $user->id;
    }

    /**
     * Determine if the user can delete the device.
     *
     * This method checks if a user can delete a specific device. Users can only
     * delete devices that belong to them (device->user_id === user->id).
     *
     * What This Checks:
     * - Device belongs to the user (device->user_id === $user->id)
     * - If device belongs to user: allow (return true)
     * - If device doesn't belong to user: deny (return false)
     *
     * Security:
     * - Prevents parents from deleting other parents' devices
     * - Ensures only device owner can remove device
     *
     * Important:
     * - Deleting a device will cascade delete related records (browsing logs, etc.)
     * - This is handled by database foreign key constraints
     *
     * @param  User  $user  The authenticated user (parent)
     * @param  Device  $device  The device to check
     * @return bool True if user owns the device, false otherwise
     *
     * Usage Example:
     * ```php
     * // In DeviceController::destroy()
     * $this->authorize('delete', $device);
     * // If user doesn't own device, Laravel throws 403 Forbidden
     * // If user owns device, deletion proceeds
     * ```
     */
    public function delete(User $user, Device $device): bool
    {
        // Check if device belongs to this user
        // Only device owner can delete device
        // Database will cascade delete related records (browsing logs, etc.)
        return $device->user_id === $user->id;
    }
}
