<?php

namespace App\Policies;

use App\Models\User;

/**
 * All signed-in staff view and receive payments.
 */
class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }
}
