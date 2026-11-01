# Testing Guide

## Framework

PHPUnit 12 (`phpunit.xml`), run through `php artisan test`. Suites: `Unit` (`tests/Unit`) and `Feature` (`tests/Feature`). Base class `Tests\TestCase`.

Test environment (from `phpunit.xml`): in-memory SQLite, `array` cache and session, `sync` queue, `array` mailer, `BCRYPT_ROUNDS=4`.

## Running tests

```sh
php artisan test --compact                          # everything
php artisan test --compact tests/Feature/ExampleTest.php
php artisan test --compact --filter=test_the_application_returns_a_successful_response
php artisan test --testsuite=Unit
composer test                                       # config:clear then artisan test
vendor/bin/phpunit                                  # direct runner, same arguments
```

Current result (2026-11-01): **2 tests, 2 passed** (the framework example tests).

## Strategy

| Kind | Where | Use for |
|---|---|---|
| Feature tests (default) | `tests/Feature` | HTTP routes, Livewire components, services hitting the database |
| Unit tests | `tests/Unit` | Pure calculations with no framework/database dependency |
| Service tests | `tests/Feature/Services` (planned) | Every public service method: success, rule violations, transaction rollback |
| Livewire tests | `tests/Feature/Livewire` (planned) | `Livewire::test()` — rendering, validation, authorisation, service call outcome |
| Security tests | alongside features | Guests redirected, wrong role gets 403, CSRF-protected forms, mass-assignment ignored |

Create tests with `php artisan make:test --phpunit --no-interaction NameTest` (add `--unit` for unit tests). Use `RefreshDatabase` for database tests and model factories for data (`database/factories/UserFactory.php` is the only factory today).

## Financial test scenarios (to implement with the features)

Each money feature must cover, in GHS: exact payment, partial payment, overpayment rejection/handling, zero and negative amounts rejected, allocation across several outstanding sales oldest-first, refund after partial payment, cancellation after payment, rounding to the pesewa, and two concurrent payments against the same balance.
