<?php

namespace App\Livewire\Sales;

use App\Models\Sale;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Receipt / sale details page, printable.
 */
class Show extends Component
{
    #[Locked]
    public Sale $sale;

    public function mount(Sale $sale): void
    {
        $this->authorize('view', $sale);

        $this->sale = $sale;
    }

    public function render(): View
    {
        $this->sale->load(['lines', 'customer', 'seller', 'allocations.payment.receiver']);

        return view('livewire.sales.show')->title('Receipt '.$this->sale->receipt_number);
    }
}
