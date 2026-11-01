<?php

namespace App\Livewire\Auth;

use App\Exceptions\DomainException;
use App\Services\Auth\LoginService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::guest')]
#[Title('Sign in')]
class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function login(LoginService $loginService): void
    {
        $this->email = trim($this->email);

        $this->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ]);

        try {
            $loginService->attempt($this->email, $this->password, $this->remember, request()->ip());
        } catch (DomainException $exception) {
            $this->reset('password');
            $this->addError('email', $exception->getMessage());

            return;
        }

        session()->regenerate();

        $this->redirectIntended(route('dashboard'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.auth.login');
    }
}
