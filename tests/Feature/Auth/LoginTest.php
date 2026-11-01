<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders_for_guests(): void
    {
        $this->get(route('login'))->assertOk()->assertSeeLivewire(Login::class);
    }

    public function test_authenticated_users_are_redirected_away_from_login(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('login'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_guests_are_redirected_to_login_from_the_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_users_can_sign_in_with_valid_credentials(): void
    {
        $user = User::factory()->create(['email' => 'ama@example.com']);

        Livewire::test(Login::class)
            ->set('email', 'ama@example.com')
            ->set('password', 'password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_email_matching_is_case_insensitive(): void
    {
        $user = User::factory()->create(['email' => 'kofi@example.com']);

        Livewire::test(Login::class)
            ->set('email', '  KOFI@Example.com ')
            ->set('password', 'password')
            ->call('login')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_is_rejected(): void
    {
        User::factory()->create(['email' => 'ama@example.com']);

        Livewire::test(Login::class)
            ->set('email', 'ama@example.com')
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertHasErrors('email')
            ->assertSet('password', '');

        $this->assertGuest();
    }

    public function test_unknown_email_gets_the_same_error_as_a_wrong_password(): void
    {
        Livewire::test(Login::class)
            ->set('email', 'nobody@example.com')
            ->set('password', 'password')
            ->call('login')
            ->assertHasErrors(['email' => __('auth.failed')]);

        $this->assertGuest();
    }

    public function test_inactive_users_cannot_sign_in(): void
    {
        User::factory()->inactive()->create(['email' => 'ama@example.com']);

        Livewire::test(Login::class)
            ->set('email', 'ama@example.com')
            ->set('password', 'password')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_is_throttled_after_five_failed_attempts(): void
    {
        $user = User::factory()->create(['email' => 'ama@example.com']);

        $component = Livewire::test(Login::class)->set('email', 'ama@example.com');

        foreach (range(1, 5) as $attempt) {
            $component->set('password', 'wrong-password')->call('login');
        }

        $component->set('password', 'password')->call('login')->assertHasErrors('email');

        $this->assertGuest();
        $this->assertStringContainsString('Too many login attempts', $component->errors()->first('email'));
        $this->assertNull($user->fresh()->last_login_at);
    }

    public function test_users_can_sign_out(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_users_deactivated_during_a_session_are_signed_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard'))->assertOk();

        $user->forceFill(['is_active' => false])->save();

        $this->get(route('dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
