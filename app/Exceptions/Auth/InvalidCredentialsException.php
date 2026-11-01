<?php

namespace App\Exceptions\Auth;

use App\Exceptions\DomainException;

class InvalidCredentialsException extends DomainException
{
    public function __construct()
    {
        parent::__construct(__('auth.failed'));
    }
}
