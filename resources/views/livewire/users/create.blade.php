<div class="max-w-lg">
    <h1 class="mb-6 text-2xl font-semibold">New user</h1>

    <form wire:submit="save" class="space-y-4 rounded-lg bg-white p-6 shadow-sm ring-1 ring-zinc-200">
        @include('livewire.users.partials.form-fields', ['roles' => $roles, 'passwordOptional' => false])

        <div class="flex items-center gap-4 pt-2">
            <button type="submit" class="rounded bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700" wire:loading.attr="disabled">Create user</button>
            <a href="{{ route('users.index') }}" wire:navigate class="text-sm text-zinc-600 underline hover:text-zinc-900">Cancel</a>
        </div>
    </form>
</div>
