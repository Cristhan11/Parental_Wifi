<?php

namespace App\Policies;

use App\Models\FlaggedWebsite;
use App\Models\User;

/**
 * Flagged Website Policy
 * 
 * This policy ensures that users (parents) can only manage flagged websites
 * for their own devices. It provides authorization checks for all flagged website
 * operations to prevent parents from flagging/unflagging websites for other parents' devices.
 * 
 * What is a Policy?
 * - A policy is Laravel's way of organizing authorization logic
 * - It defines who can perform what actions on a model (FlaggedWebsite in this case)
 * - Policies are automatically called by Laravel when using authorization methods
 *   like $this->authorize() in controllers
 * 
 * How It Works:
 * - Each method in this policy checks if the user owns the device that the
 *   flagged website belongs to
 * - Ownership is determined by checking if flaggedWebsite->device->user_id === user->id
 * - If user owns device: return true (allow action)
 * - If user doesn't own device: return false (deny action)
 * 
 * Security:
 * - Prevents parents from flagging/unflagging websites for other parents' devices
 * - Ensures data privacy and security
 * - Works with route model binding to automatically check authorization
 */
class FlaggedWebsitePolicy
{
    /**
     * Determine if the user can view any flagged websites.
     * 
     * This method is called when checking if a user can view the flagged websites list.
     * In our system, all authenticated parents can view their own devices' flagged websites,
     * so this returns true for any authenticated user.
     * 
     * @param User $user The authenticated user (parent)
     * @return bool True if user can view flagged websites list, false otherwise
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users (parents) can view their flagged websites list
        // The controller will filter to show only flagged websites for their own devices
        return true;
    }

    /**
     * Determine if the user can view the flagged website.
     * 
     * This method checks if a user can view a specific flagged website. Users can only
     * view flagged websites that belong to their devices.
     * 
     * @param User $user The authenticated user (parent)
     * @param FlaggedWebsite $flaggedWebsite The flagged website to check
     * @return bool True if user owns the device, false otherwise
     */
    public function view(User $user, FlaggedWebsite $flaggedWebsite): bool
    {
        // Check if the device that this flagged website belongs to is owned by the user
        return $flaggedWebsite->device->user_id === $user->id;
    }

    /**
     * Determine if the user can create flagged websites.
     * 
     * This method checks if a user can create new flagged websites. In our system,
     * all authenticated parents can create flagged websites for their own devices.
     * 
     * Note: Device ownership is validated in the form request (StoreFlaggedWebsiteRequest),
     * so we just return true here. The form request ensures the device belongs to the user.
     * 
     * @param User $user The authenticated user (parent)
     * @return bool True if user can create flagged websites, false otherwise
     */
    public function create(User $user): bool
    {
        // All authenticated users (parents) can create flagged websites
        // Device ownership is validated in StoreFlaggedWebsiteRequest
        return true;
    }

    /**
     * Determine if the user can update the flagged website.
     * 
     * This method checks if a user can update a specific flagged website. Users can only
     * update flagged websites that belong to their devices.
     * 
     * @param User $user The authenticated user (parent)
     * @param FlaggedWebsite $flaggedWebsite The flagged website to check
     * @return bool True if user owns the device, false otherwise
     */
    public function update(User $user, FlaggedWebsite $flaggedWebsite): bool
    {
        // Check if the device that this flagged website belongs to is owned by the user
        return $flaggedWebsite->device->user_id === $user->id;
    }

    /**
     * Determine if the user can delete the flagged website.
     * 
     * This method checks if a user can delete a specific flagged website. Users can only
     * delete flagged websites that belong to their devices.
     * 
     * @param User $user The authenticated user (parent)
     * @param FlaggedWebsite $flaggedWebsite The flagged website to check
     * @return bool True if user owns the device, false otherwise
     */
    public function delete(User $user, FlaggedWebsite $flaggedWebsite): bool
    {
        // Check if the device that this flagged website belongs to is owned by the user
        return $flaggedWebsite->device->user_id === $user->id;
    }
}
