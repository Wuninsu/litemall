# Changelog

All notable changes to this project are recorded here. Newest entries at the top of each section; never rewrite released history.

## [Unreleased]

### Added
- Phase 1 foundations (2026-11-01):
  - Livewire 4.4 (class-based components) with `layouts::app` and `layouts::guest` Tailwind layouts.
  - Custom authentication: sign-in with "remember me", sign-out, forgot/reset password (`LoginService`, `PasswordResetService`).
  - Roles `admin` and `cashier` (`App\Enums\UserRole`), `UserPolicy`, `active` middleware.
  - Admin user management: list/search, create, edit (incl. password), activate/deactivate (`UserService`, `Users\Index|Create|Edit`).
  - `php artisan app:create-admin-user` to bootstrap the first administrator.
  - Dashboard placeholder page; `/` redirects to it.
  - Local seed accounts `admin@example.com` / `cashier@example.com` (password `password`, refused in production).
  - 44 feature tests covering auth, password reset, user management, `UserService` and the console command.
  - Docs: `BUSINESS_RULES.md`, `LIVEWIRE_COMPONENTS.md`, `UUID_STRATEGY.md`; ADR-0008…0010.
- Project documentation framework: root `AGENTS.md` and `docs/` knowledge base. (2026-11-01)
- `laravel/boost` dev dependency and `boost.json`; Boost coding guidelines written to `CLAUDE.md`. (2026-11-01)

### Changed
- `users.id` is now a UUID; `users` gained `role`, `is_active`, `last_login_at`; `sessions.user_id` is a UUID (default migration edited before any shared deployment). (2026-11-01)
- ADR-0002 (UUIDs), ADR-0003 (Livewire 4), ADR-0005 (integer pesewas) accepted; ADR-0004 accepted for the service layer. (2026-11-01)
- Removed the default `welcome` view and route. (2026-11-01)

### Security
- Login rate limiting (5 failures/min per email+IP), reset-link rate limiting (5/min per IP), session regeneration on sign-in, session revocation on deactivation and password change, environment-dependent password strength rules. (2026-11-01)
- Recorded open advisory CVE-2026-45070 in `symfony/mime` (medium) as needing an update. (2026-11-01)

## [0.0.0] — Initial commit

### Added
- Laravel 13 application skeleton.
