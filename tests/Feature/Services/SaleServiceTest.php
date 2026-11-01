<?php

namespace Tests\Feature\Services;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Exceptions\Sales\SaleException;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SaleServiceTest extends TestCase
{
    use RefreshDatabase;

    private SaleService $service;

    private User $cashier;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(SaleService::class);
        $this->cashier = User::factory()->cashier()->create();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_a_paid_in_full_walk_in_sale(): void
    {
        $fan = Product::factory()->priced(45000)->create(['name' => 'Fan', 'sku' => 'FN-1', 'stock_quantity' => 10]);

        $sale = $this->service->create([
            'lines' => [['product_id' => $fan->id, 'quantity' => 2]],
            'payment_pesewas' => 90000,
            'payment_method' => 'cash',
        ], $this->cashier);

        $this->assertSame('R-000001', $sale->receipt_number);
        $this->assertNull($sale->customer_id);
        $this->assertSame(90000, $sale->subtotal_pesewas);
        $this->assertSame(90000, $sale->total_pesewas);
        $this->assertSame(90000, $sale->paid_pesewas);
        $this->assertSame(SaleStatus::Paid, $sale->status);
        $this->assertSame(8, $fan->fresh()->stock_quantity);

        $line = $sale->lines->sole();
        $this->assertSame('Fan', $line->product_name);
        $this->assertSame('FN-1', $line->product_sku);
        $this->assertSame(45000, $line->unit_price_pesewas);
        $this->assertSame(90000, $line->line_total_pesewas);

        $payment = Payment::query()->sole();
        $this->assertSame(PaymentMethod::Cash, $payment->method);
        $this->assertSame(90000, $payment->allocations->sole()->amount_pesewas);
        $this->assertTrue($payment->allocations->sole()->sale->is($sale));
    }

    public function test_a_credit_sale_with_a_part_payment(): void
    {
        $customer = Customer::factory()->create();
        $tv = Product::factory()->priced(345000)->create(['stock_quantity' => 5]);

        $sale = $this->service->create([
            'customer_id' => $customer->id,
            'lines' => [['product_id' => $tv->id, 'quantity' => 1]],
            'payment_pesewas' => 100000,
            'payment_method' => PaymentMethod::MobileMoney,
            'payment_reference' => ' MOMO-123 ',
        ], $this->cashier);

        $this->assertSame(SaleStatus::PartlyPaid, $sale->status);
        $this->assertSame(245000, $sale->balancePesewas());
        $this->assertSame('MOMO-123', Payment::query()->sole()->reference);
        $this->assertSame(245000, $customer->outstandingPesewas());
    }

    public function test_a_credit_sale_with_no_payment_creates_no_payment_record(): void
    {
        $customer = Customer::factory()->create();
        $tv = Product::factory()->priced(345000)->create(['stock_quantity' => 5]);

        $sale = $this->service->create(['customer_id' => $customer->id, 'lines' => [['product_id' => $tv->id, 'quantity' => 1]]], $this->cashier);

        $this->assertSame(SaleStatus::Unpaid, $sale->status);
        $this->assertSame(0, Payment::query()->count());
    }

    public function test_walk_in_sales_must_be_paid_in_full(): void
    {
        $fan = Product::factory()->priced(45000)->create(['stock_quantity' => 10]);

        $this->assertSaleFails('customer', fn () => $this->service->create([
            'lines' => [['product_id' => $fan->id, 'quantity' => 1]],
            'payment_pesewas' => 40000,
            'payment_method' => 'cash',
        ], $this->cashier));

        $this->assertSame(10, $fan->fresh()->stock_quantity);
    }

    public function test_payment_cannot_exceed_the_total(): void
    {
        $fan = Product::factory()->priced(45000)->create(['stock_quantity' => 10]);

        $this->assertSaleFails('payment', fn () => $this->service->create([
            'customer_id' => Customer::factory()->create()->id,
            'lines' => [['product_id' => $fan->id, 'quantity' => 1]],
            'payment_pesewas' => 45001,
            'payment_method' => 'cash',
        ], $this->cashier));
    }

