# Payment System

_Implemented in Phase 3 (2026-11-01)._

## Two ways money comes in

| When | Entry point | Service | Applied to |
|---|---|---|---|
| At the counter, during a sale | New sale screen | `SaleService::create()` | That sale only |
| Later, towards what a customer owes | Payments → "Receive payment" | `PaymentService::receive()` | The customer's unpaid sales, **oldest first** (ADR-0006) |

Both create a `payments` row and one or more `payment_allocations` rows. A sale's `paid_pesewas` equals the sum of its allocations minus any refunds on its returns (ADR-0020), and its `status` is recomputed by `Sale::recalculateStatus()`. Balances use `total − returned − paid`; cancelled sales owe nothing and are skipped by allocation.

## Oldest-first allocation

Unpaid sales of the customer are taken in `sold_at` order (then receipt number) and each is filled before moving to the next. Worked example in [FINANCIAL_LOGIC.md](FINANCIAL_LOGIC.md#payment-allocation-oldest-first). The panel shows the split ("Will be applied to …, Settled / Part-paid") before saving, using `previewAllocation()`, which runs the same algorithm without writing.

## Rules (BUSINESS_RULES.md PY1–PY7)

- Amount must be greater than GH₵ 0.00 and **not more than the customer owes** (no credit balances, no change recorded).
- Only customers who owe money are offered in the panel; paying a customer who owes nothing is refused.
- Method required: Cash, Mobile Money, Bank transfer or Card; optional reference (e.g. MoMo transaction ID) and notes.
- Payments from inactive customers are accepted (they still owe the money).
- Payments are never edited or deleted.

## Concurrency and double submits

- `receive()` locks the customer's unpaid sale rows (`lockForUpdate`) inside a transaction before computing what is owed, so two payments taken at the same moment cannot both settle the same balance.
- Each panel opening generates an idempotency key; resubmitting the same form returns the first payment instead of recording a second one.

## Screen

`/payments` (`payments.index`): list with search (customer or reference), period (today default) and method filters, total received for the period, and receipt chips showing which sales each payment went to. `?customer=<id>` opens the panel with that customer selected (used by the customers list balance link and the receipt page); `?receive=1` opens it empty (dashboard button).

## Code

`app/Services/PaymentService.php`, `app/Models/{Payment,PaymentAllocation}.php`, `app/Enums/PaymentMethod.php`, `app/Exceptions/Payments/PaymentException.php`, `app/Policies/PaymentPolicy.php`, `app/Livewire/Payments/Index.php`. Tests: `tests/Feature/Services/PaymentServiceTest.php`, `tests/Feature/Payments/PaymentsScreenTest.php`.

## Not yet implemented

Refunds and reversals of payments, customer credit balances / overpayment, choosing which sale a payment goes to, payment gateway or MoMo API integration, SMS confirmations.
