<?php

namespace Tests\Feature\Customers;

use App\Livewire\Customers\Index;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('customers.index'))->assertRedirect(route('login'));
    }

    public function test_cashiers_and_admins_can_open_the_customer_list(): void
    {
        Customer::factory()->create(['name' => 'Kwame Asante', 'phone' => '+233244123456']);

        foreach ([User::factory()->cashier()->create(), User::factory()->admin()->create()] as $user) {
            $this->actingAs($user)->get(route('customers.index'))
                ->assertOk()
                ->assertSee('Kwame Asante')
                ->assertSee('024 412 3456');
        }
    }

    public function test_search_matches_name_email_and_phone_however_it_is_typed(): void
    {
        $this->actingAs(User::factory()->cashier()->create());
        Customer::factory()->create(['name' => 'Kwame Asante', 'phone' => '+233244123456', 'email' => 'kwame@example.com']);
        Customer::factory()->create(['name' => 'Efua Owusu', 'phone' => '+233554000111', 'email' => null]);

        Livewire::test(Index::class)
            ->set('search', 'Efua')->assertSee('Efua Owusu')->assertDontSee('Kwame Asante')
            ->set('search', 'kwame@')->assertSee('Kwame Asante')->assertDontSee('Efua Owusu')
            ->set('search', '0554 000')->assertSee('Efua Owusu')->assertDontSee('Kwame Asante')
            ->set('search', '+233 24 412')->assertSee('Kwame Asante')->assertDontSee('Efua Owusu');
    }

    public function test_inactive_customers_are_hidden_by_default(): void
    {
        $this->actingAs(User::factory()->cashier()->create());
        Customer::factory()->create(['name' => 'Active Ama']);
        Customer::factory()->inactive()->create(['name' => 'Gone Kofi']);

        Livewire::test(Index::class)
            ->assertSee('Active Ama')->assertDontSee('Gone Kofi')
            ->set('status', 'inactive')->assertSee('Gone Kofi')->assertDontSee('Active Ama')
            ->set('status', '')->assertSee('Gone Kofi')->assertSee('Active Ama');
    }

    public function test_cashiers_can_add_customers_through_the_panel(): void
    {
        $cashier = User::factory()->cashier()->create();
        $this->actingAs($cashier);

        Livewire::test(Index::class)
            ->call('create')
            ->assertSet('showModal', true)
            ->set('form.name', 'Kwame Asante')
            ->set('form.phone', '024 412 3456')
            ->set('form.address', 'Madina, Accra')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showModal', false)
            ->assertSet('notice', 'Kwame Asante was added.')
            ->assertSee('Kwame Asante');

        $this->assertDatabaseHas('customers', [
            'name' => 'Kwame Asante',
            'phone' => '+233244123456',
            'created_by' => $cashier->id,
        ]);
    }

    public function test_validation_errors_keep_the_panel_open(): void
    {
        $this->actingAs(User::factory()->cashier()->create());

        Livewire::test(Index::class)
            ->call('create')
            ->set('form.name', '')
            ->set('form.phone', '12345')
            ->set('form.email', 'not-an-email')
            ->call('save')
            ->assertHasErrors(['form.name', 'form.phone', 'form.email'])
            ->assertSet('showModal', true);
    }

    public function test_a_number_already_used_in_another_format_is_rejected(): void
    {
        $this->actingAs(User::factory()->cashier()->create());
        Customer::factory()->create(['phone' => '+233244123456']);

        Livewire::test(Index::class)
            ->call('create')
            ->set('form.name', 'Duplicate')
            ->set('form.phone', '0244123456')
            ->call('save')
            ->assertHasErrors(['form.phone' => 'unique'])
            ->assertSet('form.phone', '0244123456');

        $this->assertSame(1, Customer::query()->count());
    }

    public function test_the_same_panel_edits_a_customer(): void
    {
        $this->actingAs(User::factory()->cashier()->create());
        $customer = Customer::factory()->create(['name' => 'Efua Owusu', 'phone' => '+233554000111']);

        Livewire::test(Index::class)
            ->call('edit', $customer->id)
            ->assertSet('form.name', 'Efua Owusu')
            ->assertSet('form.phone', '055 400 0111')
            ->assertSee('Save changes')
            ->set('form.name', 'Efua Mensah')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('notice', 'Efua Mensah was updated.');

        $this->assertSame('Efua Mensah', $customer->fresh()->name);
    }

    public function test_cashiers_cannot_deactivate_customers(): void
    {
        $this->actingAs(User::factory()->cashier()->create());
        $customer = Customer::factory()->create();

        Livewire::test(Index::class)
            ->call('edit', $customer->id)
            ->assertDontSee('Deactivate customer')
            ->call('deactivate')
            ->assertForbidden();

        $this->assertTrue($customer->fresh()->is_active);
    }

    public function test_admins_can_deactivate_and_reactivate_customers(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $customer = Customer::factory()->create(['name' => 'Yaw Darko']);

        Livewire::test(Index::class)
            ->call('edit', $customer->id)
            ->call('deactivate')
            ->assertSet('notice', 'Yaw Darko was deactivated.');

        $this->assertFalse($customer->fresh()->is_active);

        Livewire::test(Index::class)
            ->call('edit', $customer->id)
            ->call('activate')
            ->assertSet('notice', 'Yaw Darko was reactivated.');

        $this->assertTrue($customer->fresh()->is_active);
    }

    public function test_the_customer_being_edited_cannot_be_swapped_by_the_client(): void
    {
        $this->actingAs(User::factory()->cashier()->create());
        $customer = Customer::factory()->create();
        $other = Customer::factory()->create();

        $this->expectException(CannotUpdateLockedPropertyException::class);

        Livewire::test(Index::class)->call('edit', $customer->id)->set('form.customer', $other->id);
    }
}
