<?php

namespace Tests\Feature\Services;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Exceptions\Sales\SaleReturnException;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\User;
use App\Services\PaymentService;
use App\Services\ReturnService;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReturnServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReturnService $returns;

    private User $admin;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->returns = app(ReturnService::class);
        $this->admin = User::factory()->admin()->create();
        $this->customer = Customer::factory()->create();
    }

    public function test_returning_items_from_a_paid_sale_refunds_and_restocks(): void
    {
        $fan = Product::factory()->priced(45000)->create(['stock_quantity' => 10]);
        $sale = $this->sell([[$fan, 3]], paid: 135000);
        $line = $sale->lines->sole();

        $return = $this->returns->process($sale, [$line->id => 1], [$line->id => true], 'Faulty motor', $this->admin, PaymentMethod::Cash);

        $this->assertSame('RT-000001', $return->return_number);
        $this->assertSame(SaleReturn::TYPE_RETURN, $return->type);
        $this->assertSame(45000, $return->credit_pesewas);
        $this->assertSame(0, $return->debt_reduction_pesewas);
        $this->assertSame(45000, $return->refund_pesewas);
        $this->assertSame(PaymentMethod::Cash, $return->refund_method);

        $sale->refresh();
        $this->assertSame(45000, $sale->returned_pesewas);
        $this->assertSame(90000, $sale->paid_pesewas);
        $this->assertSame(90000, $sale->netTotalPesewas());
        $this->assertSame(0, $sale->balancePesewas());
        $this->assertSame(SaleStatus::Paid, $sale->status);
        $this->assertSame(1, $line->fresh()->returned_quantity);
        $this->assertSame(8, $fan->fresh()->stock_quantity); // 10 − 3 sold + 1 back
    }

    public function test_returning_from_an_unpaid_credit_sale_only_reduces_the_debt(): void
    {
        $tv = Product::factory()->priced(100000)->create(['stock_quantity' => 5]);
        $sale = $this->sell([[$tv, 2]]);
        $line = $sale->lines->sole();

        $return = $this->returns->process($sale, [$line->id => 1], [$line->id => false], 'Changed mind', $this->admin);

        $this->assertSame(100000, $return->debt_reduction_pesewas);
        $this->assertSame(0, $return->refund_pesewas);
        $this->assertNull($return->refund_method);
        $this->assertSame(100000, $sale->fresh()->balancePesewas());
        $this->assertSame(SaleStatus::Unpaid, $sale->fresh()->status);
        $this->assertSame(3, $tv->fresh()->stock_quantity); // not restocked
        $this->assertSame(100000, $this->customer->outstandingPesewas());
    }

    public function test_the_debt_is_reduced_first_then_the_rest_is_refunded(): void
    {
        // Total GH₵ 1,000, paid GH₵ 400 (owes 600). Return items worth GH₵ 800 → debt −600, refund 200.
        $item = Product::factory()->priced(20000)->create(['stock_quantity' => 10]);
        $sale = $this->sell([[$item, 5]], paid: 40000);
        $line = $sale->lines->sole();

        $return = $this->returns->process($sale, [$line->id => 4], [$line->id => true], 'Order reduced', $this->admin, PaymentMethod::MobileMoney, 'MOMO-1');

        $this->assertSame([80000, 60000, 20000], [$return->credit_pesewas, $return->debt_reduction_pesewas, $return->refund_pesewas]);
        $this->assertSame('MOMO-1', $return->refund_reference);

        $sale->refresh();
        $this->assertSame(20000, $sale->netTotalPesewas());
        $this->assertSame(20000, $sale->paid_pesewas);
        $this->assertSame(0, $sale->balancePesewas());
        $this->assertSame(SaleStatus::Paid, $sale->status);
    }

    public function test_a_refund_needs_a_method(): void
    {
        $fan = Product::factory()->priced(45000)->create(['stock_quantity' => 10]);
        $sale = $this->sell([[$fan, 1]], paid: 45000);
        $line = $sale->lines->sole();

        $this->assertReturnFails('refundMethod', fn () => $this->returns->process($sale, [$line->id => 1], [], 'Faulty', $this->admin));
        $this->assertSame(0, SaleReturn::query()->count());
    }

    public function test_returning_everything_cancels_the_sale_with_no_pesewa_left(): void
    {
        // Subtotal 3 × 333.33 = 999.99, discount 100.00 → total 899.99. Proportional values would round; the cancel must credit exactly 899.99.
        $item = Product::factory()->priced(33333)->create(['stock_quantity' => 10]);
        $sale = app(SaleService::class)->create([
            'customer_id' => $this->customer->id,
            'lines' => [['product_id' => $item->id, 'quantity' => 3]],
            'discount_type' => 'amount',
            'discount_value' => 10000,
            'payment_pesewas' => 50000,
            'payment_method' => 'cash',
        ], $this->admin);
        $line = $sale->lines->sole();

        $first = $this->returns->process($sale, [$line->id => 1], [$line->id => true], 'One faulty', $this->admin);
        $this->assertSame(30000, $first->credit_pesewas); // 333.33 × 899.99 / 999.99 = 299.997 → 300.00

        $last = $this->returns->process($sale->fresh(), [$line->id => 2], [$line->id => true], 'Rest not wanted', $this->admin, PaymentMethod::Cash);

        $this->assertSame(SaleReturn::TYPE_CANCELLATION, $last->type);
        $this->assertSame(89999 - 30000, $last->credit_pesewas);

        $sale->refresh();
        $this->assertSame(89999, $sale->returned_pesewas);
        $this->assertSame(0, $sale->netTotalPesewas());
        $this->assertSame(0, $sale->paid_pesewas);
        $this->assertSame(0, $sale->balancePesewas());
        $this->assertSame(SaleStatus::Cancelled, $sale->status);
        $this->assertSame(50000, SaleReturn::query()->sum('refund_pesewas'));
        $this->assertSame(10, $item->fresh()->stock_quantity);

        $this->assertReturnFails('lines', fn () => $this->returns->process($sale, [$line->id => 1], [], 'Again', $this->admin));
    }

    public function test_quantity_reason_and_line_checks(): void
    {
        $fan = Product::factory()->priced(45000)->create(['stock_quantity' => 10]);
        $sale = $this->sell([[$fan, 2]]);
        $line = $sale->lines->sole();
        $other = $this->sell([[$fan, 1]])->lines->sole();

        $this->assertReturnFails('lines', fn () => $this->returns->process($sale, [$line->id => 3], [], 'Too many', $this->admin));
        $this->assertReturnFails('lines', fn () => $this->returns->process($sale, [], [], 'Nothing', $this->admin));
        $this->assertReturnFails('lines', fn () => $this->returns->process($sale, [$other->id => 1], [], 'Wrong sale', $this->admin));
        $this->assertReturnFails('reason', fn () => $this->returns->process($sale, [$line->id => 1], [], '   ', $this->admin));

        $this->returns->process($sale, [$line->id => 1], [], 'First', $this->admin);
        $this->assertReturnFails('lines', fn () => $this->returns->process($sale->fresh(), [$line->id => 2], [], 'Second', $this->admin));
    }

    public function test_the_same_idempotency_key_records_one_return(): void
    {
        $fan = Product::factory()->priced(45000)->create(['stock_quantity' => 10]);
        $sale = $this->sell([[$fan, 2]]);
        $line = $sale->lines->sole();
        $key = (string) Str::uuid();

        $first = $this->returns->process($sale, [$line->id => 1], [$line->id => true], 'Faulty', $this->admin, idempotencyKey: $key);
        $second = $this->returns->process($sale, [$line->id => 1], [$line->id => true], 'Faulty', $this->admin, idempotencyKey: $key);

        $this->assertTrue($first->is($second));
        $this->assertSame(1, $line->fresh()->returned_quantity);
        $this->assertSame(9, $fan->fresh()->stock_quantity);
    }

    public function test_payments_after_a_return_use_the_reduced_balance(): void
    {
        $item = Product::factory()->priced(50000)->create(['stock_quantity' => 10]);
        $old = $this->sell([[$item, 2]], soldAt: '2026-10-01'); // owes 1,000
        $new = $this->sell([[$item, 1]], soldAt: '2026-10-05'); // owes 500
        $oldLine = $old->lines->sole();

        $this->returns->process($old, [$oldLine->id => 2], [$oldLine->id => true], 'Cancelled order', $this->admin);

        $this->assertSame(SaleStatus::Cancelled, $old->fresh()->status);
        $this->assertSame(50000, app(PaymentService::class)->outstandingFor($this->customer));
        $this->assertSame([$new->id], Sale::query()->outstanding()->pluck('id')->all());

        $payment = app(PaymentService::class)->receive($this->customer, 50000, PaymentMethod::Cash, $this->admin);
        $this->assertTrue($payment->allocations->sole()->sale->is($new));
        $this->assertSame(SaleStatus::Paid, $new->fresh()->status);
    }

    public function test_preview_matches_what_is_saved(): void
    {
        $item = Product::factory()->priced(20000)->create(['stock_quantity' => 10]);
        $sale = $this->sell([[$item, 5]], paid: 40000);
        $line = $sale->lines->sole();

        $preview = $this->returns->preview($sale, [$line->id => 4]);

        $this->assertSame([80000, 60000, 20000, false], [$preview['credit'], $preview['debtReduction'], $preview['refund'], $preview['isCancellation']]);
        $this->assertSame(0, SaleReturn::query()->count());
    }

    /**
     * @param  list<array{0: Product, 1: int}>  $items
     */
    private function sell(array $items, int $paid = 0, ?string $soldAt = null): Sale
    {
        $sale = app(SaleService::class)->create([
            'customer_id' => $this->customer->id,
            'lines' => array_map(fn ($item) => ['product_id' => $item[0]->id, 'quantity' => $item[1]], $items),
            'payment_pesewas' => $paid,
            'payment_method' => $paid > 0 ? 'cash' : null,
        ], $this->admin);

        if ($soldAt !== null) {
            $sale->forceFill(['sold_at' => $soldAt])->save();
        }

        return $sale->load('lines');
    }

    private function assertReturnFails(string $field, callable $attempt): void
    {
        try {
            $attempt();
            $this->fail("Expected a SaleReturnException on {$field}.");
        } catch (SaleReturnException $exception) {
            $this->assertSame($field, $exception->field, $exception->getMessage());
        }
    }
}
