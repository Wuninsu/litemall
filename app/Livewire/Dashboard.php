<?php

namespace App\Livewire;

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Dashboard')]
class Dashboard extends Component
{
    public function greeting(): string
    {
        return match (true) {
            now()->hour < 12 => 'Good morning',
            now()->hour < 17 => 'Good afternoon',
            default => 'Good evening',
        };
    }

    /**
     * Customer and product counts, shown to everyone.
     *
     * @return array{customers: int, products: int}
     */
    #[Computed]
    public function shopStats(): array
    {
        return [
            'customers' => Customer::query()->active()->count(),
            'products' => Product::query()->active()->count(),
        ];
    }

    /**
     * Staff account counts, shown to administrators only.
     *
     * @return array{active: int, admins: int, cashiers: int, inactive: int}|null
     */
    #[Computed]
    public function teamStats(): ?array
    {
        if (! auth()->user()->can('viewAny', User::class)) {
            return null;
        }

        return [
            'active' => User::query()->active()->count(),
            'admins' => User::query()->active()->where('role', UserRole::Admin)->count(),
            'cashiers' => User::query()->active()->where('role', UserRole::Cashier)->count(),
            'inactive' => User::query()->where('is_active', false)->count(),
        ];
    }

    public function render(): View
    {
        return view('livewire.dashboard');
    }
}
