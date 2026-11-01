<?php

namespace App\Livewire\Forms;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Locked;
use Livewire\Form;

/**
 * Input shape and validation for creating/editing users. Business rules
 * (last admin, own role) are enforced by UserService.
 */
class UserForm extends Form
{
    #[Locked]
    public ?User $user = null;

    public string $name = '';

    public string $email = '';

    public string $role = UserRole::Cashier->value;

    public string $password = '';

    public string $password_confirmation = '';

    public function setUser(User $user): void
    {
        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role->value;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class, 'email')->ignore($this->user?->getKey())],
            'role' => ['required', Rule::enum(UserRole::class)],
            'password' => [$this->user === null ? 'required' : 'nullable', 'string', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * @return array{name: string, email: string, role: string, password: string}
     */
    public function payload(): array
    {
        $this->email = strtolower(trim($this->email));

        $validated = $this->validate();

        return [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => (string) $validated['password'],
        ];
    }
}
