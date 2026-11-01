# Project Status

_Last updated: 2026-11-01_

## Complete

- Laravel 13 skeleton (initial commit `ee40a0b`).
- Default migrations run against local SQLite (`users`, `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`).
- Laravel Boost installed (`laravel/boost` dev dependency, `boost.json`, guidelines written to `CLAUDE.md`).
- Documentation framework: `AGENTS.md` and `docs/`.

## In progress

Nothing.

## Not yet implemented

- Livewire installation and UI layout.
- Authentication (login/logout, password reset) and roles/permissions.
- Customers, products, sales, discounts, promotions.
- Payments, allocation, outstanding balances, receipts.
- Refunds and cancellations.
- Dashboard, reports, bulk imports.
- Audit logging.
- Offline readiness (UUIDs, device ids, idempotency, sync).

## Needs attention

1. **Decide the Proposed ADRs** in [DECISIONS.md](DECISIONS.md) (UUIDs, Livewire 4, money storage, etc.) — they shape the first migrations.
2. `symfony/mime` security advisory CVE-2026-45070 (medium) — run `composer update symfony/mime` when a fixed release is available to this constraint set.
3. `APP_NAME` is still `Laravel`.
4. Frontend dependencies are not installed (`npm install`), so `npm run build` has never been run here.

## Recommended next task

Phase 1 — Foundations:
1. Accept/reject ADR-0002 (UUIDs), ADR-0003 (Livewire 4), ADR-0005 (money storage).
2. Install Livewire and an authentication mechanism; define roles.
3. Implement customers and products (migrations, models, factories, services, components, tests, docs).

## Handover — Phase 0 (2026-11-01)

| Item | Detail |
|---|---|
| Implemented | Documentation framework; Laravel Boost guidelines |
| Files created | `AGENTS.md` (rewritten from Boost bootstrap stub), `docs/*.md`, `boost.json` |
| Files modified | `composer.json`, `composer.lock` (added `laravel/boost`), `CLAUDE.md` (Boost guidelines + pointer to AGENTS.md) |
| Database changes | None |
| Services / Livewire components | None |
| Business rules | None implemented; intended rules recorded as Proposed ADRs |
| Tests executed | `php artisan test` |
| Test results | 2 passed (framework example tests) |
| Known bugs | None |
| Outstanding | See *Needs attention* |
| Decisions | ADR-0001 accepted; ADR-0002…0007 proposed |
| Next phase | Phase 1 — Foundations (above) |
