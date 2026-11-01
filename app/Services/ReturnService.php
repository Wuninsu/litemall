<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Exceptions\Sales\SaleReturnException;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleLine;
use App\Models\SaleReturn;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Returns and cancellations (ADR-0020). Nothing on the original sale is edited or deleted: each return is a new
 * `sale_returns` row with its lines, and the sale's running totals (`returned_pesewas`, `paid_pesewas`, `status`)
 * are updated in the same transaction.
 *
 * Money rule (owner decision): the value returned first reduces what is still owed on the sale; only the part
 * the customer had actually paid beyond that is refunded, with a method. No store credit.
 */
class ReturnService
{
    public function __construct(private ReceiptNumberService $numbers) {}

    /**
     * What a return would do, without saving. Used for the live preview.
     *
     * @param  array<string, int>  $quantities  sale line id → units to return
     * @return array{credit: int, debtReduction: int, refund: int, isCancellation: bool, values: array<string, int>}
     *
     * @throws SaleReturnException
     */
    public function preview(Sale $sale, array $quantities): array
    {
        return $this->calculate($sale, $sale->lines, $this->cleanQuantities($quantities));
    }

    /**
     * Records a return or cancellation.
     *
     * @param  array<string, int>  $quantities  sale line id → units to return
     * @param  array<string, bool>  $restock  sale line id → put the units back into stock?
     *
     * @throws SaleReturnException
     */
    public function process(
        Sale $sale,
        array $quantities,
        array $restock,
        string $reason,
        User $actor,
        ?PaymentMethod $refundMethod = null,
        ?string $refundReference = null,
        ?string $idempotencyKey = null,
    ): SaleReturn {
        return DB::transaction(function () use ($sale, $quantities, $restock, $reason, $actor, $refundMethod, $refundReference, $idempotencyKey): SaleReturn {
            if ($idempotencyKey !== null && ($existing = SaleReturn::query()->where('idempotency_key', $idempotencyKey)->first())) {
                return $existing;
            }

            $reason = Str::squish($reason);

            if ($reason === '') {
                throw SaleReturnException::reasonRequired();
            }

            // Lock the sale and its lines so two returns at the same time cannot return the same units twice.
            $sale = Sale::query()->whereKey($sale->getKey())->lockForUpdate()->firstOrFail();
            $lines = SaleLine::query()->where('sale_id', $sale->id)->lockForUpdate()->get();

            $quantities = $this->cleanQuantities($quantities);
            $result = $this->calculate($sale, $lines, $quantities);

            if ($result['refund'] > 0 && $refundMethod === null) {
                throw SaleReturnException::refundMethodRequired();
            }

            $saleReturn = SaleReturn::query()->create([
                'return_number' => $this->numbers->nextReturnNumber(),
                'sale_id' => $sale->id,
                'processed_by' => $actor->id,
                'type' => $result['isCancellation'] ? SaleReturn::TYPE_CANCELLATION : SaleReturn::TYPE_RETURN,
                'reason' => Str::limit($reason, 500, ''),
                'credit_pesewas' => $result['credit'],
                'debt_reduction_pesewas' => $result['debtReduction'],
                'refund_pesewas' => $result['refund'],
                'refund_method' => $result['refund'] > 0 ? $refundMethod : null,
                'refund_reference' => $result['refund'] > 0 ? $this->blankToNull($refundReference) : null,
                'idempotency_key' => $idempotencyKey,
                'returned_at' => now(),
            ]);

            foreach ($quantities as $lineId => $quantity) {
                $line = $lines->firstWhere('id', $lineId);
                $putBack = (bool) ($restock[$lineId] ?? false);

                $saleReturn->lines()->create([
                    'sale_line_id' => $line->id,
                    'product_id' => $line->product_id,
                    'quantity' => $quantity,
                    'value_pesewas' => $result['values'][$lineId],
                    'restocked' => $putBack,
                ]);

                $line->increment('returned_quantity', $quantity);

                if ($putBack) {
                    Product::query()->whereKey($line->product_id)->lockForUpdate()->first()?->increment('stock_quantity', $quantity);
                }
            }

            $sale->returned_pesewas += $result['credit'];
            $sale->paid_pesewas -= $result['refund'];
            $sale->recalculateStatus();
            $sale->save();

            return $saleReturn->load('lines');
        });
    }

    /**
     * Values each returned unit at the price paid, less its share of the sale discount
     * (line value × total ÷ subtotal, half-up to the pesewa). Returning everything that is left credits
     * exactly the remaining net total, so no pesewa is left over from rounding.
     *
     * @param  Collection<int, SaleLine>  $lines
     * @param  array<string, int>  $quantities
     * @return array{credit: int, debtReduction: int, refund: int, isCancellation: bool, values: array<string, int>}
     *
     * @throws SaleReturnException
     */
    private function calculate(Sale $sale, Collection $lines, array $quantities): array
    {
        if ($sale->isCancelled()) {
            throw SaleReturnException::alreadyCancelled();
        }

        if ($quantities === []) {
            throw SaleReturnException::nothingSelected();
        }

        $lines = $lines->keyBy('id');
        $values = [];

        foreach ($quantities as $lineId => $quantity) {
            $line = $lines->get($lineId);

            if ($line === null) {
                throw SaleReturnException::unknownLine();
            }

            if ($quantity > $line->returnableQuantity()) {
                throw SaleReturnException::tooMany($line->product_name, $line->returnableQuantity());
            }

            $lineValue = $line->unit_price_pesewas * $quantity;
            $values[$lineId] = $sale->subtotal_pesewas > 0
                ? intdiv($lineValue * $sale->total_pesewas + intdiv($sale->subtotal_pesewas, 2), $sale->subtotal_pesewas)
                : 0;
        }

        $remaining = $sale->netTotalPesewas();
        $isCancellation = $lines->every(fn (SaleLine $line) => ($quantities[$line->id] ?? 0) === $line->returnableQuantity());

        if ($isCancellation) {
            // Put any rounding difference on the last line so the credit equals what is left exactly.
            $lastId = array_key_last($values);
            $values[$lastId] += $remaining - array_sum($values);
        }

        $credit = min(array_sum($values), $remaining);
        $debtReduction = min($credit, $sale->balancePesewas());

        return [
            'credit' => $credit,
            'debtReduction' => $debtReduction,
            'refund' => $credit - $debtReduction,
            'isCancellation' => $isCancellation,
            'values' => $values,
        ];
    }

    /**
     * @param  array<string, int|string|null>  $quantities
     * @return array<string, int>
     */
    private function cleanQuantities(array $quantities): array
    {
        return collect($quantities)
            ->map(fn ($quantity) => (int) $quantity)
            ->filter(fn (int $quantity) => $quantity > 0)
            ->all();
    }

    private function blankToNull(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
