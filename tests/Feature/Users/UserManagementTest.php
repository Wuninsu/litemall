<?php

namespace Tests\Feature\Users;

use App\Enums\UserRole;
use App\Livewire\Users\Index;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashiers_cannot_access_user_management(): void
    {
        $this->actingAs(User::factory()->cashier()->create());

        $this->get(route('users.index'))->assertForbidden();
        Livewire::test(Index::class)->assertForbidden();
    }

    public function test_admins_can_list_search_and_filter_users(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Admin Person']);
        User::factory()->cashier()->create(['name' => 'Kwame Boateng']);
        User::factory()->cashier()->inactive()->create(['name' => 'Esi Appiah']);

        $this->actingAs($admin)->get(route('users.index'))->assertOk()->assertSee('Kwame Boateng');

        Livewire::test(Index::class)
            ->set('search', 'Kwame')
            ->assertSee('Kwame Boateng')
            ->assertDontSee('Admin Person')
            ->set('search', '')
            ->set('role', 'admin')
            ->assertSee('Admin Person')
            ->assertDontSee('Kwame Boateng')
            ->set('role', '')
            ->set('status', 'inactive')
            ->assertSee('Esi Appiah')
            ->assertDontSee('Kwame Boateng');
    }

    public function test_navigation_shows_users_link_only_to_admins(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get(route('dashboard'))->assertSee(route('users.index'));
        $this->actingAs(User::factory()->cashier()->create())->get(route('dashboard'))->assertDontSee(route('users.index'));
    }

    public function test_admins_can_create_users_through_the_modal(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(Index::class)
            ->call('create')
            ->assertSet('showModal', true)
            ->assertSet('form.user', null)
            ->set('form.name', 'Esi Owusu')
            ->set('form.email', 'Esi@Example.com')
            ->set('form.role', 'cashier')
            ->set('form.password', 'secret-password')
            ->set('form.password_confirmation', 'secret-password')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showModal', false)
            ->assertSet('notice', 'Esi Owusu was added.')
            ->assertSee('Esi Owusu');

        $this->assertDatabaseHas('users', ['email' => 'esi@example.com', 'role' => 'cashier', 'is_active' => true]);
    }

    public function test_create_validates_input_and_keeps_the_modal_open(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        User::factory()->create(['email' => 'taken@example.com']);

        Livewire::test(Index::class)
            ->call('create')
            ->set('form.name', '')
            ->set('form.email', 'TAKEN@example.com')
            ->set('form.role', 'superuser')
            ->set('form.password', '')
            ->call('save')
            ->assertHasErrors(['form.name', 'form.email', 'form.role', 'form.password'])
            ->assertSet('showModal', true);
    }

    public function test_the_same_modal_edits_an_existing_user(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $user = User::factory()->cashier()->create(['name' => 'Kofi Boateng']);

        Livewire::test(Index::class)
            ->call('edit', $user->id)
            ->assertSet('showModal', true)
            ->assertSet('form.name', 'Kofi Boateng')
            ->assertSet('form.email', $user->email)
            ->assertSee('Save changes')
            ->set('form.name', 'Kofi Mensah Boateng')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showModal', false)
            ->assertSet('notice', 'Kofi Mensah Boateng was updated.');

        $this->assertSame('Kofi Mensah Boateng', $user->fresh()->name);
    }

    public function test_opening_create_after_edit_starts_with_an_empty_form(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $user = User::factory()->create();

        Livewire::test(Index::class)
            ->call('edit', $user->id)
            ->call('closeModal')
            ->call('create')
            ->assertSet('form.user', null)
            ->assertSet('form.name', '')
            ->assertSet('form.email', '');
    }

    public function test_editing_own_role_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(Index::class)
            ->call('edit', $admin->id)
            ->set('form.role', 'cashier')
            ->call('save')
            ->assertHasErrors('form.role')
            ->assertSet('showModal', true);

        $this->assertSame(UserRole::Admin, $admin->fresh()->role);
    }

    public function test_admins_can_deactivate_and_reactivate_users_from_the_modal(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $user = User::factory()->cashier()->create(['name' => 'Kofi Boateng']);

        Livewire::test(Index::class)
            ->call('edit', $user->id)
            ->call('deactivate')
            ->assertHasNoErrors()
            ->assertSet('showModal', false)
            ->assertSet('notice', 'Kofi Boateng was deactivated and signed out.');

        $this->assertFalse($user->fresh()->is_active);

        Livewire::test(Index::class)
            ->call('edit', $user->id)
            ->call('activate')
            ->assertSet('notice', 'Kofi Boateng was reactivated.');

        $this->assertTrue($user->fresh()->is_active);
    }

    public function test_admins_cannot_deactivate_themselves(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(Index::class)->call('edit', $admin->id)->call('deactivate')->assertForbidden();

        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_the_user_being_edited_cannot_be_swapped_by_the_client(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->expectException(CannotUpdateLockedPropertyException::class);

        Livewire::test(Index::class)->call('edit', $user->id)->set('form.user', $other->id);
    }
}
