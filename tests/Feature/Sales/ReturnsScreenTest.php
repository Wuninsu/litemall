<?php

namespace Tests\Feature\Sales;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Livewire\Sales\Show;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\User;
use App\Services\ReturnService;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReturnsScreenTest extends TestCase
{
    use RefreshDatabase;

    private function paidSale(int $quantity = 3): Sale
    {
        $fan = Product::factory()->priced(45000)->create(['name' => 'Binatone Fan', 'stock_quantity' => 10]);

        return app(SaleService::class)->create([
            'customer_id' => Customer::factory()->create(['name' => 'Efua Owusu'])->id,
            'lines' => [['product_id' => $fan->id, 'quantity' => $quantity]],
            'payment_pesewas' => 45000 * $quantity,
            'payment_method' => 'cash',
        ], User::factory()->cashier()->create());
    }

    public function test_cashiers_cannot_return_items(): void
    {
        $sale = $this->paidSale();
        $this->actingAs(User::factory()->cashier()->create());

        $this->get(route('sales.show', $sale))->assertOk()->assertSee('Ask an administrator');
        Livewire::test(Show::class, ['sale' => $sale])->call('openReturn')->assertForbidden();
        Livewire::test(Show::class, ['sale' => $sale])->call('saveReturn')->assertForbidden();
    }

    public function test_admins_return_some_items_with_a_preview_and_refund(): void
    {
        $sale = $this->paidSale();
        $line = $sale->lines->sole();
        $this->actingAs(User::factory()->admin()->create(['name' => 'Ama Mensah']));

        Livewire::test(Show::class, ['sale' => $sale])
            ->call('openReturn')
            ->assertSet('showReturnModal', true)
            ->assertSet("restock.{$line->id}", true)
            ->set("returnQuantities.{$line->id}", 1)
            ->assertSee('Value returned')
            ->assertSee('Refund to customer')
            ->assertSee('Record return')
            ->call('saveReturn')
            ->assertHasErrors('reason')
            ->set('reason', 'Faulty motor')
            ->set('refundMethod', 'mobile_money')
            ->set('refundReference', 'MOMO-77')
            ->call('saveReturn')
            ->assertHasNoErrors()
            ->assertSet('showReturnModal', false)
            ->assertSet('notice', 'Items returned (RT-000001). Refund GH₵ 450.00 to the customer.')
            ->assertSee('Returns and cancellations')
            ->assertSee('Faulty motor')
            ->assertSee('1 × Binatone Fan (back to stock)')
            ->assertSee('Refunded GH₵ 450.00 by Mobile Money (MOMO-77)')
            ->assertSee('Net total')
            ->assertSee('1 returned');

        $this->assertSame(45000, $sale->fresh()->returned_pesewas);
    }

    public function test_return_everything_cancels_the_sale(): void
    {
        $sale = $this->paidSale(2);
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(Show::class, ['sale' => $sale])
            ->call('openReturn')
            ->call('returnEverything')
            ->assertSee('the sale will be marked Cancelled')
            ->assertSee('Cancel sale')
            ->set('reason', 'Entered by mistake')
            ->call('saveReturn')
            ->assertHasNoErrors()
            ->assertSet('notice', 'Sale cancelled (RT-000001). Refund GH₵ 900.00 to the customer.')
            ->assertDontSee('wire:click="openReturn"', false);

        $sale->refresh();
        $this->assertSame(SaleStatus::Cancelled, $sale->status);
        $this->assertSame(SaleReturn::TYPE_CANCELLATION, SaleReturn::query()->sole()->type);
        $this->get(route('sales.show', $sale))->assertSee('Cancelled');
    }

    public function test_too_many_units_shows_an_error_in_the_preview_and_on_save(): void
    {
        $sale = $this->paidSale(2);
        $line = $sale->lines->sole();
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(Show::class, ['sale' => $sale])
            ->call('openReturn')
            ->set("returnQuantities.{$line->id}", 5)
            ->assertSee('Only 2 of Binatone Fan can still be returned.')
            ->set('reason', 'Test')
            ->call('saveReturn')
            ->assertHasErrors('lines');

        $this->assertSame(0, SaleReturn::query()->count());
    }

    public function test_dashboard_and_sales_list_use_amounts_after_returns(): void
    {
        $sale = $this->paidSale(2);
        $line = $sale->lines->sole();
        $admin = User::factory()->admin()->create();
        app(ReturnService::class)->process($sale, [$line->id => 1], [$line->id => true], 'Faulty', $admin, PaymentMethod::Cash);

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertSee('GH₵ 450.00'); // sales today and received today, both net of the return/refund
        $this->get(route('sales.index'))->assertSee('GH₵ 450.00');
    }
}
