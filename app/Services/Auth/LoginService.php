<?php

namespace App\Services\Auth;

use App\Exceptions\Auth\AccountInactiveException;
use App\Exceptions\Auth\InvalidCredentialsException;
use App\Exceptions\Auth\TooManyAttemptsException;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Authenticates users against the `web` guard.
 *
 * Session regeneration/invalidation is an HTTP concern and is done by the caller.
 */
class LoginService
{
    public const MAX_ATTEMPTS = 5;

    public const DECAY_SECONDS = 60;

    /**
     * @throws TooManyAttemptsException
     * @throws InvalidCredentialsException
     * @throws AccountInactiveException
     */
    public function attempt(string $email, string $password, bool $remember, string $ipAddress): User
    {
        $email = Str::lower(trim($email));
        $throttleKey = $this->throttleKey($email, $ipAddress);

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            throw new TooManyAttemptsException(RateLimiter::availableIn($throttleKey));
        }

        $user = User::query()->where('email', $email)->first();

        if ($user === null || ! Hash::check($password, $user->password)) {
            RateLimiter::hit($throttleKey, self::DECAY_SECONDS);

            throw new InvalidCredentialsException;
        }

        if (! $user->is_active) {
            RateLimiter::hit($throttleKey, self::DECAY_SECONDS);

            throw new AccountInactiveException;
        }

        RateLimiter::clear($throttleKey);

        Auth::guard('web')->login($user, $remember);

        $user->forceFill(['last_login_at' => now()])->save();

        return $user;
    }

    public function logout(): void
    {
        Auth::guard('web')->logout();
    }

    private function throttleKey(string $email, string $ipAddress): string
    {
        return 'login:'.$email.'|'.$ipAddress;
    }
}
