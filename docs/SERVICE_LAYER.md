# Service Layer

**Business logic belongs in services, not in Livewire components, controllers, models, or Blade views.** (ADR-0004.) Components and controllers validate input shape, authorise, call one service method and present the result.

## Conventions

- Location: `app/Services/` (grouped by area, e.g. `app/Services/Auth/`). Create with `php artisan make:class Services/<Name>Service --no-interaction`.
- Resolve services from the container (method injection in Livewire actions / controllers, constructor injection elsewhere); never `new` them in entry points.
- Rule violations throw subclasses of `App\Exceptions\DomainException`; entry points catch them and show `getMessage()` to the user. Any other exception is a bug.
- Wrap multi-row writes in `DB::transaction()`; use `lockForUpdate()` when a read decides a write.
- Each service has tests under `tests/Feature/Services/` or covering feature tests.

## Domain exceptions

| Exception | Thrown when |
|---|---|
| `App\Exceptions\Auth\InvalidCredentialsException` | Unknown email or wrong password (same message for both: `auth.failed`) |
| `App\Exceptions\Auth\AccountInactiveException` | Correct credentials but `is_active = false` |
| `App\Exceptions\Auth\TooManyAttemptsException` | Rate limit hit; carries `retryAfterSeconds` |
| `App\Exceptions\Auth\InvalidPasswordResetException` | Password broker rejects a reset (bad/expired token, unknown email) |
| `App\Exceptions\Users\UserManagementException` | `lastActiveAdmin()`, `cannotDeactivateSelf()`, `cannotChangeOwnRole()` |
| `App\Exceptions\Customers\CustomerException` | `invalidPhone()` (not a Ghana number), `phoneTaken()` (unique index hit, e.g. a race between two saves) |
| `App\Exceptions\Products\ProductException` | `invalidPrice()` (≤ 0), `invalidStock()` (< 0), `imageNotSaved()` (storage failure), `invalidWholesalePrice()` (≤ 0 or above retail), `invalidWholesaleMinimum()` (< 1), `skuTaken()` (unique index hit); exposes `$field` (`price`, `wholesalePrice`, `wholesaleMinQuantity`, `stock`, `image`, `sku`) so screens can show the error under the right input |

| `App\Exceptions\Sales\SaleException` | Sale rule violations; exposes `$field` (`lines`, `customer`, `discount`, `payment`) for the New sale screen. Messages listed under SaleService below |
| `App\Exceptions\Payments\PaymentException` | `amountMustBePositive()`, `nothingOwed()`, `exceedsBalance(int $owed)` |

## Support helpers (`app/Support`)

Stateless helpers used by services, forms and models. Not services: no database access, no container dependencies.

| Class | Purpose | Tests |
|---|---|---|
| `App\Support\Money` | Cedi text ↔ integer pesewas (`parse`, `isValid`, `format`, `toInput`); percentages (`parsePercent` → basis points, `percentOf` with half-up rounding). See [FINANCIAL_LOGIC.md](FINANCIAL_LOGIC.md) | `tests/Unit/MoneyTest.php` |
| `App\Support\PhoneNumber` | Ghana phone numbers: `normalise` → `+233XXXXXXXXX` or null, `format` → `024 412 3456`, `searchFragment` for list search | `tests/Unit/PhoneNumberTest.php` |

---

### LoginService
- **File:** [app/Services/Auth/LoginService.php](../app/Services/Auth/LoginService.php)
- **Responsibility:** verify credentials, enforce active status and login rate limit, sign the user in on the `web` guard.
- **Dependencies:** `Auth`, `Hash`, `RateLimiter` facades; `User` model.
- **Related tests:** `tests/Feature/Auth/LoginTest.php`
- **Not its job:** session regeneration/invalidation (done by `Login` component and `LogoutController`).

| Method | Input | Output | Throws | DB operations |
|---|---|---|---|---|
| `attempt(string $email, string $password, bool $remember, string $ipAddress)` | raw email (trimmed + lower-cased internally) | signed-in `User` | `TooManyAttemptsException`, `InvalidCredentialsException`, `AccountInactiveException` | select user by email; update `last_login_at` (and `remember_token` when remembering) |
| `logout()` | — | void | — | may update `remember_token` |

Rate limit: `MAX_ATTEMPTS = 5` failures per `email|IP` key, decaying after `DECAY_SECONDS = 60`. Failures for inactive accounts also count. A successful sign-in clears the counter.

### PasswordResetService
- **File:** [app/Services/Auth/PasswordResetService.php](../app/Services/Auth/PasswordResetService.php)
- **Responsibility:** self-service password reset via Laravel's password broker.
- **Dependencies:** `Password` broker, `RateLimiter`, `DB`.
- **Related tests:** `tests/Feature/Auth/PasswordResetTest.php`

