<?php

namespace Tests\Feature\Payments;

use App\Livewire\Customers\Index as CustomersIndex;
use App\Livewire\Payments\Index;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentsScreenTest extends TestCase
{
    use RefreshDatabase;

    public function test_receiving_a_payment_with_an_allocation_preview(): void
    {
        $this->actingAs(User::factory()->cashier()->create());
        $customer = Customer::factory()->create(['name' => 'Kwame Asante']);
        $old = Sale::factory()->amounts(100000)->create(['customer_id' => $customer->id, 'receipt_number' => 'R-000001', 'sold_at' => '2026-10-01']);
        $new = Sale::factory()->amounts(50000)->create(['customer_id' => $customer->id, 'receipt_number' => 'R-000002', 'sold_at' => '2026-10-15']);

        $this->get(route('payments.index', ['customer' => $customer->id]))->assertOk()->assertSee('Owes GH₵ 1,500.00');

        Livewire::withQueryParams(['customer' => $customer->id])
            ->test(Index::class)
            ->assertSet('showModal', true)
            ->assertSet('customerId', $customer->id)
            ->set('amount', '1,200')
            ->assertSee('Will be applied to')
            ->assertSee('Settled')
            ->assertSee('Part-paid')
            ->set('paymentMethod', 'mobile_money')
            ->set('reference', 'MOMO-55')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showModal', false)
            ->assertSet('notice', 'GH₵ 1,200.00 received from Kwame Asante.')
            ->assertSee('MOMO-55');

        $this->assertSame(100000, $old->fresh()->paid_pesewas);
        $this->assertSame(20000, $new->fresh()->paid_pesewas);
    }

    public function test_only_customers_who_owe_money_are_offered(): void
    {
        $this->actingAs(User::factory()->cashier()->create());
        $owing = Customer::factory()->create(['name' => 'Owing Ama']);
        $settled = Customer::factory()->create(['name' => 'Settled Kofi']);
        Sale::factory()->amounts(100000)->create(['customer_id' => $owing->id]);
        Sale::factory()->amounts(100000, 100000)->create(['customer_id' => $settled->id]);

        Livewire::test(Index::class)
            ->call('openPanel')
            ->assertSee('Owing Ama')
            ->assertDontSee('Settled Kofi');
    }

    public function test_validation_and_overpayment(): void
    {
        $this->actingAs(User::factory()->cashier()->create());
        $customer = Customer::factory()->create();
        Sale::factory()->amounts(100000)->create(['customer_id' => $customer->id]);

        Livewire::test(Index::class)->call('openPanel')->set('amount', '50')->call('save')->assertHasErrors('customerId');

        Livewire::test(Index::class)
            ->call('openPanel')
            ->call('selectCustomer', $customer->id)
            ->set('amount', 'abc')->call('save')->assertHasErrors('amount')
            ->set('amount', '1,000.01')->call('save')->assertHasErrors('amount')
            ->assertSee('The customer only owes GH₵ 1,000.00');

        $this->assertSame(0, Payment::query()->count());
    }

    public function test_full_balance_button_and_dashboard_shortcut(): void
    {
        $this->actingAs(User::factory()->cashier()->create());
        $customer = Customer::factory()->create();
        Sale::factory()->amounts(123450, 3450)->create(['customer_id' => $customer->id]);

        Livewire::test(Index::class)->call('openPanel')->call('selectCustomer', $customer->id)->call('payFullBalance')->assertSet('amount', '1200.00');

        Livewire::withQueryParams(['receive' => 1])->test(Index::class)->assertSet('showModal', true);
    }

    public function test_customers_list_shows_balances_and_owing_filter(): void
    {
        $this->actingAs(User::factory()->cashier()->create());
        $owing = Customer::factory()->create(['name' => 'Owing Ama']);
        Customer::factory()->create(['name' => 'Clear Kofi']);
        Sale::factory()->amounts(250000, 50000)->create(['customer_id' => $owing->id]);

        Livewire::test(CustomersIndex::class)
            ->assertSee('GH₵ 2,000.00')
            ->set('status', 'owing')
            ->assertSee('Owing Ama')
            ->assertDontSee('Clear Kofi');
    }

    public function test_dashboard_money_figures(): void
    {
        $this->actingAs(User::factory()->cashier()->create());
        $customer = Customer::factory()->create();
        Sale::factory()->amounts(300000, 100000)->create(['customer_id' => $customer->id, 'sold_at' => now()]);
        Sale::factory()->amounts(50000, 0)->create(['customer_id' => $customer->id, 'sold_at' => now()->subDays(3)]);
        Payment::factory()->create(['customer_id' => $customer->id, 'amount_pesewas' => 100000, 'received_at' => now()]);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Sales today')
            ->assertSee('GH₵ 3,000.00')
            ->assertSee('GH₵ 1,000.00')
            ->assertSee('GH₵ 2,500.00')
            ->assertSee('1 customer owing');
    }
}
