<?php

namespace App\Enums;

/**
 * Which price list a sale (or sale line) used. Stored on `sales.price_tier` and `sale_lines.price_tier`.
 */
enum PriceTier: string
{
    case Retail = 'retail';
    case Wholesale = 'wholesale';

    public function label(): string
    {
        return match ($this) {
            self::Retail => 'Retail',
            self::Wholesale => 'Wholesale',
        };
    }
}
