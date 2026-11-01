<?php

namespace App\Exceptions\Customers;

use App\Exceptions\DomainException;

class CustomerException extends DomainException
{
    public static function invalidPhone(): self
    {
        return new self('Enter a valid Ghana phone number, e.g. 024 412 3456.');
    }

    public static function phoneTaken(): self
    {
        return new self('Another customer already uses this phone number.');
    }
}
