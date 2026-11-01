<?php

namespace App\Exceptions\Payments;

use App\Exceptions\DomainException;
use App\Support\Money;

class PaymentException extends DomainException
{
    public static function amountMustBePositive(): self
    {
        return new self('Enter an amount greater than GH₵ 0.00.');
    }

    public static function nothingOwed(): self
    {
        return new self('This customer has nothing to pay.');
    }

    public static function exceedsBalance(int $owedPesewas): self
    {
        return new self('The customer only owes '.Money::format($owedPesewas).'. Enter that amount or less.');
    }
}
