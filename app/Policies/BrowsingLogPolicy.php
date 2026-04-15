<?php

namespace App\Policies;

use App\Models\BrowsingLog;
use App\Models\User;

/**
 * Browsing Log Policy
 *
 * This policy ensures that users (parents) can only view browsing logs
 * for their own devices. It provides authorization checks to prevent parents
 * from viewing browsing history of other parents' devices.
 *
 * What is a Policy?
 * - A policy is Laravel's way of organizing authorization logic
 * - It defines who can perform what actions on a model (BrowsingLog in this case)
 * - Policies are automatically called by Laravel when using authorization methods
 *   like $this->authorize() in controllers
 *
 * Why Do We Need This Policy?
 * - Security: Prevents parents from viewing other parents' children's browsing history
 * - Privacy: Ensures each parent only sees data for their own devices
 * - Data Isolation: Keeps browsing logs private to the device owner
 *
 * How It Works:
 * - Each method in this policy checks if the user owns the device that the
 *   browsing log belongs to
 * - Ownership is determined by checking if browsingLog->device->user_id === user->id
 * - If user owns device: return true (allow action)
 * - If user doesn't own device: return false (deny action)
 *
 * Security:
 * - Prevents unauthorized access to browsing history
 * - Ensures data privacy and security
 * - Works with route model binding to automatically check authorization
 */
class BrowsingLogPolicy
{
    /**
     * Determine if the user can view any browsing logs.
     *
     * This method is called when checking if a user can view the browsing logs list.
     * In our system, all authenticated parents can view their own devices' browsing logs,
     * so this returns true for any authenticated user. The controller will filter to show
     * only browsing logs for their own devices.
     *
     * @param  User  $user  The authenticated user (parent)
     * @return bool True if user can view browsing logs list, false otherwise
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users (parents) can view their browsing logs list
        // The controller will filter to show only browsing logs for their own devices
        return true;
    }

    /**
     * Determine if the user can view the browsing log.
     *
     * This method checks if a user can view a specific browsing log. Users can only
     * view browsing logs that belong to their devices.
     *
     * Why Check Device Ownership?
     * - Browsing logs contain sensitive information about websites visited
     * - Each parent should only see browsing history for their own children's devices
     * - This prevents parents from accidentally or intentionally viewing other families' data
     *
     * @param  User  $user  The authenticated user (parent)
     * @param  BrowsingLog  $browsingLog  The browsing log to check
     * @return bool True if user owns the device, false otherwise
     */
    public function view(User $user, BrowsingLog $browsingLog): bool
    {
        // Check if the device that this browsing log belongs to is owned by the user
        // browsingLog->device gets the Device model through the relationship
        // device->user_id is the ID of the parent who owns the device
        // We compare it with user->id to ensure the logged-in user owns the device
        return $browsingLog->device->user_id === $user->id;
    }
}
