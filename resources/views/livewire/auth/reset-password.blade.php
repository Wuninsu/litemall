<div>
    <h2 class="mb-4 text-lg font-semibold">Choose a new password</h2>

    <form wire:submit="resetPassword" class="space-y-4">
        <div>
            <label for="email" class="block text-sm font-medium">Email</label>
            <input wire:model="email" id="email" type="email" autocomplete="username" required
                   class="mt-1 w-full rounded border border-zinc-300 px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none">
            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium">New password</label>
            <input wire:model="password" id="password" type="password" autocomplete="new-password" required
                   class="mt-1 w-full rounded border border-zinc-300 px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none">
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium">Confirm new password</label>
            <input wire:model="password_confirmation" id="password_confirmation" type="password" autocomplete="new-password" required
                   class="mt-1 w-full rounded border border-zinc-300 px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none">
        </div>

        <button type="submit" class="w-full rounded bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700" wire:loading.attr="disabled">
            Reset password
        </button>
    </form>
</div>
