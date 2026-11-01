<?php

namespace App\Livewire;

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
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
     * Today's trading figures and what customers owe, in pesewas. Shown to everyone.
     *
     * @return array{salesToday: int, salesCountToday: int, receivedToday: int, outstanding: int, owingCustomers: int}
     */
    #[Computed]
    public function moneyStats(): array
    {
        $today = Sale::query()->where('sold_at', '>=', today());

        return [
            'salesToday' => (int) (clone $today)->sum('total_pesewas'),
            'salesCountToday' => (clone $today)->count(),
            'receivedToday' => (int) Payment::query()->where('received_at', '>=', today())->sum('amount_pesewas'),
            'outstanding' => (int) Sale::query()->sum(DB::raw('total_pesewas - paid_pesewas')),
            'owingCustomers' => Customer::query()->whereHas('sales', fn ($query) => $query->whereColumn('paid_pesewas', '<', 'total_pesewas'))->count(),
        ];
    }

    /**
     * Customer and product counts, shown to everyone.
     *
     * @return array{customers: int, products: int, lowStock: int}
     */
    #[Computed]
    public function shopStats(): array
    {
        return [
            'customers' => Customer::query()->active()->count(),
            'products' => Product::query()->active()->count(),
            'lowStock' => Product::query()->active()->where('stock_quantity', '<=', Product::LOW_STOCK_THRESHOLD)->count(),
        ];
    }

    /**
     * @return Collection<int, Sale>
     */
    #[Computed]
    public function recentSales(): Collection
    {
        return Sale::query()->with('customer')->latest('sold_at')->latest('receipt_number')->limit(6)->get();
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
