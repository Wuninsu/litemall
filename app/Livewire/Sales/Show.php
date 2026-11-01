<?php

namespace App\Livewire\Sales;

use App\Enums\PaymentMethod;
use App\Exceptions\Sales\SaleReturnException;
use App\Models\Sale;
use App\Services\ReturnService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Receipt / sale details page, printable. Administrators can return items or cancel the sale from a side panel
 * (ADR-0020); the work is done by ReturnService.
 */
class Show extends Component
{
    #[Locked]
    public Sale $sale;

    public bool $showReturnModal = false;

    /**
     * Units to return, keyed by sale line id.
     *
     * @var array<string, int|string>
     */
    public array $returnQuantities = [];

    /**
     * Whether returned units go back into stock, keyed by sale line id ("ask each time").
     *
     * @var array<string, bool>
     */
    public array $restock = [];

    public string $reason = '';

    public string $refundMethod = 'cash';

    public string $refundReference = '';

    #[Locked]
    public string $idempotencyKey = '';

    public ?string $notice = null;

    public function mount(Sale $sale): void
    {
        $this->authorize('view', $sale);

        $this->sale = $sale;
    }

    public function openReturn(): void
    {
        $this->authorize('refund', $this->sale);

        $this->sale->load('lines');
        $this->returnQuantities = $this->sale->lines->mapWithKeys(fn ($line) => [$line->id => 0])->all();
        $this->restock = $this->sale->lines->mapWithKeys(fn ($line) => [$line->id => true])->all();
        $this->reset('reason', 'refundReference');
        $this->refundMethod = PaymentMethod::Cash->value;
        $this->idempotencyKey = (string) Str::uuid();
        $this->resetErrorBag();
        $this->showReturnModal = true;
    }

    /**
     * Fills every line with all units still returnable — i.e. cancels the sale.
     */
    public function returnEverything(): void
    {
        $this->authorize('refund', $this->sale);

        $this->sale->load('lines');
        $this->returnQuantities = $this->sale->lines->mapWithKeys(fn ($line) => [$line->id => $line->returnableQuantity()])->all();
    }

    public function saveReturn(ReturnService $returnService): void
    {
        $this->authorize('refund', $this->sale);

        $this->validate([
            'returnQuantities' => ['array'],
            'returnQuantities.*' => ['nullable', 'integer', 'min:0'],
            'restock.*' => ['boolean'],
            'reason' => ['required', 'string', 'max:500'],
            'refundMethod' => ['required', Rule::enum(PaymentMethod::class)],
            'refundReference' => ['nullable', 'string', 'max:100'],
        ], [
            'reason.required' => 'Give a reason for the return or cancellation.',
            'returnQuantities.*.integer' => 'Quantities must be whole numbers.',
        ]);

        try {
            $saleReturn = $returnService->process(
                $this->sale,
                array_map('intval', $this->returnQuantities),
                $this->restock,
                $this->reason,
                auth()->user(),
                PaymentMethod::from($this->refundMethod),
                $this->refundReference,
                $this->idempotencyKey,
            );
        } catch (SaleReturnException $exception) {
            $this->addError($exception->field, $exception->getMessage());

            return;
        }

        $this->notice = ($saleReturn->isCancellation() ? 'Sale cancelled' : 'Items returned').' ('.$saleReturn->return_number.').'
            .($saleReturn->refund_pesewas > 0 ? ' Refund '.$saleReturn->money('refund').' to the customer.' : '');
        $this->showReturnModal = false;
        $this->sale->refresh();
    }

    /**
     * Live preview of the return: value, debt reduced and refund. Null when nothing is selected.
     *
     * @return array{credit: int, debtReduction: int, refund: int, isCancellation: bool, values: array<string, int>, error: string|null}|null
     */
    #[Computed]
    public function returnPreview(): ?array
    {
        $quantities = array_filter(array_map('intval', $this->returnQuantities), fn (int $quantity) => $quantity > 0);

        if ($quantities === []) {
            return null;
        }

        try {
            return [...app(ReturnService::class)->preview($this->sale, $quantities), 'error' => null];
        } catch (SaleReturnException $exception) {
            return ['credit' => 0, 'debtReduction' => 0, 'refund' => 0, 'isCancellation' => false, 'values' => [], 'error' => $exception->getMessage()];
        }
    }

    public function render(): View
    {
        $this->sale->load(['lines', 'customer', 'seller', 'allocations.payment.receiver', 'returns.processor', 'returns.lines.saleLine']);

        return view('livewire.sales.show', [
            'methods' => PaymentMethod::cases(),
        ])->title('Receipt '.$this->sale->receipt_number);
    }
}
