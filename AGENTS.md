# AGENTS.md

Primary instructions for AI coding agents working on this repository. Keep this file short; detail lives in [docs/](docs/README.md).

> **Code is the source of truth.** If this file or anything in `docs/` disagrees with the code, identify the discrepancy, establish the intended behaviour, fix the docs or the code, and record it in `docs/CHANGELOG.md` (and `docs/DECISIONS.md` if it is a design change).

## 1. Project overview

"Mall" is a planned sales, customer-credit and payments management application priced in Ghana Cedis (GHS). **As of 2026-11-01 it is an unmodified Laravel 13 skeleton** — no business features exist yet. See [docs/PROJECT_STATUS.md](docs/PROJECT_STATUS.md) before starting any task.

## 2. Technology stack (installed, verified)

| Layer | Package / tool | Version |
|---|---|---|
| Language | PHP | 8.4 locally (`composer.json` requires `^8.3`) |
| Framework | laravel/framework | 13.x |
| Frontend build | Vite 8, Tailwind CSS 4, laravel-vite-plugin 3 | see `package.json` |
| Tests | PHPUnit 12 | `phpunit.xml` |
| Formatting | Laravel Pint | `vendor/bin/pint` |
| Agent tooling | laravel/boost (dev) | guidelines in `CLAUDE.md` |
| Database | SQLite (`database/database.sqlite`) locally; in-memory SQLite for tests | |

**Not yet installed:** Livewire 4, any auth starter kit, any roles/permissions package. Do not write code that assumes them; adding a dependency requires user approval and a `docs/DECISIONS.md` entry.

## 3. Architecture rules

- Thin entry points (Livewire components, controllers, console commands, jobs) → **services** (`app/Services`) → Eloquent models.
- All business and financial logic lives in services so every entry point (UI, API, imports, future offline sync) shares one implementation.
- Multi-row financial writes run inside `DB::transaction()` with row locking where balances are read-then-written.
- See [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md). Planned rules not yet backed by code are marked *Proposed* in [docs/DECISIONS.md](docs/DECISIONS.md).

## 4. Directory structure

```text
app/Http/Controllers   HTTP controllers (only the abstract base exists)
app/Models             Eloquent models (User only)
app/Providers          Service providers
app/Services           (planned) business-logic services
bootstrap/app.php      routing, middleware, exception config
database/migrations    schema — keep docs/DATABASE_SCHEMA.md in sync
routes/web.php         web routes; routes/console.php artisan closures
tests/Unit, tests/Feature
docs/                  technical knowledge base
```

## 5. Service-layer requirements

- One service per business capability (e.g. `SaleService`, `PaymentService`); constructor-injected dependencies.
- Services validate business invariants and throw domain exceptions; components only validate input shape and display errors.
- Every public service method gets tests and an entry in [docs/SERVICE_LAYER.md](docs/SERVICE_LAYER.md).

## 6. UUID requirements (Proposed — see ADR-0002)

Existing framework tables use auto-increment `bigint` ids. New business tables are intended to use UUID primary keys (`HasUuids`) to support future offline record creation. Confirm ADR-0002 status before the first business migration.

## 7. Database conventions

- Create migrations with `php artisan make:migration --no-interaction`; never edit a migration that has run in a shared environment — add a new one.
- Store money as integer pesewas (1 GHS = 100 pesewas) or `decimal(…, 2)` — decide in ADR-0005 before the first money column. Never use floats.
- Financial records are never hard-deleted; use status changes/reversals (ADR-0004, Proposed).

## 8. Financial business rules

None are implemented yet. Intended rules (oldest-first payment allocation, historical price snapshots, reversal-based refunds) are recorded as *Proposed* ADRs. When implemented, document them in `docs/BUSINESS_RULES.md` and `docs/FINANCIAL_LOGIC.md` with GHS worked examples.

## 9. Security requirements

- Every state-changing route/action must be authenticated and authorised (policies/gates) once auth exists.
- Rely on Laravel's CSRF middleware, Eloquent/query-builder bindings and Blade `{{ }}` escaping; never use `{!! !!}` with user data or raw SQL with interpolation.
- Rate-limit login and financial endpoints. See [docs/SECURITY.md](docs/SECURITY.md).

## 10. Livewire development rules (apply once Livewire is installed)

- Components hold UI state and call services; no queries with business meaning, no balance arithmetic.
- Authorise in every action (`$this->authorize(...)`), not only in `mount()`.
- Never trust public properties — they are client-modifiable. Lock ids with `#[Locked]`.

## 11. Testing requirements

- Feature tests by default (`php artisan make:test --phpunit --no-interaction NameTest`); `--unit` for pure logic.
- Every financial rule needs tests covering normal, boundary (zero, exact, overpayment) and failure cases.
- A feature is not complete until its tests pass and docs are updated.

## 12. Documentation requirements

Docs are part of the implementation. Mandatory updates:

| When you change… | Update |
|---|---|
| a migration | `docs/DATABASE_SCHEMA.md` |
| a model relationship | `docs/DATABASE_RELATIONSHIPS.md` |
| a service | `docs/SERVICE_LAYER.md` |
| a Livewire component's public behaviour | `docs/LIVEWIRE_COMPONENTS.md` |
| a business / financial rule | `docs/BUSINESS_RULES.md`, `docs/FINANCIAL_LOGIC.md` |
| security behaviour | `docs/SECURITY.md` |
| offline architecture | `docs/OFFLINE_READINESS.md` |
| an architectural choice | `docs/DECISIONS.md` (new ADR; never silently change one) |
| any feature added/removed | `docs/CHANGELOG.md` (`[Unreleased]`) |
| install/deploy steps | `docs/DEVELOPMENT_GUIDE.md`, `docs/DEPLOYMENT_GUIDE.md` |
| a milestone | `docs/PROJECT_STATUS.md` + handover summary |

Create a listed-but-missing doc (see `docs/README.md`) when the first feature it describes is implemented — not before. Full requirement: `prompt.md`.

## 13. Commands

```sh
composer run dev                         # server + Vite + queue + logs (php artisan dev)
php artisan test --compact               # all tests
php artisan test --compact --filter=Name # narrow run
vendor/bin/pint --dirty --format agent   # format changed PHP files (required before finishing)
php artisan migrate                      # apply migrations
php artisan migrate:status
php artisan route:list --except-vendor
npm install && npm run build             # frontend assets (node_modules not yet installed)
```

## 14. Key files

- `prompt.md` — the documentation mandate this structure follows
- `CLAUDE.md` — Laravel Boost generated coding guidelines (regenerated by `php artisan boost:install`)
- [docs/PROJECT_OVERVIEW.md](docs/PROJECT_OVERVIEW.md), [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md), [docs/PROJECT_STATUS.md](docs/PROJECT_STATUS.md) — read these first
