<?php

namespace App\Exceptions\Auth;

use App\Exceptions\DomainException;

class TooManyAttemptsException extends DomainException
{
    public function __construct(public readonly int $retryAfterSeconds)
    {
        parent::__construct(__('auth.throttle', [
            'seconds' => $retryAfterSeconds,
            'minutes' => (int) ceil($retryAfterSeconds / 60),
        ]));
    }
}
