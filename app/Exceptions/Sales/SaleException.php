<?php

namespace App\Exceptions\Sales;

use App\Exceptions\DomainException;
use App\Support\Money;

class SaleException extends DomainException
{
    /**
     * @param  string  $field  form field the message belongs to (lines, customer, discount, payment)
     */
    public function __construct(string $message, public readonly string $field)
    {
        parent::__construct($message);
    }

    public static function noLines(): self
    {
        return new self('Add at least one product to the sale.', 'lines');
    }

    public static function invalidQuantity(): self
    {
        return new self('Each quantity must be a whole number of at least 1.', 'lines');
    }

    public static function productUnavailable(string $name): self
    {
        return new self("{$name} is no longer available for sale.", 'lines');
    }

    public static function insufficientStock(string $name, int $available): self
    {
        return new self("Only {$available} of {$name} in stock.", 'lines');
    }

    public static function customerUnavailable(): self
    {
        return new self('This customer is inactive and cannot be used for new sales.', 'customer');
    }

    public static function creditNeedsCustomer(): self
    {
        return new self('Choose a customer to sell on credit. Walk-in sales must be paid in full.', 'customer');
    }

    public static function discountTooLarge(): self
    {
        return new self('The discount cannot be more than the sale subtotal.', 'discount');
    }

    public static function discountNeedsAdmin(float $limitPercent): self
    {
        $limit = rtrim(rtrim(number_format($limitPercent, 2), '0'), '.');

        return new self("Cashiers can give up to {$limit}% discount. Ask an administrator for a larger discount.", 'discount');
    }

    public static function overpayment(int $totalPesewas): self
    {
        return new self('The amount paid cannot be more than the sale total of '.Money::format($totalPesewas).'.', 'payment');
    }

    public static function paymentMethodRequired(): self
    {
        return new self('Choose how the customer paid.', 'payment');
    }
}
