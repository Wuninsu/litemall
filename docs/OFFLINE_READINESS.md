# Offline Readiness

_As of 2026-11-01. The application works **online only**. This document separates what is already in place to make offline operation possible later from what is only planned (ADR-0007, Proposed)._

## Implemented

| Piece | Where | Why it matters offline |
|---|---|---|
| UUID primary keys on every application table | ADR-0002, [UUID_STRATEGY.md](UUID_STRATEGY.md) | A device can create a sale, line or payment id without asking the server |
| Idempotency keys on sales and payments | `sales.idempotency_key`, `payments.idempotency_key` (unique, nullable); generated per form in `Sales\Create` and `Payments\Index`; checked first inside `SaleService::create()` / `PaymentService::receive()` | Re-sending the same operation (retry, sync replay, double click) returns the original record instead of duplicating money or stock movements |
| All financial writes go through services | `SaleService`, `PaymentService` | A future sync endpoint can call the same code and get the same validation |
| Receipt numbers are separate from ids | `sales.receipt_number`, `counters` table | Human-facing numbers can be issued by the server when a sale syncs |

## Planned (not built)

- **Device identification:** a `devices` table and a device id on sales/payments.
- **Local storage:** IndexedDB stores for products (with prices and stock), customers (with balances), and an outbox of pending sales/payments.
- **Service worker:** cache the app shell and assets; queue writes while offline.
- **Synchronisation:** POST the outbox to an API endpoint that calls the existing services with each record's idempotency key; pull updated products/customers.
- **Conflict rules (proposed):** server is the source of truth for prices, stock and balances. An offline sale that would make stock negative or uses a deactivated product is flagged for an administrator rather than silently rejected. Offline payments can only be applied once the server confirms the balance.
- **Offline receipt numbers:** a provisional device number, replaced by the `R-` number at sync.

None of the planned items exist; do not assume them in code.
