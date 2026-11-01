<?php

namespace App\Livewire\Products;

use App\Livewire\Products\Concerns\ManagesProductPanel;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Product list with one right-hand panel for creating and editing (list + modal pattern, ADR-0011).
 * Cashiers see the list read-only; only administrators add products or change prices (ADR-0012).
 */
#[Title('Products')]
class Index extends Component
{
    use ManagesProductPanel, WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $category = '';

    #[Url]
    public string $status = 'active';

    #[Url]
    public string $stock = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Product::class);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'category', 'status', 'stock'], true)) {
            $this->resetPage();
        }
    }

    public function render(): View
    {
        $products = Product::query()
            ->when(trim($this->search) !== '', function ($query): void {
                $term = '%'.trim($this->search).'%';
                $query->where(fn ($query) => $query->where('name', 'like', $term)->orWhere('sku', 'like', $term));
            })
            ->when($this->category !== '', fn ($query) => $query->where('category', $this->category))
            ->when($this->status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($this->status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($this->stock === 'low', fn ($query) => $query->whereBetween('stock_quantity', [1, Product::LOW_STOCK_THRESHOLD]))
            ->when($this->stock === 'out', fn ($query) => $query->where('stock_quantity', 0))
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.products.index', [
            'products' => $products,
            'categories' => app(ProductService::class)->categories(),
        ]);
    }
}
