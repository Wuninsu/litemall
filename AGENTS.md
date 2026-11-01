# AGENTS.md

Primary instructions for AI coding agents working on this repository. Keep this file short; detail lives in [docs/](docs/README.md).

> **Code is the source of truth.** If this file or anything in `docs/` disagrees with the code, identify the discrepancy, establish the intended behaviour, fix the docs or the code, and record it in `docs/CHANGELOG.md` (and `docs/DECISIONS.md` if it is a design change).

## 1. Project overview

"Mall" is a staff-only sales, customer-credit and payments application priced in Ghana Cedis (GHS). **Built so far (Phase 1):** custom authentication (sign-in, sign-out, password reset), Admin/Cashier roles, and admin user management. Customers, products, sales and payments are not built yet. Read [docs/PROJECT_STATUS.md](docs/PROJECT_STATUS.md) before starting any task.

## 2. Technology stack (installed, verified)

| Layer | Package / tool | Version |
|---|---|---|
| Language | PHP | 8.4 locally (`composer.json` requires `^8.3`) |
| Framework | laravel/framework | 13.x |
| UI | livewire/livewire (class-based components) | 4.4 |
| Frontend build | Vite 8, Tailwind CSS 4 | see `package.json` |
| Tests | PHPUnit 12 + Livewire test helpers | `phpunit.xml` |
| Formatting | Laravel Pint | `vendor/bin/pint` |
| Agent tooling | laravel/boost (dev) | guidelines in `CLAUDE.md` |
| Database | SQLite locally; in-memory SQLite for tests | |

No auth starter kit, Flux, or permissions package — do not add dependencies without user approval and a `docs/DECISIONS.md` entry.

## 3. Architecture rules

- Thin entry points (Livewire components, controllers, console commands, jobs) → **services** (`app/Services`) → Eloquent models. See [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md).
- Business rules live in services so every entry point shares one implementation (e.g. `UserService::create()` serves both the UI and `app:create-admin-user`).
- Rule violations throw subclasses of `App\Exceptions\DomainException`; entry points catch them and show the message.
- *Who* may act → policies. *Whether the action is allowed by the business* → services.
- Read-then-write rules use `DB::transaction()` + `lockForUpdate()`.

## 4. Directory structure

```text
app/Console/Commands       artisan commands (CreateAdminUser)
app/Enums                  backed enums (UserRole)
app/Exceptions             DomainException + Auth/, Users/ subclasses
app/Http/Controllers       LogoutController (only non-Livewire endpoint)
app/Http/Middleware        EnsureUserIsActive (alias `active`)
app/Livewire               components by area (Auth/, Users/), Forms/ for form objects
app/Models                 Eloquent models (User)
app/Policies               UserPolicy
app/Services               UserService, Auth/LoginService, Auth/PasswordResetService
resources/views/layouts    app.blade.php (signed-in), guest.blade.php
resources/views/livewire   component views
routes/web.php             all routes (Route::livewire for pages)
tests/Feature/{Auth,Users,Services,Console}
docs/                      technical knowledge base
```

## 5. Service-layer requirements

- One service per business capability; dependencies by injection; no HTTP/Livewire concerns inside services.
- Services do not authorise — callers do, via policies, before calling.
- Every public service method has tests and an entry in [docs/SERVICE_LAYER.md](docs/SERVICE_LAYER.md).

## 6. UUID requirements (ADR-0002, Accepted)

Every application table, including `users`, uses `$table->uuid('id')->primary()` and the model uses `HasUuids`. References use `foreignUuid()`. Framework infrastructure tables keep defaults. See [docs/UUID_STRATEGY.md](docs/UUID_STRATEGY.md).

## 7. Database conventions

- New schema changes go in **new** migrations (`php artisan make:migration --no-interaction`). The default users migration was edited once in Phase 1 before any shared deployment; don't do that again.
- **Money = integer pesewas** (ADR-0005): `bigInteger`/`unsignedBigInteger` columns suffixed `_pesewas`. Never floats, never decimals.
- Users and financial records are never hard-deleted — deactivate / reverse instead.
- Emails are stored lower-case.

## 8. Financial business rules

