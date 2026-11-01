<?php

namespace App\Exceptions\Products;

use App\Exceptions\DomainException;

class ProductException extends DomainException
{
    /**
     * @param  string  $field  form field the message belongs to (price, sku)
     */
    public function __construct(string $message, public readonly string $field)
    {
        parent::__construct($message);
    }

    public static function invalidPrice(): self
    {
        return new self('The price must be more than GH₵ 0.00.', 'price');
    }

    public static function invalidWholesalePrice(): self
    {
        return new self('The wholesale price must be more than GH₵ 0.00 and not more than the retail price.', 'wholesalePrice');
    }

    public static function invalidWholesaleMinimum(): self
    {
        return new self('The wholesale minimum quantity must be at least 1.', 'wholesaleMinQuantity');
    }

    public static function invalidStock(): self
    {
        return new self('The quantity in stock cannot be negative.', 'stock');
    }

    public static function imageNotSaved(): self
    {
        return new self('The photo could not be saved. Please try again.', 'image');
    }

    public static function skuTaken(): self
    {
        return new self('Another product already uses this SKU.', 'sku');
    }
}