    public function test_a_payment_needs_a_method(): void
    {
        $fan = Product::factory()->priced(45000)->create(['stock_quantity' => 10]);

        $this->assertSaleFails('payment', fn () => $this->service->create([
            'lines' => [['product_id' => $fan->id, 'quantity' => 1]],
            'payment_pesewas' => 45000,
        ], $this->cashier));
    }

    public function test_stock_inactive_products_and_inactive_customers_are_checked(): void
    {
        $few = Product::factory()->create(['name' => 'Sofa', 'stock_quantity' => 2]);
        $inactive = Product::factory()->inactive()->create(['stock_quantity' => 10]);
        $customer = Customer::factory()->create();

        try {
            $this->service->create(['customer_id' => $customer->id, 'lines' => [['product_id' => $few->id, 'quantity' => 3]]], $this->cashier);
            $this->fail('Expected insufficient stock.');
        } catch (SaleException $exception) {
            $this->assertSame('Only 2 of Sofa in stock.', $exception->getMessage());
        }

        $this->assertSaleFails('lines', fn () => $this->service->create(['customer_id' => $customer->id, 'lines' => [['product_id' => $inactive->id, 'quantity' => 1]]], $this->cashier));
        $this->assertSaleFails('lines', fn () => $this->service->create(['customer_id' => $customer->id, 'lines' => []], $this->cashier));
        $this->assertSaleFails('lines', fn () => $this->service->create(['customer_id' => $customer->id, 'lines' => [['product_id' => $few->id, 'quantity' => 0]]], $this->cashier));
        $this->assertSaleFails('customer', fn () => $this->service->create([
            'customer_id' => Customer::factory()->inactive()->create()->id,
            'lines' => [['product_id' => $few->id, 'quantity' => 1]],
        ], $this->cashier));

        $this->assertSame(0, Sale::query()->count());
    }

    public function test_repeated_products_are_merged_and_stock_checked_on_the_total(): void
    {
        $fan = Product::factory()->priced(45000)->create(['stock_quantity' => 3]);
        $customer = Customer::factory()->create();

        $sale = $this->service->create(['customer_id' => $customer->id, 'lines' => [
            ['product_id' => $fan->id, 'quantity' => 1],
            ['product_id' => $fan->id, 'quantity' => 2],
        ]], $this->cashier);

        $this->assertSame(3, $sale->lines->sole()->quantity);
        $this->assertSame(0, $fan->fresh()->stock_quantity);

        $this->assertSaleFails('lines', fn () => $this->service->create(['customer_id' => $customer->id, 'lines' => [['product_id' => $fan->id, 'quantity' => 1]]], $this->cashier));
    }

    public function test_amount_and_percentage_discounts(): void
    {
        $customer = Customer::factory()->create();
        $cooker = Product::factory()->priced(275000)->create(['stock_quantity' => 10]);

        // GH₵ 2,750.00 − GH₵ 100.00 = GH₵ 2,650.00
        $amount = $this->service->create([
            'customer_id' => $customer->id,
            'lines' => [['product_id' => $cooker->id, 'quantity' => 1]],
            'discount_type' => 'amount',
            'discount_value' => 10000,
            'discount_reason' => 'Loyal customer',
        ], $this->cashier);
        $this->assertSame(10000, $amount->discount_pesewas);
        $this->assertSame(265000, $amount->total_pesewas);
        $this->assertSame('Loyal customer', $amount->discount_reason);

        // 5% of GH₵ 2,750.00 = GH₵ 137.50 → total GH₵ 2,612.50
        $percent = $this->service->create([
            'customer_id' => $customer->id,
            'lines' => [['product_id' => $cooker->id, 'quantity' => 1]],
            'discount_type' => 'percent',
            'discount_value' => 500,
        ], $this->cashier);
        $this->assertSame(13750, $percent->discount_pesewas);
        $this->assertSame(261250, $percent->total_pesewas);
    }

    public function test_percentage_discounts_round_half_up_to_the_pesewa(): void
    {
        $customer = Customer::factory()->create();
        $microwave = Product::factory()->priced(89999)->create(['stock_quantity' => 10]);

        // 7.5% of GH₵ 899.99 = GH₵ 67.49925 → GH₵ 67.50
        $sale = $this->service->create([
            'customer_id' => $customer->id,
            'lines' => [['product_id' => $microwave->id, 'quantity' => 1]],
            'discount_type' => 'percent',
            'discount_value' => 750,
        ], $this->cashier);

        $this->assertSame(6750, $sale->discount_pesewas);
        $this->assertSame(83249, $sale->total_pesewas);
    }

