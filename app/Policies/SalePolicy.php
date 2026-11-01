<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;

/**
 * All signed-in staff record and view sales. Discount limits are enforced in SaleService;
 * returns, cancellations and refunds are admin-only.
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

    /**
     * Returns, cancellations and refunds are admin-only (owner decision, ADR-0020).
     */
    public function refund(User $user, Sale $sale): bool
    {
        return $user->isAdmin();
    }
}
