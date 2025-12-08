<?php

namespace App\Policies;

use App\Models\BlockedWebsite;
use App\Models\User;

/**
 * Blocked Website Policy
 * 
 * This policy ensures that users (parents) can only manage blocked websites
 * for their own devices. It provides authorization checks for all blocked website
 * operations to prevent parents from blocking/unblocking websites for other parents' devices.
 * 
 * What is a Policy?
 * - A policy is Laravel's way of organizing authorization logic
 * - It defines who can perform what actions on a model (BlockedWebsite in this case)
 * - Policies are automatically called by Laravel when using authorization methods
 *   like $this->authorize() in controllers
 * 
 * How It Works:
 * - Each method in this policy checks if the user owns the device that the
 *   blocked website belongs to
 * - Ownership is determined by checking if blockedWebsite->device->user_id === user->id
 * - If user owns device: return true (allow action)
 * - If user doesn't own device: return false (deny action)
 * 
 * Security:
 * - Prevents parents from blocking/unblocking websites for other parents' devices
 * - Ensures data privacy and security
 * - Works with route model binding to automatically check authorization
 */
class BlockedWebsitePolicy
{
    /**
     * Determine if the user can view any blocked websites.
     * 
     * This method is called when checking if a user can view the blocked websites list.
     * In our system, all authenticated parents can view their own devices' blocked websites,
     * so this returns true for any authenticated user.
     * 
     * @param User $user The authenticated user (parent)
     * @return bool True if user can view blocked websites list, false otherwise
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users (parents) can view their blocked websites list
        // The controller will filter to show only blocked websites for their own devices
        return true;
    }

    /**
     * Determine if the user can view the blocked website.
     * 
     * This method checks if a user can view a specific blocked website. Users can only
     * view blocked websites that belong to their devices.
     * 
     * @param User $user The authenticated user (parent)
     * @param BlockedWebsite $blockedWebsite The blocked website to check
     * @return bool True if user owns the device, false otherwise
     */
    public function view(User $user, BlockedWebsite $blockedWebsite): bool
    {
        // Check if the device that this blocked website belongs to is owned by the user
        return $blockedWebsite->device->user_id === $user->id;
    }

    /**
     * Determine if the user can create blocked websites.
     * 
     * This method checks if a user can create new blocked websites. In our system,
     * all authenticated parents can create blocked websites for their own devices.
     * 
     * Note: Device ownership is validated in the form request (StoreBlockedWebsiteRequest),
     * so we just return true here. The form request ensures the device belongs to the user.
     * 
     * @param User $user The authenticated user (parent)
     * @return bool True if user can create blocked websites, false otherwise
     */
    public function create(User $user): bool
    {
        // All authenticated users (parents) can create blocked websites
        // Device ownership is validated in StoreBlockedWebsiteRequest
        return true;
    }

    /**
     * Determine if the user can update the blocked website.
     * 
     * This method checks if a user can update a specific blocked website. Users can only
     * update blocked websites that belong to their devices.
     * 
     * @param User $user The authenticated user (parent)
     * @param BlockedWebsite $blockedWebsite The blocked website to check
     * @return bool True if user owns the device, false otherwise
     */
    public function update(User $user, BlockedWebsite $blockedWebsite): bool
    {
        // Check if the device that this blocked website belongs to is owned by the user
        return $blockedWebsite->device->user_id === $user->id;
    }

    /**
     * Determine if the user can delete the blocked website.
     * 
     * This method checks if a user can delete a specific blocked website. Users can only
     * delete blocked websites that belong to their devices.
     * 
     * @param User $user The authenticated user (parent)
     * @param BlockedWebsite $blockedWebsite The blocked website to check
     * @return bool True if user owns the device, false otherwise
     */
    public function delete(User $user, BlockedWebsite $blockedWebsite): bool
    {
        // Check if the device that this blocked website belongs to is owned by the user
        return $blockedWebsite->device->user_id === $user->id;
    }
}

