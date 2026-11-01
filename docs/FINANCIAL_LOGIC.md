# Financial Logic

_As of 2026-11-01 (Phase 3). Implemented: money representation, prices, stock value, sale totals, discounts, payments at the counter, later payments with oldest-first allocation, balances. Not yet: refunds, reversals, cancellations, promotions._

## Money representation (ADR-0005)

| Rule | Where |
|---|---|
| Every amount is stored as an **integer number of pesewas** (1 GHS = 100 pesewas) in `*_pesewas` columns. | All money columns |
| Floats are never used: parsing is string-based, arithmetic is integer, percentages are basis points (1% = 100). | `App\Support\Money` |
| Conversion to/from cedis happens only at the edges: form input and display. | Form objects / components; `money()`, `formatted*()` model helpers |

### `App\Support\Money`

| Method | Example | Notes |
|---|---|---|
| `parse(string)` | `"1,200.50"` → `120050` | Commas, ≤ 2 decimals, optional `GH₵`/`GHS`, max GH₵ 9,999,999,999.99. Throws `InvalidArgumentException`. |
| `isValid(string)` | `"12.345"` → `false` | |
| `format(int, bool $withSymbol = true)` | `120050` → `"GH₵ 1,200.50"` | Negative → `-GH₵ 12.30` |
| `toInput(int)` | `120050` → `"1200.50"` | Pre-fills inputs |
| `parsePercent(string)` | `"7.5"` → `750` | 0–100, ≤ 2 decimals, optional `%` |
| `percentOf(int $pesewas, int $basisPoints)` | `percentOf(89999, 750)` → `6750` | `intdiv(pesewas × bp + 5000, 10000)`: **round half up** to the pesewa |

Typing examples: `3450` → 345000 → GH₵ 3,450.00 · `899.99` → 89999 · `0.10` + `0.20` = 30 pesewas exactly · `12.345` rejected.

## Product prices and stock value

- One current selling price per product (`products.price_pesewas` > 0), set by admins (ADR-0012). Price changes affect **future sales only** because each sale line stores its own price (below).
- Stock value = `stock_quantity × price_pesewas` (`Product::stockValuePesewas()`), at selling price — not a cost valuation. Example: 12 × GH₵ 3,450.00 = GH₵ 41,400.00.

## Sale totals (`SaleService::create`)

```
line_total  = unit_price_pesewas × quantity          (unit price copied from the product at sale time)
subtotal    = Σ line_total
discount    = amount in pesewas, or percentOf(subtotal, basis points)
total       = subtotal − discount                     (0 ≤ discount ≤ subtotal)
paid        = Σ payment allocations to this sale
balance     = total − paid                            (never negative)
status      = Paid if paid ≥ total · Part-paid if 0 < paid < total · Unpaid if paid = 0
```

### Worked example — credit sale with a percentage discount

| | Calculation | Pesewas | GH₵ |
|---|---|---|---|
| Samsung 43" TV × 2 | 345000 × 2 | 690000 | 6,900.00 |
| Subtotal | | 690000 | 6,900.00 |
| Discount 10% (admin) | percentOf(690000, 1000) | 69000 | −690.00 |
| **Total** | 690000 − 69000 | 621000 | **6,210.00** |
| Paid now (Mobile Money) | | 200000 | 2,000.00 |
| **Balance on credit** | 621000 − 200000 | 421000 | **4,210.00** → status Part-paid |

### Rounding example

Microwave GH₵ 899.99, discount 7.5% → 899.99 × 0.075 = 67.49925 → `percentOf(89999, 750)` = (89999 × 750 + 5000) div 10000 = 6750 → **GH₵ 67.50**; total GH₵ 832.49.

### Discount rules

- Never more than the subtotal (`SaleException::discountTooLarge`).
- Cashiers: at most `config('sales.cashier_max_discount_percent')` % of the subtotal (default 10%), compared in pesewas: with a GH₵ 1,000.00 subtotal a cashier may give GH₵ 100.00 but not GH₵ 100.01 (`discountNeedsAdmin`). Admins may give up to 100% (a GH₵ 0.00 sale is recorded as Paid).
- The live preview on the New sale screen calls the same `discountFor()` method, so the screen and the saved sale agree.

## Payments at the counter

`0 ≤ payment ≤ total`. If `payment < total` a customer is required (walk-ins pay in full). A positive payment needs a method. The payment is allocated entirely to the new sale.

## Payment allocation (oldest first)

`PaymentService::receive()` applies a payment to the customer's unpaid sales ordered by `sold_at`, then receipt number, filling each before moving on. Overpayment is refused.

### Worked example

Ama owes on three sales (total owed GH₵ 3,500.00):

| Sale | Sold | Total | Already paid | Owed |
|---|---|---|---|---|
| R-000001 | 3 Oct | 1,000.00 | 0.00 | 1,000.00 |
| R-000002 | 10 Oct | 800.00 | 300.00 | 500.00 |
| R-000003 | 20 Oct | 2,000.00 | 0.00 | 2,000.00 |

She pays **GH₵ 1,800.00**:

| Sale | Applied | Now owed | Status |
|---|---|---|---|
| R-000001 | 1,000.00 | 0.00 | Paid |
| R-000002 | 500.00 | 0.00 | Paid |
| R-000003 | 300.00 | 1,700.00 | Part-paid |

Remaining balance GH₵ 1,700.00. Paying GH₵ 1,700.01 afterwards is refused ("The customer only owes GH₵ 1,700.00"). This exact case is `PaymentServiceTest::test_payments_settle_the_oldest_sale_first`.

## Outstanding balances

- Per sale: `Sale::balancePesewas()`.
- Per customer: `Customer::outstandingPesewas()` = Σ (total − paid) over their sales; lists use `Customer::scopeWithBalance()` (one subquery per row) to avoid N+1 queries.
- Dashboard "Owed by customers" = Σ (total − paid) over all sales.

## Consistency under concurrent use

| Risk | Protection |
|---|---|
| Two sales selling the last unit | `SaleService` locks the product rows (`lockForUpdate`) before checking stock, inside the sale transaction |
| Two payments settling the same balance | `PaymentService` locks the customer's unpaid sale rows before computing what is owed |
| Duplicate receipt numbers | `ReceiptNumberService` increments the `counters` row under a lock in the same transaction; a failed sale rolls the number back |
| Double-click / resubmit | Idempotency keys on sales and payments (unique columns); the second request returns the first record |
| Partial writes | Every sale and payment is one `DB::transaction()`; any exception rolls back stock, receipts, payments and allocations |

Note: SQLite (used locally and in tests) serialises writers instead of honouring `lockForUpdate`, which gives the same guarantee; on MySQL/PostgreSQL the row locks apply.

## Not yet implemented

Refunds · reversals · cancelled sales · promotions/promotional prices · customer credit balances (overpayment) · cost price and margins · tax.
