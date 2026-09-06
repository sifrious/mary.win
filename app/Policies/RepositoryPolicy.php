<?php

namespace App\Policies;

use App\Models\Repository;
use App\Models\User;

class RepositoryPolicy
{
    /**
     * A user may only view/analyze their own synced repositories.
     */
    public function view(User $user, Repository $repository): bool
    {
        return $user->id === $repository->user_id;
    }

    /**
     * Analyzing, kite-reading, and re-syncing all mutate the user's own repository.
     */
    public function update(User $user, Repository $repository): bool
    {
        return $user->id === $repository->user_id;
    }
}
