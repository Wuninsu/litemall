<?php

namespace App\Exceptions\Sales;

use App\Exceptions\DomainException;

class SaleReturnException extends DomainException
{
    /**
     * @param  string  $field  form field the message belongs to (lines, reason, refundMethod)
     */
    public function __construct(string $message, public readonly string $field)
    {
        parent::__construct($message);
    }

    public static function alreadyCancelled(): self
    {
        return new self('This sale has already been cancelled; nothing is left to return.', 'lines');
    }

    public static function nothingSelected(): self
    {
        return new self('Choose at least one item to return.', 'lines');
    }

    public static function tooMany(string $productName, int $returnable): self
    {
        return new self("Only {$returnable} of {$productName} can still be returned.", 'lines');
    }

    public static function unknownLine(): self
    {
        return new self('One of the items is not part of this sale.', 'lines');
    }

    public static function reasonRequired(): self
    {
        return new self('Give a reason for the return or cancellation.', 'reason');
    }

    public static function refundMethodRequired(): self
    {
        return new self('Choose how the refund is paid back to the customer.', 'refundMethod');
    }
}
