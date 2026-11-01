<?php

namespace App\Livewire\Auth;

use App\Exceptions\DomainException;
use App\Services\Auth\PasswordResetService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::guest')]
#[Title('Reset password')]
class ResetPassword extends Component
{
    #[Locked]
    public string $token = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = (string) request()->query('email', '');
    }

    public function resetPassword(PasswordResetService $passwordResetService): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        try {
            $passwordResetService->reset($this->token, $this->email, $this->password);
        } catch (DomainException $exception) {
            $this->addError('email', $exception->getMessage());

            return;
        }

        session()->flash('status', __('passwords.reset'));

        $this->redirectRoute('login', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.auth.reset-password');
    }
}
