# Project Overview

## What the application does

**Currently: nothing beyond the Laravel skeleton.** The repository contains a fresh Laravel 13 install serving a welcome page at `/` and a health check at `/up`.

## Intended purpose (Planned)

The requirements in `prompt.md` describe a sales and customer-credit management system for a retail business operating in Ghana Cedis (GHS):

- Customer and product registration.
- Sales with discounts and promotional prices, preserving historical prices.
- Initial and subsequent (instalment) payments, allocated against outstanding sales.
- Outstanding balance tracking.
- Refunds and cancellations that preserve financial history.
- Receipt numbering.
- Dashboard and reporting.
- Bulk imports.
- Design that allows future offline operation (UUIDs, idempotent sync).

**Business problem (inferred from the requirements, to be confirmed with the owner):** accurately tracking what each customer bought, what they have paid, and what they still owe, with an auditable financial history.

None of the above is implemented. See [PROJECT_STATUS.md](PROJECT_STATUS.md).

## User roles

None are implemented. There is a `users` table and `User` model, but no login, registration or role system. Roles will be documented in [AUTHENTICATION_AND_AUTHORIZATION.md](AUTHENTICATION_AND_AUTHORIZATION.md) when defined.

## Technology stack

| Component | Installed version | Notes |
|---|---|---|
| PHP | 8.4.18 (local, Laragon) | `composer.json` requires `^8.3` |
| Laravel | 13.34.0 | |
| PHPUnit | 12.5 | |
| Laravel Pint | 1.32 | code style |
| Laravel Boost | 2.10 (dev) | AI agent guidelines in `CLAUDE.md` |
| Laravel Pail, Tinker | dev / runtime tools | |
| Vite / Tailwind CSS | 8 / 4 | declared in `package.json`; `node_modules` not installed yet |
| Database | SQLite | local file `database/database.sqlite` |

**Planned, not installed:** Livewire 4 (implied by `prompt.md`; needs ADR-0003 acceptance).

## System boundaries

- Single Laravel monolith; server-rendered web UI.
- No external integrations (payment gateways, SMS, email providers) are configured. Mail uses the `log` mailer.
- No public API (`routes/api.php` does not exist).

## Current development status

Phase 0 — skeleton plus documentation framework. 2 example tests pass.

## Known limitations

- No authentication, authorisation, or business features.
- Default `APP_NAME=Laravel` has not been changed.
- `composer audit` reports one medium advisory in `symfony/mime` (CVE-2026-45070, email header injection); fix with `composer update symfony/mime` once a patched version is resolvable.

## Planned future improvements

See the *Not yet implemented* list in [PROJECT_STATUS.md](PROJECT_STATUS.md).
