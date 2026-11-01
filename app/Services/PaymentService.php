<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Exceptions\Payments\PaymentException;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Receives payments towards a customer's outstanding balance and applies them to unpaid sales,
 * oldest sale first (ADR-0006). Overpayment is refused; the customer cannot build up credit.
 */
class PaymentService
{
    /**
     * Total the customer still owes, in pesewas.
     */
    public function outstandingFor(Customer $customer): int
    {
        return (int) Sale::query()
            ->where('customer_id', $customer->id)
            ->sum(DB::raw('total_pesewas - paid_pesewas'));
    }

    /**
     * How a payment of `$amount` would be split, without saving anything.
     *
     * @return list<array{sale: Sale, amount: int}>
     */
    public function previewAllocation(Customer $customer, int $amount): array
    {
        $sales = Sale::query()->where('customer_id', $customer->id)->outstanding()->get();

        return $this->allocate($sales, $amount);
    }

    /**
     * @throws PaymentException
     */
    public function receive(
        Customer $customer,
        int $amount,
        PaymentMethod $method,
        User $actor,
        ?string $reference = null,
        ?string $notes = null,
        ?string $idempotencyKey = null,
    ): Payment {
        return DB::transaction(function () use ($customer, $amount, $method, $actor, $reference, $notes, $idempotencyKey): Payment {
            if ($idempotencyKey !== null && ($existing = Payment::query()->where('idempotency_key', $idempotencyKey)->first())) {
                return $existing;
            }

            if ($amount <= 0) {
                throw PaymentException::amountMustBePositive();
            }

            // Lock the unpaid sales so two payments taken at the same time cannot both settle the same balance.
            $sales = Sale::query()
                ->where('customer_id', $customer->id)
                ->outstanding()
                ->lockForUpdate()
                ->get();

            $owed = $sales->sum(fn (Sale $sale): int => $sale->balancePesewas());

            if ($owed === 0) {
                throw PaymentException::nothingOwed();
            }

            if ($amount > $owed) {
                throw PaymentException::exceedsBalance($owed);
            }

            $payment = Payment::query()->create([
                'customer_id' => $customer->id,
                'received_by' => $actor->id,
                'amount_pesewas' => $amount,
                'method' => $method,
                'reference' => $this->blankToNull($reference),
                'notes' => $this->blankToNull($notes),
                'idempotency_key' => $idempotencyKey,
                'received_at' => now(),
            ]);

            foreach ($this->allocate($sales, $amount) as ['sale' => $sale, 'amount' => $applied]) {
                $payment->allocations()->create(['sale_id' => $sale->id, 'amount_pesewas' => $applied]);

                $paid = $sale->paid_pesewas + $applied;

                $sale->forceFill([
                    'paid_pesewas' => $paid,
                    'status' => SaleStatus::for($sale->total_pesewas, $paid),
                ])->save();
            }

            return $payment->load('allocations.sale');
        });
    }

    /**
     * Oldest-first split of an amount across sales that are already in oldest-first order.
     *
     * @param  Collection<int, Sale>  $sales
     * @return list<array{sale: Sale, amount: int}>
     */
    private function allocate(Collection $sales, int $amount): array
    {
        $remaining = $amount;
        $allocations = [];

        foreach ($sales as $sale) {
            if ($remaining <= 0) {
                break;
            }

            $applied = min($remaining, $sale->balancePesewas());

            if ($applied > 0) {
                $allocations[] = ['sale' => $sale, 'amount' => $applied];
                $remaining -= $applied;
            }
        }

        return $allocations;
    }

    private function blankToNull(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
