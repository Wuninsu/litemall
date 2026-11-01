<div>
    <h1 class="text-[28px] font-extrabold tracking-tight text-white">Choose a new password</h1>
    <p class="mt-1 mb-8 text-teal-100/80">After saving you will be signed out of all other devices.</p>

    <form wire:submit="resetPassword" class="grid gap-5">
        <x-input tone="dark" wire:model="email" id="email" type="email" label="Email" autocomplete="username" required />
        <x-input tone="dark" wire:model="password" id="password" type="password" label="New password" autocomplete="new-password" required />
        <x-input tone="dark" wire:model="password_confirmation" id="password_confirmation" type="password" label="Confirm new password" autocomplete="new-password" required />

        <x-button variant="bright" type="submit" size="lg" block target="resetPassword" loading-text="Saving…">Reset password</x-button>
    </form>

    <p class="mt-8 text-center text-sm">
        <a href="{{ route('login') }}" wire:navigate class="font-semibold text-teal-300 hover:text-teal-200">Back to sign in</a>
    </p>
</div>
