<?php

namespace App\Policies;

use App\Models\AccessAttempt;
use App\Models\User;

/**
 * Access Attempt Policy
 *
 * This policy ensures that users (parents) can only view access attempts
 * for their own devices. It provides authorization checks to prevent parents
 * from viewing security events of other parents' devices.
 *
 * What is a Policy?
 * - A policy is Laravel's way of organizing authorization logic
 * - It defines who can perform what actions on a model (AccessAttempt in this case)
 * - Policies are automatically called by Laravel when using authorization methods
 *   like $this->authorize() in controllers
 *
 * Why Do We Need This Policy?
 * - Security: Prevents parents from viewing other parents' security events
 * - Privacy: Ensures each parent only sees access attempts for their own devices
 * - Data Isolation: Keeps security logs private to the device owner
 *
 * What Are Access Attempts?
 * - Access attempts are security events that occur when:
 *   1. A child tries to access a blocked website (type: 'blocked_website')
 *      - The system denies access and logs the attempt
 *      - Parent is notified that child tried to access a blocked site
 *   2. A child visits a flagged website (type: 'flagged_website')
 *      - The system allows access but logs the visit
 *      - Parent is notified that child visited a flagged site
 *
 * How It Works:
 * - Each method in this policy checks if the user owns the device that the
 *   access attempt belongs to
 * - Ownership is determined by checking if accessAttempt->device->user_id === user->id
 * - If user owns device: return true (allow action)
 * - If user doesn't own device: return false (deny action)
 *
 * Security:
 * - Prevents unauthorized access to security event logs
 * - Ensures data privacy and security
 * - Works with route model binding to automatically check authorization
 */
class AccessAttemptPolicy
{
    /**
     * Determine if the user can view any access attempts.
     *
     * This method is called when checking if a user can view the access attempts list.
     * In our system, all authenticated parents can view their own devices' access attempts,
     * so this returns true for any authenticated user. The controller will filter to show
     * only access attempts for their own devices.
     *
     * @param  User  $user  The authenticated user (parent)
     * @return bool True if user can view access attempts list, false otherwise
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users (parents) can view their access attempts list
        // The controller will filter to show only access attempts for their own devices
        return true;
    }

    /**
     * Determine if the user can view the access attempt.
     *
     * This method checks if a user can view a specific access attempt. Users can only
     * view access attempts that belong to their devices.
     *
     * Why Check Device Ownership?
     * - Access attempts contain sensitive security information
     * - Each parent should only see security events for their own children's devices
     * - This prevents parents from viewing other families' security logs
     * - Protects privacy of both the child and the parent
     *
     * @param  User  $user  The authenticated user (parent)
     * @param  AccessAttempt  $accessAttempt  The access attempt to check
     * @return bool True if user owns the device, false otherwise
     */
    public function view(User $user, AccessAttempt $accessAttempt): bool
    {
        // Check if the device that this access attempt belongs to is owned by the user
        // accessAttempt->device gets the Device model through the relationship
        // device->user_id is the ID of the parent who owns the device
        // We compare it with user->id to ensure the logged-in user owns the device
        return $accessAttempt->device->user_id === $user->id;
    }
}
