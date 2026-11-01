<div class="max-w-lg">
    <h1 class="mb-6 text-2xl font-semibold">Edit {{ $user->name }}</h1>

    @if ($statusMessage)
        <p class="mb-4 rounded bg-green-50 px-3 py-2 text-sm text-green-800" role="status">{{ $statusMessage }}</p>
    @endif

    <form wire:submit="save" class="space-y-4 rounded-lg bg-white p-6 shadow-sm ring-1 ring-zinc-200">
        @include('livewire.users.partials.form-fields', ['roles' => $roles, 'passwordOptional' => true])

        <div class="flex items-center gap-4 pt-2">
            <button type="submit" class="rounded bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700" wire:loading.attr="disabled">Save changes</button>
            <a href="{{ route('users.index') }}" wire:navigate class="text-sm text-zinc-600 underline hover:text-zinc-900">Cancel</a>
        </div>
    </form>

    @unless ($user->is(auth()->user()))
        <div class="mt-6 rounded-lg bg-white p-6 shadow-sm ring-1 ring-zinc-200">
            <h2 class="font-medium">Account status</h2>

            @if ($user->is_active)
                <p class="mt-1 text-sm text-zinc-600">Deactivating signs this user out everywhere and blocks future sign-ins. Their records are kept.</p>
                <button wire:click="deactivate" wire:confirm="Deactivate {{ $user->name }}?" type="button"
                        class="mt-3 rounded bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-500">Deactivate</button>
            @else
                <p class="mt-1 text-sm text-zinc-600">This account is inactive and cannot sign in.</p>
                <button wire:click="activate" type="button"
                        class="mt-3 rounded bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700">Activate</button>
            @endif

            @error('status') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    @endunless
</div>
