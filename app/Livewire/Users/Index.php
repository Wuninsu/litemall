<?php

namespace App\Livewire\Users;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Users')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $users = User::query()
            ->when($this->search !== '', function ($query): void {
                $term = '%'.$this->search.'%';
                $query->where(fn ($query) => $query->where('name', 'like', $term)->orWhere('email', 'like', $term));
            })
            ->orderBy('name')
            ->paginate(20);

        return view('livewire.users.index', ['users' => $users]);
    }
}
