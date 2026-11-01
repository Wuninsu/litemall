<div>
    <h2 class="mb-2 text-lg font-semibold">Forgot password</h2>

    @if ($linkRequested)
        <p class="rounded bg-green-50 px-3 py-2 text-sm text-green-800" role="status">
            If an account exists for that email address, a password reset link has been sent to it.
        </p>
    @else
        <p class="mb-4 text-sm text-zinc-600">Enter your email address and we will send you a link to choose a new password.</p>

        <form wire:submit="sendResetLink" class="space-y-4">
            <div>
                <label for="email" class="block text-sm font-medium">Email</label>
                <input wire:model="email" id="email" type="email" autocomplete="username" required autofocus
                       class="mt-1 w-full rounded border border-zinc-300 px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none">
                @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="w-full rounded bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700" wire:loading.attr="disabled">
                Send reset link
            </button>
        </form>
    @endif

    <p class="mt-4 text-center text-sm">
        <a href="{{ route('login') }}" class="text-zinc-600 underline hover:text-zinc-900" wire:navigate>Back to sign in</a>
    </p>
</div>
