<?php

namespace App\Enums;

/**
 * Roles a user can hold. Stored as the string value in `users.role`.
 */
enum UserRole: string
{
    case Admin = 'admin';
    case Cashier = 'cashier';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Cashier => 'Cashier',
        };
    }

    /**
     * One-line summary shown in the role picker.
     */
    public function description(): string
    {
        return match ($this) {
            self::Admin => 'Full access, including staff',
            self::Cashier => 'Day-to-day sales and payments',
        };
    }
}
