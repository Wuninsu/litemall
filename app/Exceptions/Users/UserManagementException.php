<?php

namespace App\Exceptions\Users;

use App\Exceptions\DomainException;

class UserManagementException extends DomainException
{
    public static function lastActiveAdmin(): self
    {
        return new self('At least one active administrator must remain.');
    }

    public static function cannotDeactivateSelf(): self
    {
        return new self('You cannot deactivate your own account.');
    }

    public static function cannotChangeOwnRole(): self
    {
        return new self('You cannot change your own role.');
    }
}
