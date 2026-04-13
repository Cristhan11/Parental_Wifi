<?php

namespace App\Policies;

use App\Models\FlaggedWebsite;
use App\Models\User;

/**
 * Flagged Website Policy — rules scoped to parent account (user_id).
 */
class FlaggedWebsitePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, FlaggedWebsite $flaggedWebsite): bool
    {
        return (int) $flaggedWebsite->user_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, FlaggedWebsite $flaggedWebsite): bool
    {
        return (int) $flaggedWebsite->user_id === (int) $user->id;
    }

    public function delete(User $user, FlaggedWebsite $flaggedWebsite): bool
    {
        return (int) $flaggedWebsite->user_id === (int) $user->id;
    }
}
