<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;

/**
 * All signed-in staff record and view sales. Discount limits are enforced in SaleService;
 * refunds and cancellations (not built yet) will be admin-only.
 */
class SalePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Sale $sale): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }
}
