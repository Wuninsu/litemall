# Module: Customers

_Implemented in Phase 2 (2026-11-01)._

## Purpose

Keep a register of the people the shop sells to, so future sales and instalment payments can be tied to a person and their outstanding balance tracked.

## Who can do what

| Action | Admin | Cashier |
|---|---|---|
| View / search the list | ✓ | ✓ |
| Add a customer | ✓ | ✓ |
| Edit a customer | ✓ | ✓ |
| Deactivate / reactivate | ✓ | — |

Enforced by `App\Policies\CustomerPolicy` (`viewAny`, `create`, `update`, `changeStatus`), checked on the route and in every Livewire action.

## Data

Table `customers` (UUID key): `name`, `phone` (unique, stored `+233XXXXXXXXX`), `email`, `address`, `notes`, `is_active`, `created_by` → `users.id`. Full column list in [DATABASE_SCHEMA.md](../DATABASE_SCHEMA.md).

## Rules

See C1–C6 in [BUSINESS_RULES.md](../BUSINESS_RULES.md). In short: name and a valid Ghana phone number are required; one customer per phone number, however it is typed; customers are deactivated, never deleted; the staff member who added them is recorded.

## Code map

| Piece | File |
|---|---|
| Model | `app/Models/Customer.php` (`creator()`, `formattedPhone()`, `scopeActive`) |
| Service | `app/Services/CustomerService.php` |
| Exceptions | `app/Exceptions/Customers/CustomerException.php` |
| Phone helper | `app/Support/PhoneNumber.php` |
| Policy | `app/Policies/CustomerPolicy.php` |
| Screen | `app/Livewire/Customers/Index.php` + `resources/views/livewire/customers/index.blade.php` |
| Form | `app/Livewire/Forms/CustomerForm.php` |
| Route | `GET /customers` → `customers.index` |
| Factory | `database/factories/CustomerFactory.php` (`inactive()` state) |
| Tests | `tests/Feature/Customers/CustomerManagementTest.php`, `tests/Feature/Services/CustomerServiceTest.php`, `tests/Unit/PhoneNumberTest.php` |

## Screen behaviour

- List: name (+ email), phone shown as `024 412 3456`, location, status, date added; 15 per page.
- Search box matches name, email, and phone however it is typed (`0244 12`, `+233 24`, `412-3456`).
- Status filter defaults to **Active**; options Inactive and All.
- "New customer" and each row's edit button open the same right-hand panel (Contact · Details · Status).

## Not yet implemented

Customer balances and purchase history (need sales and payments), customer codes/numbers, bulk import, merging duplicates.
