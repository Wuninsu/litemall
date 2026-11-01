<?php

namespace Tests\Feature\Sales;

use App\Enums\PriceTier;
use App\Exceptions\Products\ProductException;
use App\Livewire\Customers\Index as CustomersIndex;
use App\Livewire\Products\Index as ProductsIndex;
use App\Livewire\Sales\Create;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\ProductService;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WholesalePricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_unit_price_rules(): void
    {
        $fan = Product::factory()->priced(45000)->wholesale(38000, 6)->make();
        $noWholesale = Product::factory()->priced(45000)->make(['wholesale_price_pesewas' => null]);

        $this->assertSame(['pesewas' => 45000, 'tier' => PriceTier::Retail], $fan->unitPriceFor(PriceTier::Retail, 10));
        $this->assertSame(['pesewas' => 45000, 'tier' => PriceTier::Retail], $fan->unitPriceFor(PriceTier::Wholesale, 5));
        $this->assertSame(['pesewas' => 38000, 'tier' => PriceTier::Wholesale], $fan->unitPriceFor(PriceTier::Wholesale, 6));
        $this->assertSame(['pesewas' => 45000, 'tier' => PriceTier::Retail], $noWholesale->unitPriceFor(PriceTier::Wholesale, 100));
    }

    public function test_a_wholesale_sale_prices_each_line_and_records_the_tier(): void
    {
        $customer = Customer::factory()->wholesale()->create();
        $fan = Product::factory()->priced(45000)->wholesale(38000, 6)->create(['stock_quantity' => 50]);
        $tv = Product::factory()->priced(345000)->wholesale(320000, 2)->create(['stock_quantity' => 5]);
        $sofa = Product::factory()->priced(420000)->create(['stock_quantity' => 5, 'wholesale_price_pesewas' => null]);

        $sale = app(SaleService::class)->create([
            'customer_id' => $customer->id,
            'price_tier' => 'wholesale',
            'lines' => [
                ['product_id' => $fan->id, 'quantity' => 10],   // wholesale: 10 × 380 = 3,800
                ['product_id' => $tv->id, 'quantity' => 1],     // below minimum → retail 3,450
                ['product_id' => $sofa->id, 'quantity' => 1],   // no wholesale price → retail 4,200
            ],
        ], User::factory()->cashier()->create());

        $this->assertSame(PriceTier::Wholesale, $sale->price_tier);
        $lines = $sale->lines->keyBy('product_id');
        $this->assertSame([38000, PriceTier::Wholesale], [$lines[$fan->id]->unit_price_pesewas, $lines[$fan->id]->price_tier]);
        $this->assertSame([345000, PriceTier::Retail], [$lines[$tv->id]->unit_price_pesewas, $lines[$tv->id]->price_tier]);
        $this->assertSame([420000, PriceTier::Retail], [$lines[$sofa->id]->unit_price_pesewas, $lines[$sofa->id]->price_tier]);
        $this->assertSame(380000 + 345000 + 420000, $sale->subtotal_pesewas);
    }

    public function test_sales_are_retail_by_default(): void
    {
        $fan = Product::factory()->priced(45000)->wholesale(38000)->create(['stock_quantity' => 50]);

        $sale = app(SaleService::class)->create([
            'lines' => [['product_id' => $fan->id, 'quantity' => 10]],
            'payment_pesewas' => 450000,
            'payment_method' => 'cash',
        ], User::factory()->cashier()->create());

        $this->assertSame(PriceTier::Retail, $sale->price_tier);
        $this->assertSame(45000, $sale->lines->sole()->unit_price_pesewas);
    }

    public function test_later_wholesale_price_changes_do_not_change_past_sales(): void
    {
        $fan = Product::factory()->priced(45000)->wholesale(38000)->create(['stock_quantity' => 50]);
        $sale = app(SaleService::class)->create([
            'customer_id' => Customer::factory()->create()->id,
            'price_tier' => 'wholesale',
            'lines' => [['product_id' => $fan->id, 'quantity' => 2]],
        ], User::factory()->cashier()->create());

        $fan->update(['wholesale_price_pesewas' => 30000]);

        $this->assertSame(38000, $sale->fresh()->lines->sole()->unit_price_pesewas);
    }

    public function test_choosing_a_wholesale_customer_switches_the_pos_to_wholesale(): void
    {
        $this->actingAs(User::factory()->cashier()->create());
        $trader = Customer::factory()->wholesale()->create(['name' => 'Asante Trading']);
        $fan = Product::factory()->priced(45000)->wholesale(38000, 6)->create(['stock_quantity' => 50]);

        $component = Livewire::test(Create::class)
            ->call('addProduct', $fan->id)
            ->set("cart.{$fan->id}", 6)
            ->assertSee('GH₵ 2,700.00')
            ->call('selectCustomer', $trader->id)
            ->assertSet('priceTier', 'wholesale')
            ->assertSee('Wholesale prices apply')
            ->assertSee('GH₵ 2,280.00')
            ->set("cart.{$fan->id}", 5)
            ->assertSee('wholesale from 6')
            ->assertSee('GH₵ 2,250.00')
            ->set("cart.{$fan->id}", 6);

        $component->call('openPayment')->call('complete')->assertHasNoErrors();

        $sale = Sale::query()->sole();
        $this->assertSame(PriceTier::Wholesale, $sale->price_tier);
        $this->assertSame(228000, $sale->total_pesewas);
        $this->assertSame(228000, $sale->paid_pesewas);

        $component->call('newSale')->assertSet('priceTier', 'retail');
    }

    public function test_staff_can_switch_tiers_manually_and_walk_in_goes_back_to_retail(): void
    {
        $this->actingAs(User::factory()->cashier()->create());
        $trader = Customer::factory()->wholesale()->create();

        Livewire::test(Create::class)
            ->call('setPriceTier', 'wholesale')
            ->assertSet('priceTier', 'wholesale')
            ->call('setPriceTier', 'nonsense')
            ->assertSet('priceTier', 'retail')
            ->call('selectCustomer', $trader->id)
            ->assertSet('priceTier', 'wholesale')
            ->call('clearCustomer')
            ->assertSet('priceTier', 'retail');
    }

    public function test_admins_set_wholesale_prices_with_validation(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ProductsIndex::class)
            ->call('create')
            ->set('form.name', 'Fan')
            ->set('form.price', '450')
            ->set('form.wholesalePrice', '500')
            ->call('save')
            ->assertHasErrors('form.wholesalePrice')
            ->set('form.wholesalePrice', '380')
            ->set('form.wholesaleMinQuantity', '0')
            ->call('save')
            ->assertHasErrors('form.wholesaleMinQuantity')
            ->set('form.wholesaleMinQuantity', '6')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('GH₵ 380.00 wholesale · 6+');

        $product = Product::query()->sole();
        $this->assertSame(38000, $product->wholesale_price_pesewas);
        $this->assertSame(6, $product->wholesale_min_quantity);

        Livewire::test(ProductsIndex::class)
            ->call('edit', $product->id)
            ->assertSet('form.wholesalePrice', '380.00')
            ->set('form.wholesalePrice', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull($product->fresh()->wholesale_price_pesewas);
    }

    public function test_the_service_rejects_a_wholesale_price_above_retail(): void
    {
        try {
            app(ProductService::class)->create(['name' => 'Fan', 'price_pesewas' => 45000, 'wholesale_price_pesewas' => 45001], User::factory()->admin()->create());
            $this->fail('Expected ProductException.');
        } catch (ProductException $exception) {
            $this->assertSame('wholesalePrice', $exception->field);
        }
    }

    public function test_customers_can_be_marked_as_wholesale(): void
    {
        $this->actingAs(User::factory()->cashier()->create());

        Livewire::test(CustomersIndex::class)
            ->call('create')
            ->set('form.name', 'Asante Trading')
            ->set('form.phone', '0244000111')
            ->set('form.isWholesale', true)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Wholesale');

        $this->assertTrue(Customer::query()->sole()->is_wholesale);

        Livewire::test(Create::class)
            ->call('openNewCustomer')
            ->set('customerForm.name', 'Kumasi Wholesale')
            ->set('customerForm.phone', '0554000222')
            ->set('customerForm.isWholesale', true)
            ->call('saveNewCustomer')
            ->assertHasNoErrors()
            ->assertSet('priceTier', 'wholesale');
    }

    public function test_receipt_and_list_show_wholesale(): void
    {
        $cashier = User::factory()->cashier()->create();
        $fan = Product::factory()->priced(45000)->wholesale(38000)->create(['name' => 'Binatone Fan', 'stock_quantity' => 50]);
        $sale = app(SaleService::class)->create([
            'customer_id' => Customer::factory()->wholesale()->create()->id,
            'price_tier' => 'wholesale',
            'lines' => [['product_id' => $fan->id, 'quantity' => 3]],
        ], $cashier);

        $this->actingAs($cashier)->get(route('sales.show', $sale))->assertOk()->assertSee('Sales receipt · Wholesale')->assertSee('GH₵ 1,140.00');
        $this->get(route('sales.index'))->assertSee('Wholesale');
    }
}
