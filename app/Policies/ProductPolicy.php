<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

/**
 * Everyone can look up products and prices; only administrators add products or change prices (ADR-0012).
 */
class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Product $product): bool
    {
        return $user->isAdmin();
    }

    public function changeStatus(User $user, Product $product): bool
    {
        return $user->isAdmin();
    }
}
