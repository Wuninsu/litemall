<div>
    <h2 class="mb-4 text-lg font-semibold">Sign in</h2>

    <form wire:submit="login" class="space-y-4">
        <div>
            <label for="email" class="block text-sm font-medium">Email</label>
            <input wire:model="email" id="email" type="email" autocomplete="username" required autofocus
                   class="mt-1 w-full rounded border border-zinc-300 px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none">
            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium">Password</label>
            <input wire:model="password" id="password" type="password" autocomplete="current-password" required
                   class="mt-1 w-full rounded border border-zinc-300 px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none">
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input wire:model="remember" type="checkbox" class="rounded border-zinc-300">
            Remember me
        </label>

        <button type="submit" class="w-full rounded bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700" wire:loading.attr="disabled">
            Sign in
        </button>

        <p class="text-center text-sm">
            <a href="{{ route('password.request') }}" class="text-zinc-600 underline hover:text-zinc-900" wire:navigate>Forgot your password?</a>
        </p>
    </form>
</div>
