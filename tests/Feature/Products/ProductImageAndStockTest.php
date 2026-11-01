<?php

namespace Tests\Feature\Products;

use App\Exceptions\Products\ProductException;
use App\Livewire\Products\Index;
use App\Livewire\Products\Show;
use App\Models\Product;
use App\Models\User;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProductImageAndStockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Product::IMAGE_DISK);
    }

    public function test_admins_can_add_a_product_with_a_photo_and_stock(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(Index::class)
            ->call('create')
            ->set('form.name', 'Samsung TV')
            ->set('form.price', '3,450')
            ->set('form.stock', '12')
            ->set('form.image', UploadedFile::fake()->image('tv.jpg', 800, 800))
            ->call('save')
            ->assertHasNoErrors();

        $product = Product::query()->sole();
        $this->assertSame(12, $product->stock_quantity);
        $this->assertNotNull($product->image_path);
        $this->assertStringStartsWith(Product::IMAGE_DIRECTORY.'/', $product->image_path);
        Storage::disk(Product::IMAGE_DISK)->assertExists($product->image_path);
        $this->assertNotNull($product->imageUrl());
    }

    public function test_photo_must_be_a_small_image(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        foreach ([
            UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf'),
            UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml'),
            UploadedFile::fake()->image('huge.jpg')->size(3000),
        ] as $file) {
            Livewire::test(Index::class)
                ->call('create')
                ->set('form.name', 'Thing')
                ->set('form.price', '10')
                ->set('form.image', $file)
                ->call('save')
                ->assertHasErrors('form.image');
        }

        $this->assertSame(0, Product::query()->count());
        $this->assertSame([], Storage::disk(Product::IMAGE_DISK)->allFiles(Product::IMAGE_DIRECTORY));
    }

    public function test_stock_must_be_a_whole_non_negative_number(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        foreach (['', '-1', '2.5', 'ten'] as $stock) {
            Livewire::test(Index::class)
                ->call('create')
                ->set('form.name', 'Thing')
                ->set('form.price', '10')
                ->set('form.stock', $stock)
                ->call('save')
                ->assertHasErrors('form.stock');
        }

        $this->assertSame(0, Product::query()->count());
    }

    public function test_replacing_a_photo_deletes_the_old_file(): void
    {
        $service = app(ProductService::class);
        $product = $service->create(['name' => 'Fan', 'price_pesewas' => 45000], User::factory()->admin()->create(), UploadedFile::fake()->image('old.jpg'));
        $oldPath = $product->image_path;

        $service->update($product, ['name' => 'Fan', 'price_pesewas' => 45000], UploadedFile::fake()->image('new.png'));

        Storage::disk(Product::IMAGE_DISK)->assertMissing($oldPath);
        Storage::disk(Product::IMAGE_DISK)->assertExists($product->fresh()->image_path);
        $this->assertNotSame($oldPath, $product->fresh()->image_path);
    }

    public function test_removing_a_photo_deletes_the_file(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $product = app(ProductService::class)->create(['name' => 'Fan', 'price_pesewas' => 45000], User::factory()->admin()->create(), UploadedFile::fake()->image('fan.jpg'));
        $path = $product->image_path;

        Livewire::test(Index::class)
            ->call('edit', $product->id)
            ->set('form.removeImage', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull($product->fresh()->image_path);
        Storage::disk(Product::IMAGE_DISK)->assertMissing($path);
    }

    public function test_a_failed_save_does_not_leave_an_orphaned_photo(): void
    {
        $service = app(ProductService::class);
        $admin = User::factory()->admin()->create();
        $service->create(['name' => 'A', 'sku' => 'DUP-1', 'price_pesewas' => 100], $admin);

        try {
            $service->create(['name' => 'B', 'sku' => 'dup-1', 'price_pesewas' => 100], $admin, UploadedFile::fake()->image('b.jpg'));
            $this->fail('Expected ProductException.');
        } catch (ProductException $exception) {
            $this->assertSame('sku', $exception->field);
        }

        $this->assertSame([], Storage::disk(Product::IMAGE_DISK)->allFiles(Product::IMAGE_DIRECTORY));
    }

    public function test_negative_stock_is_rejected_by_the_service(): void
    {
        try {
            app(ProductService::class)->create(['name' => 'A', 'price_pesewas' => 100, 'stock_quantity' => -3], User::factory()->admin()->create());
            $this->fail('Expected ProductException.');
        } catch (ProductException $exception) {
            $this->assertSame('stock', $exception->field);
        }
    }

    public function test_stock_levels_and_value(): void
    {
        $this->assertSame('out', Product::factory()->make(['stock_quantity' => 0])->stockLevel());
        $this->assertSame('low', Product::factory()->make(['stock_quantity' => 1])->stockLevel());
        $this->assertSame('low', Product::factory()->make(['stock_quantity' => Product::LOW_STOCK_THRESHOLD])->stockLevel());
        $this->assertSame('in', Product::factory()->make(['stock_quantity' => Product::LOW_STOCK_THRESHOLD + 1])->stockLevel());

        // 12 × GH₵ 3,450.00 = GH₵ 41,400.00
        $this->assertSame(4_140_000, Product::factory()->priced(345000)->make(['stock_quantity' => 12])->stockValuePesewas());
    }

    public function test_stock_filter_on_the_list(): void
    {
        $this->actingAs(User::factory()->cashier()->create());
        Product::factory()->create(['name' => 'Plenty Fans', 'stock_quantity' => 40]);
        Product::factory()->create(['name' => 'Few Sofas', 'stock_quantity' => 3]);
        Product::factory()->create(['name' => 'No TVs', 'stock_quantity' => 0]);

        Livewire::test(Index::class)
            ->assertSee('Plenty Fans')->assertSee('Few Sofas')->assertSee('No TVs')
            ->set('stock', 'low')->assertSee('Few Sofas')->assertDontSee('Plenty Fans')->assertDontSee('No TVs')
            ->set('stock', 'out')->assertSee('No TVs')->assertDontSee('Few Sofas');
    }

    public function test_cashiers_can_view_the_details_page_without_edit_controls(): void
    {
        $creator = User::factory()->admin()->create(['name' => 'Ama Mensah']);
        $product = Product::factory()->priced(345000)->create([
            'name' => 'Samsung TV',
            'sku' => 'TV-SAM-43',
            'stock_quantity' => 12,
            'description' => 'Smart TV with two-year warranty',
            'created_by' => $creator->id,
        ]);

        $this->actingAs(User::factory()->cashier()->create())
            ->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('Samsung TV')
            ->assertSee('TV-SAM-43')
            ->assertSee('GH₵ 3,450.00')
            ->assertSee('GH₵ 41,400.00')
            ->assertSee('In stock')
            ->assertSee('Smart TV with two-year warranty')
            ->assertSee('Ama Mensah')
            ->assertDontSee('Edit product');

        Livewire::test(Show::class, ['product' => $product])->call('edit', $product->id)->assertForbidden();
    }

    public function test_admins_can_edit_from_the_details_page(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $product = Product::factory()->priced(100000)->create(['name' => 'Gas Cooker', 'stock_quantity' => 2]);

        Livewire::test(Show::class, ['product' => $product])
            ->assertSee('Edit product')
            ->assertSee('Low stock')
            ->call('edit', $product->id)
            ->assertSet('form.stock', '2')
            ->set('form.stock', '20')
            ->set('form.price', '1,250')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('notice', 'Gas Cooker was updated.')
            ->assertSee('GH₵ 1,250.00')
            ->assertSee('GH₵ 25,000.00');

        $this->assertSame(20, $product->fresh()->stock_quantity);
    }

    public function test_list_rows_link_to_the_details_page_and_unknown_products_404(): void
    {
        $this->actingAs(User::factory()->cashier()->create());
        $product = Product::factory()->create();

        $this->get(route('products.index'))->assertSee(route('products.show', $product));
        $this->get('/products/01a198a9-0000-7000-8000-000000000000')->assertNotFound();
    }
}
