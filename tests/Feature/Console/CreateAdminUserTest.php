<?php

namespace Tests\Feature\Console;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateAdminUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_administrator(): void
    {
        $this->artisan('app:create-admin-user', ['--name' => 'Owner', '--email' => 'Owner@Example.com', '--password' => 'secret-password'])
            ->expectsOutputToContain('Administrator owner@example.com created.')
            ->assertSuccessful();

        $user = User::query()->where('email', 'owner@example.com')->sole();
        $this->assertSame(UserRole::Admin, $user->role);
    }

    public function test_it_prompts_for_missing_values(): void
    {
        $this->artisan('app:create-admin-user')
            ->expectsQuestion('Name', 'Owner')
            ->expectsQuestion('Email', 'owner@example.com')
            ->expectsQuestion('Password', 'secret-password')
            ->assertSuccessful();

        $this->assertDatabaseHas('users', ['email' => 'owner@example.com', 'role' => 'admin']);
    }

    public function test_it_rejects_invalid_input(): void
    {
        User::factory()->create(['email' => 'owner@example.com']);

        $this->artisan('app:create-admin-user', ['--name' => 'Owner', '--email' => 'owner@example.com', '--password' => 'short'])
            ->assertFailed();

        $this->assertSame(1, User::query()->count());
    }
}
