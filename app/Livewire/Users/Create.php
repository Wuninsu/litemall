<?php

namespace App\Livewire\Users;

use App\Enums\UserRole;
use App\Livewire\Forms\UserForm;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('New user')]
class Create extends Component
{
    public UserForm $form;

    public function mount(): void
    {
        $this->authorize('create', User::class);
    }

    public function save(UserService $userService): void
    {
        $this->authorize('create', User::class);

        $user = $userService->create($this->form->payload());

        session()->flash('status', "User {$user->name} created.");

        $this->redirectRoute('users.index', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.users.create', ['roles' => UserRole::cases()]);
    }
}
