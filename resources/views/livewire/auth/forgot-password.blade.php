<div>
    <h1 class="text-[28px] font-extrabold tracking-tight text-white">Forgot your password?</h1>

    @if ($linkRequested)
        <div class="mt-6 grid gap-2 rounded-xl border border-teal-300/30 bg-teal-300/10 px-4 py-4 text-sm text-teal-50" role="status">
            <p class="flex items-center gap-2 font-bold text-white"><x-icon name="check" class="size-4" />Check your email</p>
            <p>If an account exists for that email address, we have sent a link to choose a new password. The link expires in 60 minutes.</p>
        </div>
    @else
        <p class="mt-1 mb-8 text-teal-100/80">Enter your email address and we will send you a link to choose a new one.</p>

        <form wire:submit="sendResetLink" class="grid gap-5">
            <x-input tone="dark" wire:model="email" id="email" type="email" label="Email" autocomplete="username" placeholder="you@example.com" required autofocus />

            <x-button variant="bright" type="submit" size="lg" block target="sendResetLink" loading-text="Sending…">Send reset link</x-button>
        </form>
    @endif

    <p class="mt-8 text-center text-sm text-teal-100/70">
        Remembered it? <a href="{{ route('login') }}" wire:navigate class="font-semibold text-teal-300 hover:text-teal-200">Back to sign in</a>
    </p>
</div>
