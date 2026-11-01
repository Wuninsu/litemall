# Module: Sales

_Implemented in Phase 3 (2026-11-01). Full description: [SALES_MANAGEMENT.md](../SALES_MANAGEMENT.md)._

## Who can do what

| Action | Admin | Cashier |
|---|---|---|
| Record a sale, view sales and receipts | ✓ | ✓ |
| Discount up to 10% of subtotal (configurable) | ✓ | ✓ |
| Discount above the cashier limit (up to 100%) | ✓ | — |
| Edit, refund or cancel a sale | not built | not built |

`SalePolicy` allows `viewAny`, `view`, `create` to all signed-in staff; the discount limit is enforced in `SaleService::discountFor()`.

## Data

`sales` (receipt number, customer, seller, subtotal/discount/total/paid in pesewas, status, notes, idempotency key, sold_at) and `sale_lines` (product snapshot: name, SKU, unit price; quantity; line total). See [DATABASE_SCHEMA.md](../DATABASE_SCHEMA.md).

## Configuration

`config/sales.php`: `cashier_max_discount_percent` (env `SALES_CASHIER_MAX_DISCOUNT_PERCENT`, default 10), `receipt_prefix` (`R-`), `receipt_digits` (6).

## Code map

| Piece | File |
|---|---|
| Service | `app/Services/SaleService.php`, `app/Services/ReceiptNumberService.php` |
| Models | `app/Models/Sale.php` (`balancePesewas()`, `money()`, `scopeOutstanding`), `app/Models/SaleLine.php` |
| Enum | `app/Enums/SaleStatus.php` |
| Exception | `app/Exceptions/Sales/SaleException.php` (carries `field`) |
| Screens | `app/Livewire/Sales/{Create,Index,Show}.php` + views |
| Component | `resources/views/components/sale-status.blade.php` |
| Factory | `database/factories/SaleFactory.php` (`amounts($total, $paid)`) — raw rows only; use `SaleService` for real sales |
| Tests | `tests/Feature/Services/SaleServiceTest.php`, `tests/Feature/Sales/SalesScreensTest.php`, `tests/Unit/SaleStatusTest.php` |
