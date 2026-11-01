<?php

namespace Tests\Feature\Services;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Exceptions\Payments\PaymentException;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private PaymentService $service;

    private User $cashier;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PaymentService::class);
        $this->cashier = User::factory()->cashier()->create();
        $this->customer = Customer::factory()->create();
    }

    public function test_payments_settle_the_oldest_sale_first(): void
    {
        // Owes GH₵ 1,000 (3 Oct), GH₵ 500 of GH₵ 800 (10 Oct), GH₵ 2,000 (20 Oct): GH₵ 3,500 in total.
        $oldest = $this->sale(100000, 0, '2026-10-03');
        $middle = $this->sale(80000, 30000, '2026-10-10');
        $newest = $this->sale(200000, 0, '2026-10-20');

        $this->assertSame(350000, $this->service->outstandingFor($this->customer));

        // GH₵ 1,800 → GH₵ 1,000 to the oldest, GH₵ 500 to the middle, GH₵ 300 to the newest.
        $payment = $this->service->receive($this->customer, 180000, PaymentMethod::Cash, $this->cashier);

        $this->assertSame([100000, 50000, 30000], $payment->allocations->sortBy(fn ($allocation) => $allocation->sale->sold_at)->pluck('amount_pesewas')->values()->all());
        $this->assertSame(180000, $payment->allocations->sum('amount_pesewas'));

        $this->assertSame(SaleStatus::Paid, $oldest->fresh()->status);
        $this->assertSame(SaleStatus::Paid, $middle->fresh()->status);
        $this->assertSame(SaleStatus::PartlyPaid, $newest->fresh()->status);
        $this->assertSame(170000, $newest->fresh()->balancePesewas());
        $this->assertSame(170000, $this->service->outstandingFor($this->customer));
    }

    public function test_preview_matches_what_is_saved(): void
    {
        $this->sale(100000, 0, '2026-10-03');
        $this->sale(50000, 0, '2026-10-04');

        $preview = $this->service->previewAllocation($this->customer, 120000);

        $this->assertSame([100000, 20000], array_column($preview, 'amount'));
        $this->assertSame(0, Payment::query()->count());
    }

    public function test_paying_the_exact_balance_settles_everything(): void
    {
        $this->sale(100000, 25000, '2026-10-03');
        $this->sale(50000, 0, '2026-10-04');

        $this->service->receive($this->customer, 125000, PaymentMethod::BankTransfer, $this->cashier, ' TRF-1 ');

        $this->assertSame(0, $this->service->outstandingFor($this->customer));
        $this->assertSame(2, Sale::query()->where('status', SaleStatus::Paid)->count());
        $this->assertSame('TRF-1', Payment::query()->sole()->reference);
    }

    public function test_overpayment_and_bad_amounts_are_refused(): void
    {
        $this->sale(100000, 0, '2026-10-03');

        foreach ([0, -100, 100001] as $amount) {
            try {
                $this->service->receive($this->customer, $amount, PaymentMethod::Cash, $this->cashier);
                $this->fail('Expected PaymentException.');
            } catch (PaymentException) {
            }
        }

        $this->assertSame(0, Payment::query()->count());
        $this->assertSame(100000, $this->service->outstandingFor($this->customer));
    }

    public function test_a_customer_who_owes_nothing_cannot_pay(): void
    {
        $this->sale(100000, 100000, '2026-10-03');

        $this->expectExceptionObject(PaymentException::nothingOwed());

        $this->service->receive($this->customer, 100, PaymentMethod::Cash, $this->cashier);
    }

    public function test_other_customers_sales_are_never_touched(): void
    {
        $other = Customer::factory()->create();
        $otherSale = Sale::factory()->amounts(100000)->create(['customer_id' => $other->id, 'sold_at' => '2026-01-01']);
        $this->sale(100000, 0, '2026-10-03');

        $this->service->receive($this->customer, 100000, PaymentMethod::Cash, $this->cashier);

        $this->assertSame(0, $otherSale->fresh()->paid_pesewas);
    }

    public function test_the_same_idempotency_key_records_one_payment(): void
    {
        $this->sale(100000, 0, '2026-10-03');
        $key = (string) Str::uuid();

        $first = $this->service->receive($this->customer, 40000, PaymentMethod::Cash, $this->cashier, idempotencyKey: $key);
        $second = $this->service->receive($this->customer, 40000, PaymentMethod::Cash, $this->cashier, idempotencyKey: $key);

        $this->assertTrue($first->is($second));
        $this->assertSame(60000, $this->service->outstandingFor($this->customer));
    }

    private function sale(int $total, int $paid, string $soldAt): Sale
    {
        return Sale::factory()->amounts($total, $paid)->create(['customer_id' => $this->customer->id, 'sold_at' => $soldAt]);
    }
}
