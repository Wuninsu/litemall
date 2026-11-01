<?php

namespace App\Livewire\Products;

use App\Livewire\Products\Concerns\ManagesProductPanel;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Product details page. Everyone can view it; administrators can open the same edit panel used on the list.
 */
class Show extends Component
{
    use ManagesProductPanel;

    #[Locked]
    public Product $product;

    public function mount(Product $product): void
    {
        $this->authorize('viewAny', Product::class);

        $this->product = $product;
    }

    public function productSaved(Product $product): void
    {
        $this->product = $product->fresh();
    }

    public function render(): View
    {
        $this->product->loadMissing('creator');

        return view('livewire.products.show', [
            'categories' => app(ProductService::class)->categories(),
        ])->title($this->product->name);
    }
}
