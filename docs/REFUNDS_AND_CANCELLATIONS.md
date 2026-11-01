# Refunds and Cancellations

_Implemented in Phase 4 (2026-11-01). Decision: ADR-0020._

## Owner decisions

| Question | Decision |
|---|---|
| Who | Administrators only. Cashiers see a disabled "Return items" button ("Ask an administrator"). |
| What | Return some items from a sale, or everything (= cancellation). No separate payment reversal. |
| Money | The value returned first reduces what is still owed on that sale; only money the customer actually paid beyond that is refunded, with a method. No store credit. |
| Stock | Asked per item each time: "Put back into stock" (default ticked; untick if damaged or faulty). |

## How a return works

1. On the receipt page (`/sales/{sale}`) an administrator clicks **Return items**.
2. The panel lists every line with the quantity still returnable. They enter quantities (or click **Return everything (cancel sale)**), tick/untick **Put back into stock** per line, and give a **reason** (required).
3. A live preview shows **Value returned**, **Reduces what is owed** and **Refund to customer**. If there is a refund they choose the **method** (Cash, Mobile Money, Bank transfer, Card) and an optional reference.
4. **Record return** / **Cancel sale** calls `ReturnService::process()`, which in one transaction (locks on the sale, its lines and the products):
   - issues a return number `RT-000001` (own counter, no gaps from failures),
   - creates `sale_returns` + `sale_return_lines`,
   - increases `sale_lines.returned_quantity`,
   - adds the units back to `products.stock_quantity` for lines marked "back to stock",
   - updates the sale: `returned_pesewas += credit`, `paid_pesewas −= refund`, `status` recalculated (Cancelled when everything is returned).
5. The receipt shows the return history (number, type, date, who, reason, items, debt reduced, refund and method), "N returned" under lines, Returned / Net total rows, and a CANCELLED stamp for cancelled sales.

The original sale, its lines and its payments are never edited or deleted (ADR-0004).

## Valuing returned items

Each returned unit is valued at the unit price on the sale line, less its share of the sale discount:

```
value = round_half_up(unit_price × quantity × total ÷ subtotal)
```

When the return takes everything still left on the sale, the credit is exactly the remaining net total (any rounding difference goes on the last line), so a cancelled sale always ends at GH₵ 0.00.

## Money examples (GH₵)

| Situation | Total | Paid | Owed | Returned value | Reduces debt | Refund | Afterwards |
|---|---|---|---|---|---|---|---|
| Paid in full, 1 of 3 fans returned | 1,350.00 | 1,350.00 | 0.00 | 450.00 | 0.00 | **450.00** | Net 900.00, paid 900.00, Paid |
| Unpaid credit sale, 1 of 2 TVs returned | 2,000.00 | 0.00 | 2,000.00 | 1,000.00 | 1,000.00 | 0.00 | Net 1,000.00, owes 1,000.00, Unpaid |
| Part-paid, most items returned | 1,000.00 | 400.00 | 600.00 | 800.00 | 600.00 | **200.00** | Net 200.00, paid 200.00, Paid |
| Discounted sale cancelled in two steps | 899.99 (999.99 − 100.00) | 500.00 | 399.99 | 300.00 then 599.99 | 300.00 then 99.99 | 0.00 then **500.00** | Net 0.00, paid 0.00, Cancelled |

All four are tests in `tests/Feature/Services/ReturnServiceTest.php`.

## Effects elsewhere

- **Balances** everywhere use `total − returned − paid` (`Sale::OWED_SQL`): customer balances, the "Owing money" filter, oldest-first payment allocation (cancelled sales are skipped), and the dashboard "Owed by customers".
- **Dashboard:** "Sales today" is net of returns; "Received today" is payments minus refunds; cancelled sales are not counted in the number of sales.
- **Sales list:** totals use net amounts; cancelled sales show a grey struck-through "Cancelled" pill.

## Rules

RF1–RF9 in [BUSINESS_RULES.md](BUSINESS_RULES.md).

## Code

`app/Services/ReturnService.php` (`preview`, `process`), `app/Models/{SaleReturn,SaleReturnLine}.php`, `Sale::netTotalPesewas()`, `balancePesewas()`, `recalculateStatus()`, `OWED_SQL`, `SaleLine::returnableQuantity()`, `SaleStatus::Cancelled`, `app/Exceptions/Sales/SaleReturnException.php`, `SalePolicy::refund`, `app/Livewire/Sales/Show.php` (panel), `ReceiptNumberService::nextReturnNumber()`, `config('sales.return_prefix')`.
Tests: `tests/Feature/Services/ReturnServiceTest.php`, `tests/Feature/Sales/ReturnsScreenTest.php`.

## Not implemented

Reversing a single wrongly recorded payment; exchanges (return + new sale in one step); store credit; time limits on returns; returns from the POS screen; printing a separate return slip.
