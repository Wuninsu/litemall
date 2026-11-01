# Module: Payments

_Implemented in Phase 3 (2026-11-01). Full description: [PAYMENT_SYSTEM.md](../PAYMENT_SYSTEM.md)._

## Who can do what

| Action | Admin | Cashier |
|---|---|---|
| View payments | ✓ | ✓ |
| Receive a payment towards a customer's balance | ✓ | ✓ |
| Refund / reverse a payment | not built | not built |

`PaymentPolicy` allows `viewAny` and `create` to all signed-in staff.

## Data

`payments` (customer — null for walk-in counter payments, received by, amount in pesewas, method, reference, notes, idempotency key, received_at) and `payment_allocations` (payment, sale, amount; unique per payment+sale).

## Code map

| Piece | File |
|---|---|
| Service | `app/Services/PaymentService.php` (`outstandingFor`, `previewAllocation`, `receive`) |
| Models | `app/Models/Payment.php`, `app/Models/PaymentAllocation.php`; `Customer::sales()`, `payments()`, `outstandingPesewas()`, `scopeWithBalance` |
| Enum | `app/Enums/PaymentMethod.php` (`label()`, `expectsReference()`) |
| Exception | `app/Exceptions/Payments/PaymentException.php` |
| Screen | `app/Livewire/Payments/Index.php` + view |
| Factory | `database/factories/PaymentFactory.php` — raw rows without allocations |
| Tests | `tests/Feature/Services/PaymentServiceTest.php`, `tests/Feature/Payments/PaymentsScreenTest.php` |

## Related screens

- Customers list: **Balance** column (links to Payments with the customer preselected) and an **Owing money** filter.
- Receipt page: payments applied to the sale; "Receive payment" button.
- Dashboard: received today, owed by customers.
