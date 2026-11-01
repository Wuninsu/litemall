# Testing Guide

## Framework

PHPUnit 12 (`phpunit.xml`), run through `php artisan test`, with Livewire's `Livewire::test()` helpers. Suites: `Unit` (`tests/Unit`) and `Feature` (`tests/Feature`). Base class `Tests\TestCase` calls `withoutVite()` so tests never need built assets.

Test environment (from `phpunit.xml`): in-memory SQLite, `array` cache and session, `sync` queue, `array` mailer, `BCRYPT_ROUNDS=4`. Database tests use `RefreshDatabase`.

## Running tests

```sh
php artisan test --compact                                  # everything
php artisan test --compact tests/Feature/Auth/LoginTest.php # one file
php artisan test --compact --filter=test_login_is_throttled_after_five_failed_attempts
php artisan test --testsuite=Unit
composer test                                               # config:clear then artisan test
vendor/bin/phpunit                                          # direct runner, same arguments
```

Current result (2026-11-01): **139 tests, 498 assertions, all passing** (also verified with no `public/build` present).

## Test map

| File | Covers |
|---|---|
| `tests/Feature/ExampleTest.php` | `/up` health check; `/` sends guests to login |
| `tests/Feature/Auth/LoginTest.php` | Sign-in success, case-insensitive email, wrong password, unknown email, inactive user, throttling, sign-out, guest/auth redirects, deactivation mid-session |
| `tests/Feature/Auth/PasswordResetTest.php` | Reset link sent / not revealed for unknown email, IP throttling, reset with valid/invalid token, confirmation mismatch, sessions revoked |
| `tests/Feature/Services/UserServiceTest.php` | `UserService` rules: normalisation, UUIDs, password keep/change, session revocation, own-role, last-admin, self-deactivation, activate |
| `tests/Feature/Users/UserManagementTest.php` | Cashier 403 (route and component), list/search/role/status filters, nav visibility, create via modal + validation, edit via the same modal, empty form after edit→create, own-role error, deactivate/reactivate, self-deactivation forbidden, locked `form.user` cannot be swapped |
| `tests/Feature/DashboardTest.php` | Admins see team statistics with correct counts; cashiers do not |
| `tests/Unit/MoneyTest.php` | Cedi parsing (commas, decimals, prefixes, limits), rejection of invalid input, no float errors, formatting, input round-trip |
| `tests/Unit/PhoneNumberTest.php` | Same Ghana number in 8 formats → one E.164 value; invalid numbers; display format; search fragments |
| `tests/Feature/Services/CustomerServiceTest.php` | Normalisation, creator recorded, invalid phone, duplicate number in another format, update keeps creator, own number on update, (de)activation |
| `tests/Feature/Services/ProductServiceTest.php` | Normalisation, pesewas storage, blank SKUs, zero/negative price, case-insensitive duplicate SKU, price update, categories, (de)activation |
| `tests/Feature/Customers/CustomerManagementTest.php` | Guest redirect, both roles see list, search by name/email/phone formats, status filter, add/edit via panel, validation, duplicate number, cashier cannot deactivate, admin (de)activates, locked `form.customer` |
| `tests/Feature/Products/ProductManagementTest.php` | Cashier read-only list and 403 on actions, filters, admin adds with cedi price, price validation, duplicate SKU, edit pre-fills and saves price, (de)activation, sidebar and dashboard links |
| `tests/Feature/Products/ProductImageAndStockTest.php` | Photo upload, rejected files (PDF, SVG, >2 MB), stock validation, photo replace/remove deletes old file, no orphaned file on failed save, stock levels and value, stock filter, details page for cashier (read-only) and admin (edit in place), list links, 404 |
| `tests/Feature/Components/ButtonTest.php` | `x-button` rendering: plain button, loader wiring for `wire:click` (with escaped arguments), submit + `loading-text`, submit without target, loader off, link mode |
| `tests/Feature/Console/CreateAdminUserTest.php` | Command with options, interactive prompts, invalid input |

## Strategy

| Kind | Where | Use for |
|---|---|---|
| Feature tests (default) | `tests/Feature/<Area>` | Routes, Livewire components, anything touching the database |
| Service tests | `tests/Feature/Services` | Every public service method: success, each domain exception, side effects (sessions, locks) |
| Livewire tests | `tests/Feature/<Area>` | `Livewire::test()` — validation errors, `assertForbidden()` for wrong roles, redirects, service outcome in DB |
| Security tests | alongside features | Guests redirected, wrong role 403 (both route and component), throttling, enumeration-safe messages |
| Unit tests | `tests/Unit` | Pure calculations (e.g. future money helpers) |

Data: use factories and their states — `User::factory()->admin()`, `->cashier()`, `->inactive()`, `->unverified()` (password `password`); `Customer::factory()->inactive()`; `Product::factory()->inactive()`, `->priced(189950)` (pesewas). Unit tests in `tests/Unit` extend PHPUnit's `TestCase` directly (no Laravel boot) and use data providers.

## Financial test scenarios (to implement with the features)

Each money feature must cover, in GHS/pesewas: exact payment, partial payment, overpayment handling, zero and negative amounts rejected, allocation across several outstanding sales oldest-first, refund after partial payment, cancellation after payment, rounding to the pesewa, and two concurrent payments against the same balance.
