<?php

namespace Tests\Feature\Services;

use App\Enums\UserRole;
use App\Exceptions\Users\UserManagementException;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(UserService::class);
    }

    public function test_create_normalises_email_hashes_password_and_assigns_a_uuid(): void
    {
        $user = $this->service->create([
            'name' => '  Ama Mensah ',
            'email' => ' Ama@Example.COM ',
            'role' => UserRole::Cashier,
            'password' => 'secret-password',
        ]);

        $this->assertTrue(Str::isUuid($user->id));
        $this->assertSame('Ama Mensah', $user->name);
        $this->assertSame('ama@example.com', $user->email);
        $this->assertSame(UserRole::Cashier, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertTrue(Hash::check('secret-password', $user->password));
    }

    public function test_update_changes_profile_and_keeps_password_when_blank(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->cashier()->create();
        $originalHash = $user->password;

        $this->service->update($user, ['name' => 'New Name', 'email' => 'NEW@example.com', 'role' => 'admin', 'password' => ''], $admin);

        $user->refresh();
        $this->assertSame('New Name', $user->name);
        $this->assertSame('new@example.com', $user->email);
        $this->assertSame(UserRole::Admin, $user->role);
        $this->assertSame($originalHash, $user->password);
    }

    public function test_changing_another_users_password_ends_their_sessions(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->cashier()->create();
        $this->insertSession('cashier-session', $user);
        $this->insertSession('admin-session', $admin);

        $this->service->update($user, ['name' => $user->name, 'email' => $user->email, 'role' => 'cashier', 'password' => 'brand-new-password'], $admin);

        $this->assertTrue(Hash::check('brand-new-password', $user->fresh()->password));
        $this->assertDatabaseMissing('sessions', ['id' => 'cashier-session']);
        $this->assertDatabaseHas('sessions', ['id' => 'admin-session']);
    }

    public function test_users_cannot_change_their_own_role(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->admin()->create();

        $this->expectExceptionObject(UserManagementException::cannotChangeOwnRole());

        $this->service->update($admin, ['name' => $admin->name, 'email' => $admin->email, 'role' => 'cashier'], $admin);
    }

    public function test_the_last_active_admin_cannot_be_demoted(): void
    {
        $actor = User::factory()->cashier()->create();
        $onlyAdmin = User::factory()->admin()->create();
        User::factory()->admin()->inactive()->create();

        try {
            $this->service->update($onlyAdmin, ['name' => $onlyAdmin->name, 'email' => $onlyAdmin->email, 'role' => 'cashier'], $actor);
            $this->fail('Expected UserManagementException.');
        } catch (UserManagementException $exception) {
            $this->assertSame(UserManagementException::lastActiveAdmin()->getMessage(), $exception->getMessage());
        }

        $this->assertSame(UserRole::Admin, $onlyAdmin->fresh()->role);
    }

    public function test_an_admin_can_be_demoted_when_another_active_admin_exists(): void
    {
        $actor = User::factory()->admin()->create();
        $other = User::factory()->admin()->create();

        $this->service->update($other, ['name' => $other->name, 'email' => $other->email, 'role' => 'cashier'], $actor);

        $this->assertSame(UserRole::Cashier, $other->fresh()->role);
    }

    public function test_deactivate_disables_the_account_and_ends_sessions(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->cashier()->create();
        $originalRememberToken = $user->remember_token;
        $this->insertSession('cashier-session', $user);

        $this->service->deactivate($user, $admin);

        $user->refresh();
        $this->assertFalse($user->is_active);
        $this->assertNotSame($originalRememberToken, $user->remember_token);
        $this->assertDatabaseMissing('sessions', ['id' => 'cashier-session']);
    }

    public function test_users_cannot_deactivate_themselves(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->admin()->create();

        $this->expectExceptionObject(UserManagementException::cannotDeactivateSelf());

        $this->service->deactivate($admin, $admin);
    }

    public function test_the_last_active_admin_cannot_be_deactivated(): void
    {
        $actor = User::factory()->cashier()->create();
        $onlyAdmin = User::factory()->admin()->create();

        $this->expectExceptionObject(UserManagementException::lastActiveAdmin());

        $this->service->deactivate($onlyAdmin, $actor);
    }

    public function test_activate_re_enables_an_account(): void
    {
        $user = User::factory()->inactive()->create();

        $this->service->activate($user);

        $this->assertTrue($user->fresh()->is_active);
    }

    private function insertSession(string $id, User $user): void
    {
        DB::table('sessions')->insert(['id' => $id, 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
    }
}
