<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PriceTier;
use App\Enums\SaleStatus;
use App\Exceptions\Sales\SaleException;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

/**
 * Records sales. Everything happens in one database transaction: product rows are locked, prices are
 * snapshotted onto the lines, stock is reduced, a receipt number is issued and any payment taken at the
 * counter is recorded and allocated to the sale. Sales are never edited afterwards (ADR-0004).
 */
class SaleService
{
    public function __construct(private ReceiptNumberService $receiptNumbers) {}

    /**
     * @param  array{
     *     customer_id?: string|null,
     *     lines: list<array{product_id: string, quantity: int}>,
     *     discount_type?: 'amount'|'percent'|null,
     *     discount_value?: int,
     *     discount_reason?: string|null,
     *     payment_pesewas?: int,
     *     payment_method?: PaymentMethod|string|null,
     *     payment_reference?: string|null,
     *     notes?: string|null,
     *     idempotency_key?: string|null,
     *     price_tier?: PriceTier|string|null,
     * }  $input  discount_value is pesewas for 'amount' and basis points (1% = 100) for 'percent';
     *            price_tier defaults to retail (ADR-0019)
     *
     * @throws SaleException
     */
    public function create(array $input, User $actor): Sale
    {
        return DB::transaction(function () use ($input, $actor): Sale {
            $key = $input['idempotency_key'] ?? null;

            if ($key !== null && ($existing = Sale::query()->where('idempotency_key', $key)->first())) {
                return $existing;
            }

            $quantities = $this->mergeLines($input['lines'] ?? []);
            $customer = $this->resolveCustomer($input['customer_id'] ?? null);
            $products = $this->lockProducts($quantities);
            $tier = $this->resolveTier($input['price_tier'] ?? null);

            $lines = [];
            $subtotal = 0;

            foreach ($quantities as $productId => $quantity) {
                $product = $products[$productId];
                $price = $product->unitPriceFor($tier, $quantity);
                $lineTotal = $price['pesewas'] * $quantity;
                $subtotal += $lineTotal;

                $lines[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'unit_price_pesewas' => $price['pesewas'],
                    'price_tier' => $price['tier'],
                    'quantity' => $quantity,
                    'line_total_pesewas' => $lineTotal,
                ];
            }

            $discount = $this->discountFor($subtotal, $input['discount_type'] ?? null, (int) ($input['discount_value'] ?? 0), $actor);
            $total = $subtotal - $discount;

            $payment = (int) ($input['payment_pesewas'] ?? 0);
            $method = $this->resolveMethod($input['payment_method'] ?? null);

            if ($payment < 0 || $payment > $total) {
                throw SaleException::overpayment($total);
            }

            if ($payment < $total && $customer === null) {
                throw SaleException::creditNeedsCustomer();
            }

            if ($payment > 0 && $method === null) {
                throw SaleException::paymentMethodRequired();
            }

            $soldAt = now();

            $sale = Sale::query()->create([
                'receipt_number' => $this->receiptNumbers->next(),
                'customer_id' => $customer?->id,
                'sold_by' => $actor->id,
                'price_tier' => $tier,
                'subtotal_pesewas' => $subtotal,
                'discount_pesewas' => $discount,
                'discount_reason' => $discount > 0 ? $this->blankToNull($input['discount_reason'] ?? null) : null,
                'total_pesewas' => $total,
                'paid_pesewas' => 0,
                'status' => SaleStatus::for($total, 0),
                'notes' => $this->blankToNull($input['notes'] ?? null),
                'idempotency_key' => $key,
                'sold_at' => $soldAt,
            ]);

            $sale->lines()->createMany($lines);

            foreach ($quantities as $productId => $quantity) {
                $products[$productId]->decrement('stock_quantity', $quantity);
            }

            if ($payment > 0) {
                $paymentRecord = Payment::query()->create([
                    'customer_id' => $customer?->id,
                    'received_by' => $actor->id,
                    'amount_pesewas' => $payment,
                    'method' => $method,
                    'reference' => $this->blankToNull($input['payment_reference'] ?? null),
                    'received_at' => $soldAt,
                ]);

                PaymentAllocation::query()->create([
                    'payment_id' => $paymentRecord->id,
                    'sale_id' => $sale->id,
                    'amount_pesewas' => $payment,
                ]);

                $sale->forceFill([
                    'paid_pesewas' => $payment,
                    'status' => SaleStatus::for($total, $payment),
                ])->save();
            }

            return $sale->load('lines');
        });
    }

    /**
     * Discount in pesewas for a subtotal, enforcing the cashier limit (config `sales.cashier_max_discount_percent`).
     *
     * @throws SaleException
     */
    public function discountFor(int $subtotal, ?string $type, int $value, User $actor): int
    {
        if ($type === null || $value <= 0) {
            return 0;
        }

        $discount = $type === 'percent' ? Money::percentOf($subtotal, $value) : $value;

        if ($discount > $subtotal) {
            throw SaleException::discountTooLarge();
        }

        $limitPercent = (float) config('sales.cashier_max_discount_percent');
        $limitPesewas = Money::percentOf($subtotal, (int) round($limitPercent * 100));

        if (! $actor->isAdmin() && $discount > $limitPesewas) {
            throw SaleException::discountNeedsAdmin($limitPercent);
        }

        return $discount;
    }

    /**
     * Combines repeated products into one quantity per product.
     *
     * @param  list<array{product_id: string, quantity: int}>  $lines
     * @return array<string, int>
     *
     * @throws SaleException
     */
    private function mergeLines(array $lines): array
    {
        $quantities = [];

        foreach ($lines as $line) {
            $quantity = (int) ($line['quantity'] ?? 0);

            if ($quantity < 1) {
                throw SaleException::invalidQuantity();
            }

            $quantities[$line['product_id']] = ($quantities[$line['product_id']] ?? 0) + $quantity;
        }

        if ($quantities === []) {
            throw SaleException::noLines();
        }

        return $quantities;
    }

    /**
     * Loads and locks the products, checking each is active and has enough stock.
     *
     * @param  array<string, int>  $quantities
     * @return array<string, Product>
     *
     * @throws SaleException
     */
    private function lockProducts(array $quantities): array
    {
        $products = Product::query()
            ->whereKey(array_keys($quantities))
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($quantities as $productId => $quantity) {
            $product = $products->get($productId);

            if ($product === null || ! $product->is_active) {
                throw SaleException::productUnavailable($product?->name ?? 'A product');
            }

            if ($product->stock_quantity < $quantity) {
                throw SaleException::insufficientStock($product->name, $product->stock_quantity);
            }
        }

        return $products->all();
    }

    /**
     * @throws SaleException
     */
    private function resolveCustomer(?string $customerId): ?Customer
    {
        if ($customerId === null || $customerId === '') {
            return null;
        }

        $customer = Customer::query()->find($customerId);

        if ($customer === null || ! $customer->is_active) {
            throw SaleException::customerUnavailable();
        }

        return $customer;
    }

    private function resolveTier(PriceTier|string|null $tier): PriceTier
    {
        if ($tier instanceof PriceTier) {
            return $tier;
        }

        return PriceTier::tryFrom((string) $tier) ?? PriceTier::Retail;
    }

    private function resolveMethod(PaymentMethod|string|null $method): ?PaymentMethod
    {
        if ($method instanceof PaymentMethod) {
            return $method;
        }

        return $method ? PaymentMethod::tryFrom($method) : null;
    }

    private function blankToNull(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
