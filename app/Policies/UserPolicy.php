<?php

namespace App\Policies;

use App\Models\User;

/**
 * Only administrators manage user accounts. Business invariants (last admin,
 * self-deactivation) are enforced in UserService, not here.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    public function deactivate(User $user, User $model): bool
    {
        return $user->isAdmin() && ! $user->is($model);
    }
}
