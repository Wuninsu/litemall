<?php

namespace Tests\Feature\Products;

use App\Livewire\Products\Index;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashiers_see_products_and_prices_read_only(): void
    {
        Product::factory()->priced(345000)->create(['name' => 'Samsung TV']);
        $this->actingAs(User::factory()->cashier()->create());

        $this->get(route('products.index'))
            ->assertOk()
            ->assertSee('Samsung TV')
            ->assertSee('GH₵ 3,450.00')
            ->assertSee('Prices and stock are set by an administrator.')
            ->assertDontSee('New product');
    }

    public function test_cashiers_cannot_create_or_edit_products(): void
    {
        $this->actingAs(User::factory()->cashier()->create());
        $product = Product::factory()->create();

        Livewire::test(Index::class)->call('create')->assertForbidden();
        Livewire::test(Index::class)->call('edit', $product->id)->assertForbidden();
        Livewire::test(Index::class)->call('save')->assertForbidden();
    }

    public function test_search_category_and_status_filters(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Product::factory()->create(['name' => 'Tecno Spark 20', 'sku' => 'PH-TEC-S20', 'category' => 'Phones']);
        Product::factory()->create(['name' => 'Gas Cooker', 'sku' => 'KT-GAS-4B', 'category' => 'Kitchen']);
        Product::factory()->inactive()->create(['name' => 'Old Radio', 'category' => 'Electronics']);

        Livewire::test(Index::class)
            ->assertSee('Tecno Spark 20')->assertSee('Gas Cooker')->assertDontSee('Old Radio')
            ->set('search', 'KT-GAS')->assertSee('Gas Cooker')->assertDontSee('Tecno Spark 20')
            ->set('search', '')
            ->set('category', 'Phones')->assertSee('Tecno Spark 20')->assertDontSee('Gas Cooker')
            ->set('category', '')
            ->set('status', 'inactive')->assertSee('Old Radio')->assertDontSee('Gas Cooker');
    }

    public function test_admins_can_add_a_product_with_a_cedi_price(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(Index::class)
            ->call('create')
            ->assertSet('showModal', true)
            ->set('form.name', 'LG Refrigerator')
            ->set('form.sku', 'fr-lg-2d')
            ->set('form.category', 'Appliances')
            ->set('form.price', '6,899.50')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showModal', false)
            ->assertSet('notice', 'LG Refrigerator was added.')
            ->assertSee('GH₵ 6,899.50');

        $this->assertDatabaseHas('products', [
            'name' => 'LG Refrigerator',
            'sku' => 'FR-LG-2D',
            'price_pesewas' => 689950,
            'created_by' => $admin->id,
        ]);
    }

    public function test_price_validation(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        foreach (['', 'abc', '12.345', '0', '0.00', '-5'] as $price) {
            Livewire::test(Index::class)
                ->call('create')
                ->set('form.name', 'Thing')
                ->set('form.price', $price)
                ->call('save')
                ->assertHasErrors('form.price');
        }

        $this->assertSame(0, Product::query()->count());
    }

    public function test_duplicate_sku_is_rejected(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Product::factory()->create(['sku' => 'TV-SAM-43']);

        Livewire::test(Index::class)
            ->call('create')
            ->set('form.name', 'Another TV')
            ->set('form.sku', 'tv-sam-43')
            ->set('form.price', '100')
            ->call('save')
            ->assertHasErrors(['form.sku' => 'unique']);
    }

    public function test_editing_prefills_the_price_and_saves_a_new_one(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $product = Product::factory()->priced(189950)->create(['name' => 'Tecno Spark 20']);

        Livewire::test(Index::class)
            ->call('edit', $product->id)
            ->assertSet('form.price', '1899.50')
            ->assertSee('past sales keep the price they were sold at.')
            ->set('form.price', '1,750')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('notice', 'Tecno Spark 20 was updated.');

        $this->assertSame(175000, $product->fresh()->price_pesewas);
    }

    public function test_admins_can_deactivate_and_reactivate_products(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $product = Product::factory()->create(['name' => 'Gas Cooker']);

        Livewire::test(Index::class)->call('edit', $product->id)->call('deactivate')->assertSet('notice', 'Gas Cooker was deactivated.');
        $this->assertFalse($product->fresh()->is_active);

        Livewire::test(Index::class)->call('edit', $product->id)->call('activate')->assertSet('notice', 'Gas Cooker was reactivated.');
        $this->assertTrue($product->fresh()->is_active);
    }

    public function test_sidebar_links_to_customers_and_products(): void
    {
        $this->actingAs(User::factory()->cashier()->create())
            ->get(route('dashboard'))
            ->assertSee(route('customers.index'))
            ->assertSee(route('products.index'))
            ->assertSee('Active customers')
            ->assertSee('Products on sale');
    }
}