| Method | Input | Output | Throws | DB operations |
|---|---|---|---|---|
| `sendResetLink(string $email, string $ipAddress)` | email, client IP | void (never reveals whether the account exists) | `TooManyAttemptsException` | upsert `password_reset_tokens`; sends `ResetPassword` notification by mail |
| `reset(string $token, string $email, string $password)` | token from link, email, new password | void | `InvalidPasswordResetException` | update `users.password` + `remember_token`; delete the user's `sessions` rows; delete token; fires `PasswordReset` event |

Rate limit: `MAX_REQUESTS_PER_IP = 5` per 60 s per IP (all requests count). The broker additionally throttles repeat links to the same user to one per 60 s (`config/auth.php`), silently.

### UserService
- **File:** [app/Services/UserService.php](../app/Services/UserService.php)
- **Responsibility:** administrator user management; guarantees at least one active administrator.
- **Dependencies:** `User` model, `DB`.
- **Used by:** `Users\Index` Livewire component (create/edit modal); `app:create-admin-user` command.
- **Related tests:** `tests/Feature/Services/UserServiceTest.php`, `tests/Feature/Users/UserManagementTest.php`, `tests/Feature/Console/CreateAdminUserTest.php`

| Method | Input | Output | Throws | DB operations |
|---|---|---|---|---|
| `create(array{name, email, role, password})` | validated data; `role` as `UserRole` or string | new active `User` | — | insert `users` |
| `update(User $user, array{name, email, role, password?}, User $actor)` | blank/absent password = keep current | updated `User` | `cannotChangeOwnRole`, `lastActiveAdmin` | transaction: lock other active admins (if demoting an active admin), update `users`; if password changed for someone other than `$actor`, delete their `sessions` rows |
| `deactivate(User $user, User $actor)` | | void | `cannotDeactivateSelf`, `lastActiveAdmin` | transaction: lock other active admins (if target is admin), set `is_active=false`, rotate `remember_token`, delete their `sessions` rows |
| `activate(User $user)` | | void | — | set `is_active=true` |

Authorisation (who may call these) is **not** checked here; callers authorise via `UserPolicy` first.

### CustomerService
- **File:** [app/Services/CustomerService.php](../app/Services/CustomerService.php)
- **Responsibility:** register and maintain customers; normalise input; enforce one customer per phone number.
- **Dependencies:** `Customer` model, `App\Support\PhoneNumber`.
- **Used by:** `Customers\Index` Livewire component.
- **Related tests:** `tests/Feature/Services/CustomerServiceTest.php`, `tests/Feature/Customers/CustomerManagementTest.php`

| Method | Input | Output | Throws | DB operations |
|---|---|---|---|---|
| `create(array{name, phone, email?, address?, notes?}, User $actor)` | raw form values; phone in any accepted format | new active `Customer` with `created_by = $actor` | `invalidPhone`, `phoneTaken` | insert `customers` |
| `update(Customer, array{…same})` | | updated `Customer` (creator unchanged) | `invalidPhone`, `phoneTaken` | update `customers` |
| `deactivate(Customer)` / `activate(Customer)` | | void | — | update `is_active` |

Normalisation: name squished (single spaces), phone → `+233…`, email trimmed + lower-cased, blank optional fields → `null`. `is_wholesale` (bool, ADR-0019) is stored as given; on update it is left unchanged when omitted.

### ProductService
- **File:** [app/Services/ProductService.php](../app/Services/ProductService.php)
- **Responsibility:** maintain products, current price, stock quantity and photo; normalise SKU and category; list categories.
- **Dependencies:** `Product` model, `Storage` (`public` disk, `products/` folder).
- **Used by:** `Products\Index` and `Products\Show` via the `ManagesProductPanel` trait.
- **Related tests:** `tests/Feature/Services/ProductServiceTest.php`, `tests/Feature/Products/ProductManagementTest.php`, `tests/Feature/Products/ProductImageAndStockTest.php`

| Method | Input | Output | Throws | DB / file operations |
|---|---|---|---|---|
| `create(array{name, sku?, category?, price_pesewas, wholesale_price_pesewas?, wholesale_min_quantity?, stock_quantity?, description?}, User $actor, ?UploadedFile $image = null)` | price already in pesewas (converted by `ProductForm`) | new active `Product` with `created_by = $actor` | `invalidPrice`, `invalidStock`, `skuTaken`, `imageNotSaved` | store image (if any); insert `products` |
| `update(Product, array{…same}, ?UploadedFile $image = null, bool $removeImage = false)` | new image replaces; `removeImage` clears | updated `Product` | same | store new image; update `products`; **after** a successful save delete the previous file |

