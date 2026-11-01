# Changelog

All notable changes to this project are recorded here. Newest entries at the top of each section; never rewrite released history.

## [Unreleased]

### Added
- UI refresh (2026-11-01, ADR-0011):
  - Sign-in, forgot-password and reset-password pages: full-screen teal brand background with the form centred in a frosted translucent card.
  - Brand colour tokens `bg-brand`, `bg-canvas` and the `brand-texture` grid utility (`resources/css/app.css`); the deep teal from the auth pages is used for the sidebar and a dashboard banner (flat colour; grid texture and glows on the auth pages only).
  - Sidebar application shell (dark brand teal) with grouped navigation, "Soon" placeholders for unbuilt modules, user card with sign-out, and a slide-out menu on phones.
  - Dashboard with greeting, admin-only team statistics and a "Coming next" module list.
  - Users screen with search, role and status filters, and one right-hand full-height panel for both creating and editing (including deactivate/reactivate), with a stacked one-row-per-role picker.
  - `x-modal` `position="right"` option; `UserRole::description()`.
  - Shared Blade components: `x-icon`, `x-input`, `x-button`, `x-modal`, `x-nav-link`; `User::initials()`.
  - Manrope font self-hosted via the Vite fonts plugin (`@fonts`).
  - `x-input tone="dark"` and `x-button variant="bright"` for the brand background.
  - Reusable `x-button` with variants, sizes, icons, link mode, full width and an automatic loading spinner for Livewire actions; all app buttons use it.
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
- Manrope now also bundles the `latin-ext` subset so the cedi sign (₵, U+20B5) is covered by the font instead of falling back. (2026-11-01)
- `x-icon` no longer forces `size-5` when a `size-*` class is passed (the two sizes could conflict). (2026-11-01)
- Replaced the `/users/create` and `/users/{user}/edit` pages (`Users\Create`, `Users\Edit` components) with the modal on `/users`. (2026-11-01)
- `UserForm::$user` is now `#[Locked]`. (2026-11-01)

### Security
- Login rate limiting (5 failures/min per email+IP), reset-link rate limiting (5/min per IP), session regeneration on sign-in, session revocation on deactivation and password change, environment-dependent password strength rules. (2026-11-01)
- Recorded open advisory CVE-2026-45070 in `symfony/mime` (medium) as needing an update. (2026-11-01)

## [0.0.0] — Initial commit

### Added
- Laravel 13 application skeleton.
