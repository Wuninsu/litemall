<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\ResetPassword;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_page_renders(): void
    {
        $this->get(route('password.request'))->assertOk()->assertSeeLivewire(ForgotPassword::class);
    }

    public function test_reset_link_is_sent_to_existing_users(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'ama@example.com']);

        Livewire::test(ForgotPassword::class)
            ->set('email', 'AMA@example.com')
            ->call('sendResetLink')
            ->assertHasNoErrors()
            ->assertSet('linkRequested', true);

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_unknown_emails_get_the_same_response_without_a_notification(): void
    {
        Notification::fake();

        Livewire::test(ForgotPassword::class)
            ->set('email', 'nobody@example.com')
            ->call('sendResetLink')
            ->assertHasNoErrors()
            ->assertSet('linkRequested', true);

        Notification::assertNothingSent();
    }

    public function test_reset_link_requests_are_throttled_per_ip(): void
    {
        Notification::fake();

        foreach (range(1, 5) as $request) {
            Livewire::test(ForgotPassword::class)->set('email', "user{$request}@example.com")->call('sendResetLink');
        }

        Livewire::test(ForgotPassword::class)
            ->set('email', 'another@example.com')
            ->call('sendResetLink')
            ->assertHasErrors('email')
            ->assertSet('linkRequested', false);
    }

    public function test_password_can_be_reset_with_a_valid_token(): void
    {
        $user = User::factory()->create(['email' => 'ama@example.com']);
        $token = Password::broker()->createToken($user);
        DB::table('sessions')->insert(['id' => 'old-session', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);

        $this->get(route('password.reset', ['token' => $token, 'email' => 'ama@example.com']))
            ->assertOk()
            ->assertSeeLivewire(ResetPassword::class);

        Livewire::withQueryParams(['email' => 'ama@example.com'])
            ->test(ResetPassword::class, ['token' => $token])
            ->assertSet('email', 'ama@example.com')
            ->set('password', 'new-secret-password')
            ->set('password_confirmation', 'new-secret-password')
            ->call('resetPassword')
            ->assertHasNoErrors()
            ->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('new-secret-password', $user->fresh()->password));
        $this->assertDatabaseMissing('sessions', ['id' => 'old-session']);
    }

    public function test_invalid_token_is_rejected(): void
    {
        $user = User::factory()->create(['email' => 'ama@example.com']);

        Livewire::test(ResetPassword::class, ['token' => 'invalid-token'])
            ->set('email', 'ama@example.com')
            ->set('password', 'new-secret-password')
            ->set('password_confirmation', 'new-secret-password')
            ->call('resetPassword')
            ->assertHasErrors('email');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_password_confirmation_must_match(): void
    {
        Livewire::test(ResetPassword::class, ['token' => 'token'])
            ->set('email', 'ama@example.com')
            ->set('password', 'new-secret-password')
            ->set('password_confirmation', 'different-password')
            ->call('resetPassword')
            ->assertHasErrors(['password' => 'confirmed']);
    }
}