    public function test_cashier_discounts_are_limited_but_admins_can_go_further(): void
    {
        config(['sales.cashier_max_discount_percent' => 10]);
        $customer = Customer::factory()->create();
        $tv = Product::factory()->priced(100000)->create(['stock_quantity' => 10]);
        $sale = fn (User $actor, int $discount) => $this->service->create([
            'customer_id' => $customer->id,
            'lines' => [['product_id' => $tv->id, 'quantity' => 1]],
            'discount_type' => 'amount',
            'discount_value' => $discount,
        ], $actor);

        $this->assertSame(10000, $sale($this->cashier, 10000)->discount_pesewas);
        $this->assertSaleFails('discount', fn () => $sale($this->cashier, 10001));
        $this->assertSame(50000, $sale($this->admin, 50000)->discount_pesewas);
        $this->assertSaleFails('discount', fn () => $sale($this->admin, 100001));
    }

    public function test_a_full_discount_by_an_admin_is_a_paid_zero_sale(): void
    {
        $tv = Product::factory()->priced(100000)->create(['stock_quantity' => 10]);

        $sale = $this->service->create([
            'lines' => [['product_id' => $tv->id, 'quantity' => 1]],
            'discount_type' => 'percent',
            'discount_value' => 10000,
        ], $this->admin);

        $this->assertSame(0, $sale->total_pesewas);
        $this->assertSame(SaleStatus::Paid, $sale->status);
    }

    public function test_later_price_changes_do_not_change_past_sales(): void
    {
        $tv = Product::factory()->priced(345000)->create(['stock_quantity' => 10]);
        $sale = $this->service->create([
            'customer_id' => Customer::factory()->create()->id,
            'lines' => [['product_id' => $tv->id, 'quantity' => 1]],
        ], $this->cashier);

        $tv->update(['price_pesewas' => 399000, 'name' => 'Renamed TV']);

        $sale->refresh();
        $this->assertSame(345000, $sale->total_pesewas);
        $this->assertSame(345000, $sale->lines->sole()->unit_price_pesewas);
        $this->assertNotSame('Renamed TV', $sale->lines->sole()->product_name);
    }

    public function test_receipt_numbers_are_sequential_and_failed_sales_do_not_use_one(): void
    {
        $fan = Product::factory()->priced(45000)->create(['stock_quantity' => 10]);
        $customer = Customer::factory()->create();
        $make = fn () => $this->service->create(['customer_id' => $customer->id, 'lines' => [['product_id' => $fan->id, 'quantity' => 1]]], $this->cashier);

        $this->assertSame('R-000001', $make()->receipt_number);

        try {
            $this->service->create(['customer_id' => $customer->id, 'lines' => [['product_id' => $fan->id, 'quantity' => 99]]], $this->cashier);
        } catch (SaleException) {
        }

        $this->assertSame('R-000002', $make()->receipt_number);
    }

    public function test_the_same_idempotency_key_returns_the_first_sale(): void
    {
        $fan = Product::factory()->priced(45000)->create(['stock_quantity' => 10]);
        $input = [
            'lines' => [['product_id' => $fan->id, 'quantity' => 1]],
            'payment_pesewas' => 45000,
            'payment_method' => 'cash',
            'idempotency_key' => (string) Str::uuid(),
        ];

        $first = $this->service->create($input, $this->cashier);
        $second = $this->service->create($input, $this->cashier);

        $this->assertTrue($first->is($second));
        $this->assertSame(1, Sale::query()->count());
        $this->assertSame(1, Payment::query()->count());
        $this->assertSame(9, $fan->fresh()->stock_quantity);
    }

    private function assertSaleFails(string $field, callable $attempt): void
    {
        try {
            $attempt();
            $this->fail("Expected a SaleException on {$field}.");
        } catch (SaleException $exception) {
            $this->assertSame($field, $exception->field, $exception->getMessage());
        }
    }
}
