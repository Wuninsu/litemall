<?php

namespace App\Services\Auth;

use App\Exceptions\Auth\InvalidPasswordResetException;
use App\Exceptions\Auth\TooManyAttemptsException;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Self-service password reset using Laravel's password broker (`password_reset_tokens`).
 */
class PasswordResetService
{
    public const MAX_REQUESTS_PER_IP = 5;

    public const DECAY_SECONDS = 60;

    /**
     * Sends a reset link if the email belongs to a user.
     *
     * Deliberately reports nothing about whether the account exists, so the
     * form cannot be used to discover registered email addresses.
     *
     * @throws TooManyAttemptsException
     */
    public function sendResetLink(string $email, string $ipAddress): void
    {
        $throttleKey = 'password-reset:'.$ipAddress;

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_REQUESTS_PER_IP)) {
            throw new TooManyAttemptsException(RateLimiter::availableIn($throttleKey));
        }

        RateLimiter::hit($throttleKey, self::DECAY_SECONDS);

        Password::broker()->sendResetLink(['email' => Str::lower(trim($email))]);
    }

    /**
     * @throws InvalidPasswordResetException when the token is invalid/expired or the email is unknown
     */
    public function reset(string $token, string $email, string $password): void
    {
        $status = Password::broker()->reset(
            [
                'email' => Str::lower(trim($email)),
                'password' => $password,
                'token' => $token,
            ],
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                DB::table('sessions')->where('user_id', $user->getKey())->delete();

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw new InvalidPasswordResetException($status);
        }
    }
}
