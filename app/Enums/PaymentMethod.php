<?php

namespace App\Enums;

/**
 * How a customer paid. Stored as the string value in `payments.method`.
 */
enum PaymentMethod: string
{
    case Cash = 'cash';
    case MobileMoney = 'mobile_money';
    case BankTransfer = 'bank_transfer';
    case Card = 'card';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::MobileMoney => 'Mobile Money',
            self::BankTransfer => 'Bank transfer',
            self::Card => 'Card',
        };
    }

    /**
     * Icon name for `<x-icon>`.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Cash => 'cash',
            self::MobileMoney => 'phone',
            self::BankTransfer => 'bank',
            self::Card => 'card',
        };
    }

    /**
     * Whether staff should record a transaction reference (e.g. MoMo transaction ID).
     */
    public function expectsReference(): bool
    {
        return $this !== self::Cash;
    }
}
