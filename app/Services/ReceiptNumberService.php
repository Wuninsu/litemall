<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Issues document numbers from the `counters` table: receipts (R-000184) and returns (RT-000012).
 *
 * The counter row is locked while it is incremented, so two documents saved at the same moment can never
 * get the same number, and a number is never reused (if the surrounding transaction rolls back, the
 * increment rolls back with it, so no gaps are created by failed saves).
 */
class ReceiptNumberService
{
    public const COUNTER = 'receipt';

    public const RETURN_COUNTER = 'return';

    public function next(): string
    {
        return $this->nextFor(self::COUNTER, (string) config('sales.receipt_prefix'));
    }

    public function nextReturnNumber(): string
    {
        return $this->nextFor(self::RETURN_COUNTER, (string) config('sales.return_prefix', 'RT-'));
    }

    private function nextFor(string $counter, string $prefix): string
    {
        return DB::transaction(function () use ($counter, $prefix): string {
            DB::table('counters')->insertOrIgnore([
                'name' => $counter,
                'value' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $current = (int) DB::table('counters')->where('name', $counter)->lockForUpdate()->value('value');
            $next = $current + 1;

            DB::table('counters')->where('name', $counter)->update(['value' => $next, 'updated_at' => now()]);

            return $prefix.str_pad((string) $next, (int) config('sales.receipt_digits'), '0', STR_PAD_LEFT);
        });
    }
}
