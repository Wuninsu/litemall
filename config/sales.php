<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cashier discount limit
    |--------------------------------------------------------------------------
    |
    | The largest discount a cashier may give on a sale, as a percentage of the
    | sale subtotal. Larger discounts must be entered by an administrator.
    | Administrators may discount up to the full subtotal.
    |
    */

    'cashier_max_discount_percent' => (float) env('SALES_CASHIER_MAX_DISCOUNT_PERCENT', 10),

    /*
    |--------------------------------------------------------------------------
    | Receipt numbers
    |--------------------------------------------------------------------------
    |
    | Receipts are numbered PREFIX + zero-padded running number, e.g. R-000184;
    | returns and cancellations use their own sequence, e.g. RT-000012.
    | Numbers are never reused.
    |
    */

    'receipt_prefix' => 'R-',

    'return_prefix' => 'RT-',

    'receipt_digits' => 6,

];