File safety: a newly stored image is deleted again if the database save fails (e.g. duplicate SKU), so no orphaned files are left.
| `deactivate(Product)` / `activate(Product)` | | void | — | update `is_active` |
| `categories()` | — | `list<string>` distinct non-null categories, A→Z | — | select distinct |

Normalisation: name squished, SKU trimmed + upper-cased (blank → `null`), category squished + first letter capitalised (blank → `null`), blank description → `null`. Wholesale (ADR-0019): `wholesale_price_pesewas` null or 0 < value ≤ `price_pesewas` (`invalidWholesalePrice`), `wholesale_min_quantity` ≥ 1 (`invalidWholesaleMinimum`); omitted keys keep the current values.

### SaleService
- **File:** [app/Services/SaleService.php](../app/Services/SaleService.php)
- **Responsibility:** record a complete sale atomically: lines with price snapshots, discount, totals, stock reduction, receipt number, payment taken at the counter. See [SALES_MANAGEMENT.md](SALES_MANAGEMENT.md).
- **Dependencies:** `ReceiptNumberService` (constructor), models `Sale`, `SaleLine`, `Product`, `Customer`, `Payment`, `PaymentAllocation`, `App\Support\Money`, `config('sales.*')`.
- **Used by:** `Sales\Create`, `DatabaseSeeder`.
- **Related tests:** `tests/Feature/Services/SaleServiceTest.php`, `tests/Feature/Sales/SalesScreensTest.php`

| Method | Input | Output | Throws | DB operations |
|---|---|---|---|---|
| `create(array $input, User $actor)` | `customer_id?`, `lines` (`product_id`, `quantity`), `discount_type?` (`amount`/`percent`), `discount_value?` (pesewas or basis points), `discount_reason?`, `payment_pesewas?`, `payment_method?`, `payment_reference?`, `notes?`, `idempotency_key?`, `price_tier?` (`retail` default / `wholesale`; each line priced by `Product::unitPriceFor()`, ADR-0019) | `Sale` with `lines` loaded | `SaleException` (`noLines`, `invalidQuantity`, `productUnavailable`, `insufficientStock`, `customerUnavailable`, `creditNeedsCustomer`, `discountTooLarge`, `discountNeedsAdmin`, `overpayment`, `paymentMethodRequired`) | One transaction: lookup by idempotency key; `lockForUpdate` on products; counter increment; insert `sales`, `sale_lines`; decrement `products.stock_quantity`; optional insert `payments` + `payment_allocations`; update `sales.paid_pesewas/status` |
| `discountFor(int $subtotal, ?string $type, int $value, User $actor)` | | discount in pesewas | `discountTooLarge`, `discountNeedsAdmin` | none (also used for the live preview) |

### PaymentService
- **File:** [app/Services/PaymentService.php](../app/Services/PaymentService.php)
- **Responsibility:** receive payments towards a customer's balance and allocate them oldest-first. See [PAYMENT_SYSTEM.md](PAYMENT_SYSTEM.md).
- **Dependencies:** models `Sale`, `Payment`, `Customer`.
- **Used by:** `Payments\Index`, `DatabaseSeeder`.
- **Related tests:** `tests/Feature/Services/PaymentServiceTest.php`, `tests/Feature/Payments/PaymentsScreenTest.php`

| Method | Input | Output | Throws | DB operations |
|---|---|---|---|---|
| `outstandingFor(Customer)` | | owed pesewas | — | sum over `sales` |
| `previewAllocation(Customer, int $amount)` | | `list<array{sale, amount}>` | — | read only |
| `receive(Customer, int $amount, PaymentMethod, User $actor, ?string $reference, ?string $notes, ?string $idempotencyKey)` | amount in pesewas | `Payment` with `allocations.sale` | `PaymentException` (`amountMustBePositive`, `nothingOwed`, `exceedsBalance`) | One transaction: idempotency lookup; `lockForUpdate` on the customer's unpaid sales; insert `payments`, `payment_allocations`; update each sale's `paid_pesewas` and `status` |

### ReceiptNumberService
- **File:** [app/Services/ReceiptNumberService.php](../app/Services/ReceiptNumberService.php)
- **Responsibility:** issue the next receipt number (`R-000001`…) from the `counters` table.
- **Used by:** `SaleService` (inside its transaction, so a rolled-back sale does not consume a number).

| Method | Output | DB operations |
|---|---|---|
| `next()` | `string` | insert-or-ignore counter row; `lockForUpdate`; increment |

## Catalogue entry template

```markdown
### <ServiceName>
- **File:** app/Services/<ServiceName>.php
- **Responsibility:**
- **Dependencies:**
- **Interacts with services:**
- **Related tests:**

| Method | Input | Output | Throws | DB operations |
|---|---|---|---|---|
```
