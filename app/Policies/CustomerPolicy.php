<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

/**
 * Every signed-in staff member serves customers; only administrators deactivate or reactivate them.
 */
class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Customer $customer): bool
    {
        return true;
    }

    public function changeStatus(User $user, Customer $customer): bool
    {
        return $user->isAdmin();
    }
}
