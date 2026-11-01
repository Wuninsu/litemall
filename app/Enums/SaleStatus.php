<?php

namespace App\Enums;

/**
 * Payment state of a sale, derived from `paid_pesewas` vs `total_pesewas` and stored in `sales.status`
 * so lists can be filtered. Always recomputed by the services; never set from input.
 */
enum SaleStatus: string
{
    case Paid = 'paid';
    case PartlyPaid = 'partly_paid';
    case Unpaid = 'unpaid';

    public static function for(int $totalPesewas, int $paidPesewas): self
    {
        return match (true) {
            $paidPesewas >= $totalPesewas => self::Paid,
            $paidPesewas > 0 => self::PartlyPaid,
            default => self::Unpaid,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Paid => 'Paid',
            self::PartlyPaid => 'Part-paid',
            self::Unpaid => 'Unpaid',
        };
    }
}
