<?php

namespace App\Exceptions\Auth;

use App\Exceptions\DomainException;

class InvalidPasswordResetException extends DomainException
{
    public function __construct(string $brokerStatus)
    {
        parent::__construct(__($brokerStatus));
    }
}
