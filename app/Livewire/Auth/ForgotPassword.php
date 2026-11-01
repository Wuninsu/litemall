<?php

namespace App\Livewire\Auth;

use App\Exceptions\DomainException;
use App\Services\Auth\PasswordResetService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::guest')]
#[Title('Forgot password')]
class ForgotPassword extends Component
{
    public string $email = '';

    public bool $linkRequested = false;

    public function sendResetLink(PasswordResetService $passwordResetService): void
    {
        $this->email = trim($this->email);

        $this->validate(['email' => ['required', 'string', 'email']]);

        try {
            $passwordResetService->sendResetLink($this->email, request()->ip());
        } catch (DomainException $exception) {
            $this->addError('email', $exception->getMessage());

            return;
        }

        $this->linkRequested = true;
    }

    public function render(): View
    {
        return view('livewire.auth.forgot-password');
    }
}
