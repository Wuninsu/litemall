<?php

namespace App\Exceptions\Auth;

use App\Exceptions\DomainException;

class AccountInactiveException extends DomainException
{
    public function __construct()
    {
        parent::__construct('This account has been deactivated. Please contact an administrator.');
    }
}
