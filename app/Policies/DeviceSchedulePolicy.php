<?php

namespace App\Policies;

use App\Models\DeviceSchedule;
use App\Models\User;

/**
 * Device Schedule Policy
 *
 * This policy ensures that users (parents) can only manage schedules
 * for their own devices. It provides authorization checks for all schedule
 * operations to prevent parents from managing schedules for other parents' devices.
 *
 * What is a Policy?
 * - A policy is Laravel's way of organizing authorization logic
 * - It defines who can perform what actions on a model (DeviceSchedule in this case)
 * - Policies are automatically called by Laravel when using authorization methods
 *   like $this->authorize() in controllers
 *
 * How It Works:
 * - Each method in this policy checks if the user owns the device that the
 *   schedule belongs to
 * - Ownership is determined by checking if schedule->device->user_id === user->id
 * - If user owns device: return true (allow action)
 * - If user doesn't own device: return false (deny action)
 *
 * Security:
 * - Prevents parents from managing schedules for other parents' devices
 * - Ensures data privacy and security
 * - Works with route model binding to automatically check authorization
 */
class DeviceSchedulePolicy
{
    /**
     * Determine if the user can view any schedules.
     *
     * This method is called when checking if a user can view the schedules list.
     * In our system, all authenticated parents can view their own devices' schedules,
     * so this returns true for any authenticated user.
     *
     * @param  User  $user  The authenticated user (parent)
     * @return bool True if user can view schedules list, false otherwise
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users (parents) can view their schedules list
        // The controller will filter to show only schedules for their own devices
        return true;
    }

    /**
     * Determine if the user can view the schedule.
     *
     * This method checks if a user can view a specific schedule. Users can only
     * view schedules that belong to their devices.
     *
     * @param  User  $user  The authenticated user (parent)
     * @param  DeviceSchedule  $schedule  The schedule to check
     * @return bool True if user owns the device, false otherwise
     */
    public function view(User $user, DeviceSchedule $schedule): bool
    {
        // Check if the device that this schedule belongs to is owned by the user
        return $schedule->device->user_id === $user->id;
    }

    /**
     * Determine if the user can create schedules.
     *
     * This method checks if a user can create new schedules. In our system,
     * all authenticated parents can create schedules for their own devices.
     *
     * Note: Device ownership is validated in the form request (StoreDeviceScheduleRequest),
     * so we just return true here. The form request ensures the device belongs to the user.
     *
     * @param  User  $user  The authenticated user (parent)
     * @return bool True if user can create schedules, false otherwise
     */
    public function create(User $user): bool
    {
        // All authenticated users (parents) can create schedules
        // Device ownership is validated in StoreDeviceScheduleRequest
        return true;
    }

    /**
     * Determine if the user can update the schedule.
     *
     * This method checks if a user can update a specific schedule. Users can only
     * update schedules that belong to their devices.
     *
     * @param  User  $user  The authenticated user (parent)
     * @param  DeviceSchedule  $schedule  The schedule to check
     * @return bool True if user owns the device, false otherwise
     */
    public function update(User $user, DeviceSchedule $schedule): bool
    {
        // Check if the device that this schedule belongs to is owned by the user
        return $schedule->device->user_id === $user->id;
    }

    /**
     * Determine if the user can delete the schedule.
     *
     * This method checks if a user can delete a specific schedule. Users can only
     * delete schedules that belong to their devices.
     *
     * @param  User  $user  The authenticated user (parent)
     * @param  DeviceSchedule  $schedule  The schedule to check
     * @return bool True if user owns the device, false otherwise
     */
    public function delete(User $user, DeviceSchedule $schedule): bool
    {
        // Check if the device that this schedule belongs to is owned by the user
        return $schedule->device->user_id === $user->id;
    }
}