None implemented yet. Proposed rules (oldest-first payment allocation, historical price snapshots, reversal-based refunds) are ADR-0004/0006 in [docs/DECISIONS.md](docs/DECISIONS.md) — confirm with the user before building sales/payments. When implemented, document them in `docs/BUSINESS_RULES.md` and `docs/FINANCIAL_LOGIC.md` with GHS worked examples.

## 9. Security requirements

- Signed-in routes use `auth` + `active` middleware; admin areas add `can:` middleware.
- Use Blade `{{ }}` only; Eloquent/query-builder bindings only.
- Rate-limit authentication and future financial actions (see `LoginService` for the pattern).
- See [docs/SECURITY.md](docs/SECURITY.md) and [docs/AUTHENTICATION_AND_AUTHORIZATION.md](docs/AUTHENTICATION_AND_AUTHORIZATION.md).

## 10. Livewire development rules

- Class-based components (`php artisan make:livewire Area/Name --no-interaction` — config produces class + view). Full pages via `Route::livewire()`; default layout `layouts::app`, guest pages use `#[Layout('layouts::guest')]`.
- Call `$this->authorize()` in `mount()` **and** every mutating action — Livewire update requests bypass most route middleware.
- Public properties are client-modifiable: mark ids/tokens `#[Locked]`; use Form objects (`app/Livewire/Forms`) for input validation.
- No business logic or money arithmetic in components. Catalogue: [docs/LIVEWIRE_COMPONENTS.md](docs/LIVEWIRE_COMPONENTS.md).

## 11. Testing requirements

- Feature tests by default (`php artisan make:test --phpunit --no-interaction NameTest`); use factory states `admin()`, `cashier()`, `inactive()`.
- Test both route-level and component-level authorisation for each role.
- Every financial rule needs tests for normal, boundary (zero, exact, overpayment) and failure cases.
- A feature is not complete until its tests pass and docs are updated. See [docs/TESTING_GUIDE.md](docs/TESTING_GUIDE.md).

## 12. Documentation requirements

Docs are part of the implementation. Mandatory updates:

| When you change… | Update |
|---|---|
| a migration | `docs/DATABASE_SCHEMA.md` |
| a model relationship | `docs/DATABASE_RELATIONSHIPS.md` |
| a service | `docs/SERVICE_LAYER.md` |
| a Livewire component's public behaviour | `docs/LIVEWIRE_COMPONENTS.md` |
| a business / financial rule | `docs/BUSINESS_RULES.md`, `docs/FINANCIAL_LOGIC.md` |
| security / auth behaviour | `docs/SECURITY.md`, `docs/AUTHENTICATION_AND_AUTHORIZATION.md` |
| offline architecture | `docs/OFFLINE_READINESS.md` |
| an architectural choice | `docs/DECISIONS.md` (new ADR; never silently change one) |
| any feature added/removed | `docs/CHANGELOG.md` (`[Unreleased]`) |
| install/deploy steps | `docs/DEVELOPMENT_GUIDE.md`, `docs/DEPLOYMENT_GUIDE.md` |
| a milestone | `docs/PROJECT_STATUS.md` + handover summary |

Create a listed-but-missing doc (see `docs/README.md`) when the first feature it describes is implemented — not before. Full requirement: `prompt.md`.

## 13. Commands

```sh
composer run dev                         # server + Vite + queue + logs
php artisan test --compact               # all tests (44 passing at end of Phase 1)
php artisan test --compact --filter=Name # narrow run
vendor/bin/pint --dirty --format agent   # format changed PHP files (required before finishing)
php artisan migrate:fresh --seed         # local reset; seeds admin@example.com / cashier@example.com (password: password)
php artisan app:create-admin-user        # create an administrator
php artisan route:list                   # (Livewire page routes are hidden by --except-vendor)
npm run build                            # frontend assets
```

## 14. Key files

- `prompt.md` — the documentation mandate this structure follows
- `CLAUDE.md` — Laravel Boost generated coding guidelines (regenerate with `php artisan boost:install --guidelines`)
- [docs/PROJECT_OVERVIEW.md](docs/PROJECT_OVERVIEW.md), [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md), [docs/PROJECT_STATUS.md](docs/PROJECT_STATUS.md) — read these first
- [docs/BUSINESS_RULES.md](docs/BUSINESS_RULES.md), [docs/DECISIONS.md](docs/DECISIONS.md)
