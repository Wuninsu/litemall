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
| `App\Exceptions\Products\ProductException` | `invalidPrice()` (≤ 0), `invalidStock()` (< 0), `imageNotSaved()` (storage failure), `skuTaken()` (unique index hit); exposes `$field` (`price` / `stock` / `image` / `sku`) so screens can show the error under the right input |

## Support helpers (`app/Support`)

Stateless helpers used by services, forms and models. Not services: no database access, no container dependencies.

| Class | Purpose | Tests |
|---|---|---|
| `App\Support\Money` | Cedi text ↔ integer pesewas (`parse`, `isValid`, `format`, `toInput`). See [FINANCIAL_LOGIC.md](FINANCIAL_LOGIC.md) | `tests/Unit/MoneyTest.php` |
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

Normalisation: name squished (single spaces), phone → `+233…`, email trimmed + lower-cased, blank optional fields → `null`.

### ProductService
- **File:** [app/Services/ProductService.php](../app/Services/ProductService.php)
- **Responsibility:** maintain products, current price, stock quantity and photo; normalise SKU and category; list categories.
- **Dependencies:** `Product` model, `Storage` (`public` disk, `products/` folder).
- **Used by:** `Products\Index` and `Products\Show` via the `ManagesProductPanel` trait.
- **Related tests:** `tests/Feature/Services/ProductServiceTest.php`, `tests/Feature/Products/ProductManagementTest.php`, `tests/Feature/Products/ProductImageAndStockTest.php`

| Method | Input | Output | Throws | DB / file operations |
|---|---|---|---|---|
| `create(array{name, sku?, category?, price_pesewas, stock_quantity?, description?}, User $actor, ?UploadedFile $image = null)` | price already in pesewas (converted by `ProductForm`) | new active `Product` with `created_by = $actor` | `invalidPrice`, `invalidStock`, `skuTaken`, `imageNotSaved` | store image (if any); insert `products` |
| `update(Product, array{…same}, ?UploadedFile $image = null, bool $removeImage = false)` | new image replaces; `removeImage` clears | updated `Product` | same | store new image; update `products`; **after** a successful save delete the previous file |

File safety: a newly stored image is deleted again if the database save fails (e.g. duplicate SKU), so no orphaned files are left.
| `deactivate(Product)` / `activate(Product)` | | void | — | update `is_active` |
| `categories()` | — | `list<string>` distinct non-null categories, A→Z | — | select distinct |

Normalisation: name squished, SKU trimmed + upper-cased (blank → `null`), category squished + first letter capitalised (blank → `null`), blank description → `null`.

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
