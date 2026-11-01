<?php

namespace Tests\Feature\Services;

use App\Exceptions\Products\ProductException;
use App\Models\Product;
use App\Models\User;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProductService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ProductService::class);
    }

    public function test_create_normalises_input_and_stores_price_in_pesewas(): void
    {
        $admin = User::factory()->admin()->create();

        $product = $this->service->create([
            'name' => '  Samsung  43-inch TV ',
            'sku' => ' tv-sam-43 ',
            'category' => ' electronics ',
            'price_pesewas' => 345000,
            'description' => '',
        ], $admin);

        $this->assertTrue(Str::isUuid($product->id));
        $this->assertSame('Samsung 43-inch TV', $product->name);
        $this->assertSame('TV-SAM-43', $product->sku);
        $this->assertSame('Electronics', $product->category);
        $this->assertSame(345000, $product->price_pesewas);
        $this->assertSame('GH₵ 3,450.00', $product->formattedPrice());
        $this->assertNull($product->description);
        $this->assertTrue($product->creator->is($admin));
    }

    public function test_blank_sku_is_stored_as_null_so_many_products_can_have_none(): void
    {
        $admin = User::factory()->admin()->create();

        $first = $this->service->create(['name' => 'A', 'sku' => '', 'price_pesewas' => 100], $admin);
        $second = $this->service->create(['name' => 'B', 'sku' => '  ', 'price_pesewas' => 100], $admin);

        $this->assertNull($first->sku);
        $this->assertNull($second->sku);
    }

    public function test_zero_or_negative_prices_are_rejected(): void
    {
        $admin = User::factory()->admin()->create();

        foreach ([0, -100] as $pesewas) {
            try {
                $this->service->create(['name' => 'Free thing', 'price_pesewas' => $pesewas], $admin);
                $this->fail('Expected ProductException.');
            } catch (ProductException $exception) {
                $this->assertSame('price', $exception->field);
            }
        }

        $this->assertSame(0, Product::query()->count());
    }

    public function test_duplicate_skus_are_rejected_case_insensitively(): void
    {
        $admin = User::factory()->admin()->create();
        $this->service->create(['name' => 'A', 'sku' => 'ABC-1', 'price_pesewas' => 100], $admin);

        try {
            $this->service->create(['name' => 'B', 'sku' => 'abc-1', 'price_pesewas' => 100], $admin);
            $this->fail('Expected ProductException.');
        } catch (ProductException $exception) {
            $this->assertSame('sku', $exception->field);
        }
    }

    public function test_update_changes_the_current_price(): void
    {
        $product = Product::factory()->priced(100000)->create();

        $this->service->update($product, ['name' => $product->name, 'sku' => $product->sku, 'price_pesewas' => 125050]);

        $this->assertSame(125050, $product->fresh()->price_pesewas);
    }

    public function test_categories_are_distinct_and_sorted(): void
    {
        Product::factory()->create(['category' => 'Phones']);
        Product::factory()->create(['category' => 'Appliances']);
        Product::factory()->create(['category' => 'Phones']);
        Product::factory()->create(['category' => null]);

        $this->assertSame(['Appliances', 'Phones'], $this->service->categories());
    }

    public function test_deactivate_and_activate(): void
    {
        $product = Product::factory()->create();

        $this->service->deactivate($product);
        $this->assertFalse($product->fresh()->is_active);

        $this->service->activate($product);
        $this->assertTrue($product->fresh()->is_active);
    }
}
