<?php

namespace Tests\Feature\Sales;

use App\Enums\SaleStatus;
use App\Livewire\Sales\Create;
use App\Livewire\Sales\Index;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SalesScreensTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_open_sales_pages(): void
    {
        $this->get(route('sales.index'))->assertRedirect(route('login'));
        $this->get(route('sales.create'))->assertRedirect(route('login'));
    }

    public function test_cashiers_can_ring_up_a_walk_in_sale(): void
    {
        $this->actingAs(User::factory()->cashier()->create());
        $fan = Product::factory()->priced(45000)->create(['name' => 'Binatone Fan', 'stock_quantity' => 10]);

        $this->get(route('sales.create'))->assertOk()->assertSee('Complete sale');

        $component = Livewire::test(Create::class)
            ->set('productSearch', 'Binatone')
            ->assertSee('Binatone Fan')
            ->call('addProduct', $fan->id)
            ->assertSet("cart.{$fan->id}", 1)
            ->set("cart.{$fan->id}", 2)
            ->assertSee('GH₵ 900.00')
            ->call('payInFull')
            ->assertSet('paymentAmount', '900.00')
            ->call('complete')
            ->assertHasNoErrors();

        $sale = Sale::query()->sole();
        $component->assertRedirect(route('sales.show', $sale));
        $this->assertSame(SaleStatus::Paid, $sale->status);
        $this->assertSame(90000, $sale->total_pesewas);
        $this->assertSame(8, $fan->fresh()->stock_quantity);
    }

    public function test_a_credit_sale_needs_a_customer(): void
    {
        $this->actingAs(User::factory()->cashier()->create());
        $tv = Product::factory()->priced(345000)->create(['stock_quantity' => 5]);
        $customer = Customer::factory()->create(['name' => 'Kwame Asante', 'phone' => '+233244123456']);

        $component = Livewire::test(Create::class)
            ->call('addProduct', $tv->id)
            ->set('paymentAmount', '1,000')
            ->call('complete')
            ->assertHasErrors('customer');

        $this->assertSame(0, Sale::query()->count());

        $component
            ->set('customerSearch', '0244 123')
            ->assertSee('Kwame Asante')
            ->call('selectCustomer', $customer->id)
            ->assertSee('Balance on credit')
            ->call('complete')
            ->assertHasNoErrors();

        $sale = Sale::query()->sole();
        $this->assertTrue($sale->customer->is($customer));
        $this->assertSame(245000, $sale->balancePesewas());
    }

    public function test_discount_limit_is_shown_and_enforced_for_cashiers(): void
    {
        config(['sales.cashier_max_discount_percent' => 10]);
        $this->actingAs(User::factory()->cashier()->create());
        $tv = Product::factory()->priced(100000)->create(['stock_quantity' => 5]);

        Livewire::test(Create::class)
            ->call('addProduct', $tv->id)
            ->set('discountType', 'percent')
            ->set('discountValue', '15')
            ->assertSee('Cashiers can give up to 10% discount')
            ->call('payInFull')
            ->call('complete')
            ->assertHasErrors('discount');

        $this->assertSame(0, Sale::query()->count());
    }

    public function test_out_of_stock_products_cannot_be_added(): void
    {
        $this->actingAs(User::factory()->cashier()->create());
        $tv = Product::factory()->create(['name' => 'Nasco TV', 'stock_quantity' => 0]);
        $customer = Customer::factory()->create();

        Livewire::test(Create::class)
            ->call('selectCustomer', $customer->id)
            ->call('addProduct', $tv->id)
            ->call('complete')
            ->assertHasErrors('lines');
    }

    public function test_the_receipt_page_shows_lines_totals_and_payments(): void
    {
        $cashier = User::factory()->cashier()->create(['name' => 'Kofi Boateng']);
        $customer = Customer::factory()->create(['name' => 'Efua Owusu']);
        $tv = Product::factory()->priced(345000)->create(['name' => 'Samsung TV', 'sku' => 'TV-SAM-43', 'stock_quantity' => 5]);
        $sale = app(SaleService::class)->create([
            'customer_id' => $customer->id,
            'lines' => [['product_id' => $tv->id, 'quantity' => 1]],
            'discount_type' => 'amount',
            'discount_value' => 5000,
            'discount_reason' => 'Promo',
            'payment_pesewas' => 100000,
            'payment_method' => 'mobile_money',
            'payment_reference' => 'MOMO-77',
        ], $cashier);

        $this->actingAs($cashier)->get(route('sales.show', $sale))
            ->assertOk()
            ->assertSee('R-000001')
            ->assertSee('Efua Owusu')
            ->assertSee('Kofi Boateng')
            ->assertSee('Samsung TV')
            ->assertSee('GH₵ 3,450.00')
            ->assertSee('Discount (Promo)')
            ->assertSee('GH₵ 3,400.00')
            ->assertSee('GH₵ 2,400.00')
            ->assertSee('Mobile Money')
            ->assertSee('MOMO-77')
            ->assertSee('Part-paid')
            ->assertSee('Print receipt')
            ->assertSee(route('payments.index', ['customer' => $customer->id]), false);
    }

    public function test_sales_list_filters_and_totals(): void
    {
        $this->actingAs(User::factory()->cashier()->create());
        $customer = Customer::factory()->create(['name' => 'Yaw Darko']);
        Sale::factory()->amounts(100000, 100000)->create(['receipt_number' => 'R-000010', 'sold_at' => now(), 'customer_id' => null]);
        Sale::factory()->amounts(50000, 0)->create(['receipt_number' => 'R-000011', 'sold_at' => now(), 'customer_id' => $customer->id]);
        Sale::factory()->amounts(70000, 0)->create(['receipt_number' => 'R-000001', 'sold_at' => now()->subDays(40), 'customer_id' => $customer->id]);

        Livewire::test(Index::class)
            ->assertSee('R-000010')->assertSee('R-000011')->assertDontSee('R-000001')
            ->assertSee('GH₵ 1,500.00')
            ->assertSee('GH₵ 500.00')
            ->set('status', 'unpaid')->assertSee('R-000011')->assertDontSee('R-000010')
            ->set('status', '')->set('period', '')->assertSee('R-000001')
            ->set('search', 'Yaw')->assertSee('R-000011')->assertDontSee('R-000010');
    }
}
