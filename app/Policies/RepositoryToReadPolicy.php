<?php

namespace App\Policies;

use App\Models\RepositoryToRead;
use App\Models\User;

class RepositoryToReadPolicy
{
    /**
     * A user may only view/read repositories they added to their own reading list.
     */
    public function view(User $user, RepositoryToRead $repositoryToRead): bool
    {
        return $user->id === $repositoryToRead->user_id;
    }

    /**
     * Analyzing a reading-list repository mutates the user's own vocabulary data.
     */
    public function update(User $user, RepositoryToRead $repositoryToRead): bool
    {
        return $user->id === $repositoryToRead->user_id;
    }
}
