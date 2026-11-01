<div>
    <h1 class="text-[28px] font-extrabold tracking-tight text-white">Welcome back</h1>
    <p class="mt-1 mb-8 text-teal-100/80">Sign in to continue to your dashboard.</p>

    <form wire:submit="login" class="grid gap-5">
        <x-input tone="dark" wire:model="email" id="email" type="email" label="Email" autocomplete="username" placeholder="you@example.com" required autofocus />
        <x-input tone="dark" wire:model="password" id="password" type="password" label="Password" autocomplete="current-password" placeholder="Enter your password" required />

        <div class="flex items-center justify-between text-sm">
            <label class="flex items-center gap-2 text-teal-50/90">
                <input wire:model="remember" type="checkbox" class="size-4 rounded border-white/30 bg-white/10 text-teal-400 focus:ring-teal-300/30 focus:ring-offset-0">
                Remember me
            </label>
            <a href="{{ route('password.request') }}" wire:navigate class="font-semibold text-teal-300 hover:text-teal-200">Forgot password?</a>
        </div>

        <x-button variant="bright" type="submit" size="lg" block target="login" loading-text="Signing in…">Sign in</x-button>
    </form>
</div>
