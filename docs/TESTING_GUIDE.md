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

Current result (2026-11-01): **44 tests, 154 assertions, all passing.**

## Test map

| File | Covers |
|---|---|
| `tests/Feature/ExampleTest.php` | `/up` health check; `/` sends guests to login |
| `tests/Feature/Auth/LoginTest.php` | Sign-in success, case-insensitive email, wrong password, unknown email, inactive user, throttling, sign-out, guest/auth redirects, deactivation mid-session |
| `tests/Feature/Auth/PasswordResetTest.php` | Reset link sent / not revealed for unknown email, IP throttling, reset with valid/invalid token, confirmation mismatch, sessions revoked |
| `tests/Feature/Services/UserServiceTest.php` | `UserService` rules: normalisation, UUIDs, password keep/change, session revocation, own-role, last-admin, self-deactivation, activate |
| `tests/Feature/Users/UserManagementTest.php` | Cashier 403s (routes and direct component), list/search, nav visibility, create + validation, edit, own-role error, deactivate/activate, self-deactivation forbidden |
| `tests/Feature/Console/CreateAdminUserTest.php` | Command with options, interactive prompts, invalid input |

## Strategy

| Kind | Where | Use for |
|---|---|---|
| Feature tests (default) | `tests/Feature/<Area>` | Routes, Livewire components, anything touching the database |
| Service tests | `tests/Feature/Services` | Every public service method: success, each domain exception, side effects (sessions, locks) |
| Livewire tests | `tests/Feature/<Area>` | `Livewire::test()` — validation errors, `assertForbidden()` for wrong roles, redirects, service outcome in DB |
| Security tests | alongside features | Guests redirected, wrong role 403 (both route and component), throttling, enumeration-safe messages |
| Unit tests | `tests/Unit` | Pure calculations (e.g. future money helpers) |

Data: use factories and their states — `User::factory()->admin()`, `->cashier()`, `->inactive()`, `->unverified()`. Factory password is `password`.

## Financial test scenarios (to implement with the features)

Each money feature must cover, in GHS/pesewas: exact payment, partial payment, overpayment handling, zero and negative amounts rejected, allocation across several outstanding sales oldest-first, refund after partial payment, cancellation after payment, rounding to the pesewa, and two concurrent payments against the same balance.
