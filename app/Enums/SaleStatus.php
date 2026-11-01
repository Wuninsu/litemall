<?php

namespace App\Enums;

/**
 * Payment state of a sale, derived from the amounts and stored in `sales.status` so lists can be filtered.
 * Always recomputed by the services (`Sale::recalculateStatus()`); never set from input.
 */
enum SaleStatus: string
{
    case Paid = 'paid';
    case PartlyPaid = 'partly_paid';
    case Unpaid = 'unpaid';
    case Cancelled = 'cancelled';

    /**
     * @param  int  $totalPesewas  original sale total
     * @param  int  $paidPesewas  money kept (payments in minus refunds out)
     * @param  int  $returnedPesewas  value of items returned (ADR-0020)
     */
    public static function for(int $totalPesewas, int $paidPesewas, int $returnedPesewas = 0): self
    {
        if ($totalPesewas > 0 && $returnedPesewas >= $totalPesewas) {
            return self::Cancelled;
        }

        $net = $totalPesewas - $returnedPesewas;

        return match (true) {
            $paidPesewas >= $net => self::Paid,
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
            self::Cancelled => 'Cancelled',
        };
    }
}
