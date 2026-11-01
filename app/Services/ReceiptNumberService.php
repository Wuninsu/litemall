<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Issues receipt numbers like R-000184 from the `counters` table.
 *
 * The counter row is locked while it is incremented, so two sales saved at the same moment can never
 * get the same number, and a number is never reused (if the surrounding sale transaction rolls back,
 * the increment rolls back with it, so no gaps are created by failed sales).
 */
class ReceiptNumberService
{
    public const COUNTER = 'receipt';

    public function next(): string
    {
        return DB::transaction(function (): string {
            DB::table('counters')->insertOrIgnore([
                'name' => self::COUNTER,
                'value' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $current = (int) DB::table('counters')->where('name', self::COUNTER)->lockForUpdate()->value('value');
            $next = $current + 1;

            DB::table('counters')->where('name', self::COUNTER)->update(['value' => $next, 'updated_at' => now()]);

            return config('sales.receipt_prefix').str_pad((string) $next, (int) config('sales.receipt_digits'), '0', STR_PAD_LEFT);
        });
    }
}
