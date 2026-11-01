<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Exceptions\Users\UserManagementException;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Administrator-driven user management.
 *
 * Guarantees the system always keeps at least one active administrator.
 */
class UserService
{
    /**
     * @param  array{name: string, email: string, role: UserRole|string, password: string}  $data
     */
    public function create(array $data): User
    {
        return User::query()->create([
            'name' => trim($data['name']),
            'email' => Str::lower(trim($data['email'])),
            'role' => $data['role'],
            'password' => $data['password'],
            'is_active' => true,
        ]);
    }

    /**
     * Updates profile fields and role; optionally sets a new password.
     *
     * @param  array{name: string, email: string, role: UserRole|string, password?: string|null}  $data
     *
     * @throws UserManagementException
     */
    public function update(User $user, array $data, User $actor): User
    {
        $role = $data['role'] instanceof UserRole ? $data['role'] : UserRole::from($data['role']);

        return DB::transaction(function () use ($user, $data, $role, $actor): User {
            if ($role !== $user->role) {
                if ($user->is($actor)) {
                    throw UserManagementException::cannotChangeOwnRole();
                }

                if ($user->isAdmin() && $user->is_active) {
                    $this->ensureAnotherActiveAdminExists($user);
                }
            }

            $user->fill([
                'name' => trim($data['name']),
                'email' => Str::lower(trim($data['email'])),
                'role' => $role,
            ]);

            if (! empty($data['password'])) {
                $user->password = $data['password'];
                $user->setRememberToken(Str::random(60));
            }

            $passwordChanged = $user->isDirty('password');

            $user->save();

            if ($passwordChanged && ! $user->is($actor)) {
                DB::table('sessions')->where('user_id', $user->getKey())->delete();
            }

            return $user;
        });
    }

    /**
     * Deactivates a user and ends all of their sessions immediately.
     *
     * @throws UserManagementException
     */
    public function deactivate(User $user, User $actor): void
    {
        if ($user->is($actor)) {
            throw UserManagementException::cannotDeactivateSelf();
        }

        DB::transaction(function () use ($user): void {
            if ($user->isAdmin()) {
                $this->ensureAnotherActiveAdminExists($user);
            }

            $user->forceFill([
                'is_active' => false,
                'remember_token' => Str::random(60),
            ])->save();

            DB::table('sessions')->where('user_id', $user->getKey())->delete();
        });
    }

    public function activate(User $user): void
    {
        $user->forceFill(['is_active' => true])->save();
    }

    /**
     * Locks the active admin rows so two concurrent demotions/deactivations
     * cannot both pass the check and leave zero administrators.
     *
     * @throws UserManagementException
     */
    private function ensureAnotherActiveAdminExists(User $except): void
    {
        $otherActiveAdmins = User::query()
            ->active()
            ->where('role', UserRole::Admin)
            ->whereKeyNot($except->getKey())
            ->lockForUpdate()
            ->count();

        if ($otherActiveAdmins === 0) {
            throw UserManagementException::lastActiveAdmin();
        }
    }
}
