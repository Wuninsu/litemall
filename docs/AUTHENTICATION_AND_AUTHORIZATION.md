# Authentication and Authorization

_As of 2026-11-01._

## Authentication

- **No authentication is implemented.** There are no login, logout, registration or password-reset routes, controllers or views, and no auth starter kit is installed.
- What exists from the skeleton: the `users` table, `App\Models\User` (extends `Illuminate\Foundation\Auth\User`), the default `web` guard using the `session` driver and Eloquent user provider (`config/auth.php`), and the `password_reset_tokens` table.
- Passwords are hashed automatically via the `password => 'hashed'` cast (bcrypt, `BCRYPT_ROUNDS=12`; 4 in tests).

## Authorization

- No gates, policies, roles or permissions exist.
- All current routes (`/`, `/up`) are public.

## To decide (Phase 1)

- Auth mechanism (starter kit vs. custom Livewire forms) — record as an ADR.
- Role model (e.g. administrator, cashier) and whether to use policies alone or a permissions package — record as an ADR and document here.
