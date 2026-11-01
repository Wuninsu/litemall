<?php

namespace App\Livewire\Users;

use App\Enums\UserRole;
use App\Exceptions\Users\UserManagementException;
use App\Livewire\Forms\UserForm;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Users list with a single modal used for both creating and editing.
 * This list + modal shape is the template for customers and products.
 */
#[Title('Users')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $role = '';

    #[Url]
    public string $status = '';

    public UserForm $form;

    public bool $showModal = false;

    public ?string $notice = null;

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'role', 'status'], true)) {
            $this->resetPage();
        }
    }

    public function create(): void
    {
        $this->authorize('create', User::class);

        $this->form->reset();
        $this->resetValidation();
        $this->showModal = true;
    }

    public function edit(string $userId): void
    {
        $user = User::query()->findOrFail($userId);

        $this->authorize('update', $user);

        $this->form->reset();
        $this->form->setUser($user);
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(UserService $userService): void
    {
        $user = $this->form->user;

        $this->authorize($user === null ? 'create' : 'update', $user ?? User::class);

        $payload = $this->form->payload();

        try {
            $user = $user === null
                ? $userService->create($payload)
                : $userService->update($user, $payload, auth()->user());
        } catch (UserManagementException $exception) {
            $this->addError('form.role', $exception->getMessage());

            return;
        }

        $this->notice = $this->form->user === null ? "{$user->name} was added." : "{$user->name} was updated.";
        $this->closeModal();
    }

    public function deactivate(UserService $userService): void
    {
        $user = $this->form->user;

        abort_if($user === null, 404);
        $this->authorize('deactivate', $user);

        try {
            $userService->deactivate($user, auth()->user());
        } catch (UserManagementException $exception) {
            $this->addError('form.status', $exception->getMessage());

            return;
        }

        $this->notice = "{$user->name} was deactivated and signed out.";
        $this->closeModal();
    }

    public function activate(UserService $userService): void
    {
        $user = $this->form->user;

        abort_if($user === null, 404);
        $this->authorize('update', $user);

        $userService->activate($user);

        $this->notice = "{$user->name} was reactivated.";
        $this->closeModal();
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->form->reset();
        $this->resetValidation();
    }

    public function render(): View
    {
        $users = User::query()
            ->when($this->search !== '', function ($query): void {
                $term = '%'.$this->search.'%';
                $query->where(fn ($query) => $query->where('name', 'like', $term)->orWhere('email', 'like', $term));
            })
            ->when(UserRole::tryFrom($this->role), fn ($query, UserRole $role) => $query->where('role', $role))
            ->when($this->status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($this->status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.users.index', [
            'users' => $users,
            'roles' => UserRole::cases(),
        ]);
    }
}
