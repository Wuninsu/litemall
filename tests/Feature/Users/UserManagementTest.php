<?php

namespace Tests\Feature\Users;

use App\Enums\UserRole;
use App\Livewire\Users\Create;
use App\Livewire\Users\Edit;
use App\Livewire\Users\Index;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashiers_cannot_access_user_management(): void
    {
        $cashier = User::factory()->cashier()->create();
        $other = User::factory()->create();

        $this->actingAs($cashier);

        $this->get(route('users.index'))->assertForbidden();
        $this->get(route('users.create'))->assertForbidden();
        $this->get(route('users.edit', $other))->assertForbidden();
    }

    public function test_cashiers_cannot_invoke_user_management_components_directly(): void
    {
        $this->actingAs(User::factory()->cashier()->create());

        Livewire::test(Create::class)->assertForbidden();
    }

    public function test_admins_can_list_and_search_users(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Admin Person']);
        User::factory()->create(['name' => 'Kwame Boateng']);

        $this->actingAs($admin)->get(route('users.index'))->assertOk()->assertSee('Kwame Boateng');

        Livewire::test(Index::class)
            ->set('search', 'Kwame')
            ->assertSee('Kwame Boateng')
            ->assertDontSee('Admin Person');
    }

    public function test_navigation_shows_users_link_only_to_admins(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get(route('dashboard'))->assertSee(route('users.index'));
        $this->actingAs(User::factory()->cashier()->create())->get(route('dashboard'))->assertDontSee(route('users.index'));
    }

    public function test_admins_can_create_users(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(Create::class)
            ->set('form.name', 'Esi Owusu')
            ->set('form.email', 'Esi@Example.com')
            ->set('form.role', 'cashier')
            ->set('form.password', 'secret-password')
            ->set('form.password_confirmation', 'secret-password')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', ['email' => 'esi@example.com', 'role' => 'cashier', 'is_active' => true]);
    }

    public function test_create_validates_input(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        User::factory()->create(['email' => 'taken@example.com']);

        Livewire::test(Create::class)
            ->set('form.name', '')
            ->set('form.email', 'TAKEN@example.com')
            ->set('form.role', 'superuser')
            ->set('form.password', '')
            ->call('save')
            ->assertHasErrors(['form.name', 'form.email', 'form.role', 'form.password']);
    }

    public function test_admins_can_edit_users(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->cashier()->create();

        $this->actingAs($admin)->get(route('users.edit', $user))->assertOk();

        Livewire::test(Edit::class, ['user' => $user])
            ->assertSet('form.email', $user->email)
            ->set('form.name', 'Updated Name')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('users.index'));

        $this->assertSame('Updated Name', $user->fresh()->name);
    }

    public function test_editing_own_role_shows_an_error(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(Edit::class, ['user' => $admin])
            ->set('form.role', 'cashier')
            ->call('save')
            ->assertHasErrors('form.role');

        $this->assertSame(UserRole::Admin, $admin->fresh()->role);
    }

    public function test_admins_can_deactivate_and_reactivate_users(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $user = User::factory()->cashier()->create();

        Livewire::test(Edit::class, ['user' => $user])
            ->call('deactivate')
            ->assertHasNoErrors()
            ->assertSee('Activate');

        $this->assertFalse($user->fresh()->is_active);

        Livewire::test(Edit::class, ['user' => $user->fresh()])->call('activate');

        $this->assertTrue($user->fresh()->is_active);
    }

    public function test_admins_cannot_deactivate_themselves(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(Edit::class, ['user' => $admin])->call('deactivate')->assertForbidden();

        $this->assertTrue($admin->fresh()->is_active);
    }
}
