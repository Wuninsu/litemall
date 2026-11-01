<?php

namespace App\Livewire\Sales;

use App\Enums\SaleStatus;
use App\Models\Sale;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Sales list with search, payment-status and period filters, and totals for the filtered sales.
 */
#[Title('Sales')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $period = 'today';

    public function mount(): void
    {
        $this->authorize('viewAny', Sale::class);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'status', 'period'], true)) {
            $this->resetPage();
        }
    }

    public function render(): View
    {
        $query = Sale::query()
            ->when(trim($this->search) !== '', function ($query): void {
                $term = '%'.trim($this->search).'%';
                $query->where(fn ($query) => $query
                    ->where('receipt_number', 'like', $term)
                    ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', $term)));
            })
            ->when(SaleStatus::tryFrom($this->status), fn ($query, SaleStatus $status) => $query->where('status', $status))
            ->when($this->period === 'today', fn ($query) => $query->where('sold_at', '>=', today()))
            ->when($this->period === '7days', fn ($query) => $query->where('sold_at', '>=', today()->subDays(6)))
            ->when($this->period === '30days', fn ($query) => $query->where('sold_at', '>=', today()->subDays(29)));

        $totals = (clone $query)->selectRaw('count(*) as count, coalesce(sum(total_pesewas - returned_pesewas), 0) as total, coalesce(sum(paid_pesewas), 0) as paid')->first();

        return view('livewire.sales.index', [
            'sales' => $query->with(['customer', 'seller'])->withCount('lines')->latest('sold_at')->latest('receipt_number')->paginate(20),
            'totals' => [
                'count' => (int) $totals->count,
                'total' => (int) $totals->total,
                'paid' => (int) $totals->paid,
            ],
            'statuses' => SaleStatus::cases(),
        ]);
    }
}
