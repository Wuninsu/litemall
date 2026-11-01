<?php

namespace App\Livewire\Users;

use App\Enums\UserRole;
use App\Exceptions\Users\UserManagementException;
use App\Livewire\Forms\UserForm;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Edit user')]
class Edit extends Component
{
    #[Locked]
    public User $user;

    public UserForm $form;

    public ?string $statusMessage = null;

    public function mount(User $user): void
    {
        $this->authorize('update', $user);

        $this->user = $user;
        $this->form->setUser($user);
    }

    public function save(UserService $userService): void
    {
        $this->authorize('update', $this->user);

        try {
            $userService->update($this->user, $this->form->payload(), auth()->user());
        } catch (UserManagementException $exception) {
            $this->addError('form.role', $exception->getMessage());

            return;
        }

        session()->flash('status', "User {$this->user->name} updated.");

        $this->redirectRoute('users.index', navigate: true);
    }

    public function deactivate(UserService $userService): void
    {
        $this->authorize('deactivate', $this->user);

        try {
            $userService->deactivate($this->user, auth()->user());
        } catch (UserManagementException $exception) {
            $this->addError('status', $exception->getMessage());

            return;
        }

        $this->statusMessage = "User {$this->user->name} deactivated.";
    }

    public function activate(UserService $userService): void
    {
        $this->authorize('update', $this->user);

        $userService->activate($this->user);

        $this->statusMessage = "User {$this->user->name} activated.";
    }

    public function render(): View
    {
        return view('livewire.users.edit', ['roles' => UserRole::cases()]);
    }
}
