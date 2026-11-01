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

        $this->get(route('sales.create'))->assertOk()->assertSee('Complete sale')->assertSee('Point of sale')->assertSee('Binatone Fan');

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
        $component->assertSet('completedSaleId', $sale->id)->assertSee('Sale complete')->assertSee($sale->receipt_number);
        $this->assertSame(SaleStatus::Paid, $sale->status);
        $this->assertSame(90000, $sale->total_pesewas);
        $this->assertSame(8, $fan->fresh()->stock_quantity);

        $component->call('newSale')
            ->assertSet('completedSaleId', null)
            ->assertSet('cart', [])
            ->assertSet('paymentAmount', '')
            ->assertDontSee('Sale complete');
    }

    public function test_cash_over_the_total_is_given_back_as_change(): void
    {
        $this->actingAs(User::factory()->cashier()->create());
        $fan = Product::factory()->priced(45000)->create(['stock_quantity' => 10]);

        Livewire::test(Create::class)
            ->call('addProduct', $fan->id)
            ->assertSet('quickCashOptions', [45000, 50000, 60000])
            ->call('setPaymentAmount', 50000)
            ->assertSee('Change due')
            ->call('complete')
            ->assertHasNoErrors()
            ->assertSet('changeDue', 5000)
            ->assertSee('GH₵ 50.00');

        $sale = Sale::query()->sole();
        $this->assertSame(45000, $sale->paid_pesewas);
        $this->assertSame(45000, $sale->allocations()->sum('amount_pesewas'));
    }

    public function test_non_cash_payments_above_the_total_are_refused(): void
    {
        $this->actingAs(User::factory()->cashier()->create());
        $fan = Product::factory()->priced(45000)->create(['stock_quantity' => 10]);

        Livewire::test(Create::class)
            ->call('addProduct', $fan->id)
            ->set('paymentMethod', 'mobile_money')
            ->set('paymentAmount', '500')
            ->call('complete')
            ->assertHasErrors('payment');

        $this->assertSame(0, Sale::query()->count());
    }

    public function test_enter_adds_by_exact_sku_and_categories_filter_the_grid(): void
    {
        $this->actingAs(User::factory()->cashier()->create());
        $tv = Product::factory()->create(['name' => 'Samsung TV', 'sku' => 'TV-SAM-43', 'category' => 'Electronics', 'stock_quantity' => 3]);
        Product::factory()->create(['name' => 'Gas Cooker', 'sku' => 'KT-GAS-4B', 'category' => 'Kitchen', 'stock_quantity' => 3]);

        Livewire::test(Create::class)
            ->assertSee('Samsung TV')->assertSee('Gas Cooker')
            ->call('$set', 'category', 'Kitchen')
            ->assertSee('Gas Cooker')->assertDontSee('Samsung TV')
            ->set('category', '')
            ->set('productSearch', 'tv-sam-43')
            ->call('addFirstMatch')
            ->assertSet("cart.{$tv->id}", 1)
            ->assertSet('productSearch', '');
    }

    public function test_charge_opens_the_payment_dialog_with_the_total_filled_in(): void
    {
        $this->actingAs(User::factory()->cashier()->create());
        $fan = Product::factory()->priced(45000)->create(['stock_quantity' => 5]);

        Livewire::test(Create::class)
            ->call('openPayment')
            ->assertHasErrors('cart')
            ->assertSet('showPaymentModal', false)
            ->call('addProduct', $fan->id)
            ->call('openPayment')
            ->assertHasNoErrors()
            ->assertSet('showPaymentModal', true)
            ->assertSet('paymentAmount', '450.00')
            ->assertSee('Take payment')
            ->assertSee('Amount due')
            ->set('paymentMethod', 'mobile_money')
            ->set('paymentReference', 'MOMO-9')
            ->call('complete')
            ->assertHasNoErrors()
            ->assertSet('showPaymentModal', false)
            ->assertSee('Sale complete');

        $this->assertSame('MOMO-9', Sale::query()->sole()->allocations->sole()->payment->reference);
    }

    public function test_no_customer_means_a_walk_in_sale(): void
    {
        $this->actingAs(User::factory()->cashier()->create());
        $fan = Product::factory()->priced(45000)->create(['stock_quantity' => 5]);

        Livewire::test(Create::class)
            ->assertSee('Walk-in customer')
            ->assertSee('Pays in full now')
            ->call('addProduct', $fan->id)
            ->call('payInFull')
            ->call('complete')
            ->assertHasNoErrors();

        $this->assertNull(Sale::query()->sole()->customer_id);
    }

    public function test_a_new_customer_can_be_added_from_the_pos_without_leaving_it(): void
    {
        $cashier = User::factory()->cashier()->create();
        $this->actingAs($cashier);
        $tv = Product::factory()->priced(345000)->create(['stock_quantity' => 5]);

        $component = Livewire::test(Create::class)
            ->call('addProduct', $tv->id)
            ->set('customerSearch', '024 412 3456')
            ->call('openNewCustomer')
            ->assertSet('showCustomerModal', true)
            ->assertSet('customerForm.phone', '024 412 3456')
            ->set('customerForm.name', 'Akua Serwaa')
            ->call('saveNewCustomer')
            ->assertHasNoErrors()
            ->assertSet('showCustomerModal', false)
            ->assertNoRedirect()
            ->assertSee('Akua Serwaa');

        $customer = Customer::query()->where('phone', '+233244123456')->sole();
        $this->assertSame($cashier->id, $customer->created_by);
        $component->assertSet('customerId', $customer->id)
            ->assertSet("cart.{$tv->id}", 1)
            ->set('paymentAmount', '1000')
            ->call('complete')
            ->assertHasNoErrors();

        $this->assertTrue(Sale::query()->sole()->customer->is($customer));
    }

    public function test_typed_names_prefill_the_new_customer_name(): void
    {
        $this->actingAs(User::factory()->cashier()->create());

        Livewire::test(Create::class)
            ->set('customerSearch', 'kofi mensah')
            ->call('openNewCustomer')
            ->assertSet('customerForm.name', 'Kofi Mensah')
            ->assertSet('customerForm.phone', '');
    }

    public function test_new_customer_modal_validates_and_rejects_a_used_number(): void
    {
        $this->actingAs(User::factory()->cashier()->create());
        Customer::factory()->create(['phone' => '+233244123456']);

        Livewire::test(Create::class)
            ->call('openNewCustomer')
            ->call('saveNewCustomer')
            ->assertHasErrors(['customerForm.name', 'customerForm.phone'])
            ->set('customerForm.name', 'Duplicate')
            ->set('customerForm.phone', '0244123456')
            ->call('saveNewCustomer')
            ->assertHasErrors(['customerForm.phone' => 'unique'])
            ->assertSet('showCustomerModal', true)
            ->assertSet('customerId', null);

        $this->assertSame(1, Customer::query()->count());
    }

    public function test_steppers_respect_stock_and_remove_at_zero(): void
    {
        $this->actingAs(User::factory()->cashier()->create());
        $sofa = Product::factory()->create(['stock_quantity' => 2]);

        Livewire::test(Create::class)
            ->call('addProduct', $sofa->id)
            ->call('increment', $sofa->id)
            ->call('increment', $sofa->id)
            ->assertSet("cart.{$sofa->id}", 2)
            ->call('decrement', $sofa->id)
            ->call('decrement', $sofa->id)
            ->assertSet('cart', []);
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
            ->assertSee('Out of stock')
            ->call('selectCustomer', $customer->id)
            ->call('addProduct', $tv->id)
            ->assertSet('cart', [])
            ->call('complete')
            ->assertHasErrors('cart');

        $this->assertSame(0, Sale::query()->count());
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
