# Project Status

_Last updated: 2026-11-01 (Phase 1 + UI refresh)_

## Complete

- **Phase 0:** documentation framework (`AGENTS.md`, `docs/`), Laravel Boost.
- **Phase 1 — Foundations:**
  - Livewire 4.4 (class-based), Tailwind 4 layouts, Vite build working.
  - UUID primary key on `users`; roles `admin` / `cashier`; `is_active`; `last_login_at`.
  - Custom authentication: sign-in, sign-out, forgot/reset password, rate limiting, deactivated-user lockout.
  - Admin user management (list/search, create, edit, activate/deactivate) with last-admin protection.
  - `app:create-admin-user` command; local demo seeder.
  - 44 passing feature tests at the end of the phase.
- **UI refresh (ADR-0011):** split-screen auth pages, sidebar shell, dashboard, users list with a single create/edit modal, shared Blade UI components.

## In progress

Nothing.

## Not yet implemented

- Customers, products (Phase 2).
- Sales, discounts, promotions, receipts.
- Payments, allocation, outstanding balances.
- Refunds and cancellations.
- Dashboard content, reports, bulk imports.
- Audit logging of administrative and financial actions.
- Offline readiness beyond UUIDs (device ids, idempotency, sync).
- Production mailer configuration (reset emails currently go to the log).

## Needs attention

1. Remaining Proposed ADRs: ADR-0006 (oldest-first allocation, price snapshots) and ADR-0007 (offline design) — confirm before Phase 3 (sales/payments).
2. `symfony/mime` advisory CVE-2026-45070 (medium) — `composer update symfony/mime` when a fixed release is resolvable.
3. Audit logging is absent; it should exist before any financial feature ships.

## Recommended next task

**Phase 2 — Customers and products:** migrations (UUID PKs, prices as `_pesewas` integers), models + factories, `CustomerService` / `ProductService`, Livewire `Index` screens following the users list + modal pattern (see LIVEWIRE_COMPONENTS.md), enabling the Customers/Products sidebar links, policies (who may edit prices — likely admin only), tests, and `modules/customers.md`, `modules/products.md`, plus BUSINESS_RULES/SCHEMA updates.

## Handover — Phase 1 (2026-11-01)

| Item | Detail |
|---|---|
| Implemented | Livewire setup, custom auth, roles, user management, admin bootstrap command |
| Files created | `app/Enums/UserRole.php`; `app/Exceptions/DomainException.php`, `Auth/*`, `Users/UserManagementException.php`; `app/Services/UserService.php`, `Services/Auth/LoginService.php`, `PasswordResetService.php`; `app/Livewire/{Dashboard, Auth/*, Users/*, Forms/UserForm}.php` + views; `resources/views/layouts/{app,guest}.blade.php`; `app/Policies/UserPolicy.php`; `app/Http/Middleware/EnsureUserIsActive.php`; `app/Http/Controllers/Auth/LogoutController.php`; `app/Console/Commands/CreateAdminUser.php`; `config/livewire.php`; tests under `tests/Feature/{Auth,Users,Services,Console}`; `package-lock.json` |
| Files modified | users migration; `User` model + factory; `DatabaseSeeder`; `routes/web.php`; `bootstrap/app.php`; `AppServiceProvider`; `tests/TestCase.php` (`withoutVite`); `tests/Feature/ExampleTest.php`; `composer.json/lock` (livewire) |
| Files removed | `resources/views/welcome.blade.php` |
| Database changes | `users`: UUID `id`, `role`, `is_active`, `last_login_at`; `sessions.user_id` UUID |
| Services | `LoginService`, `PasswordResetService`, `UserService` |
| Livewire components | `Auth\Login`, `Auth\ForgotPassword`, `Auth\ResetPassword`, `Dashboard`, `Users\Index`, `Users\Create`, `Users\Edit`, `Forms\UserForm` |
| Business rules | U1–U10 in [BUSINESS_RULES.md](BUSINESS_RULES.md) |
| Tests executed | `php artisan test --compact`; `vendor/bin/pint --dirty`; manual check with `php artisan serve` (login page renders with built assets, `/dashboard` redirects guests) |
| Test results | 44 passed, 154 assertions |
| Known bugs | None known |
| Decisions | ADR-0002, 0003, 0005 accepted; 0004 accepted for services; new 0008 (class components), 0009 (custom auth), 0010 (role enum) |
| Next phase | Phase 2 — Customers and products |

## Handover — UI refresh (2026-11-01)

| Item | Detail |
|---|---|
| Implemented | Design A + 1 from the mockup gallery; users list + single modal; dashboard content |
| Files created | `resources/views/components/{icon,input,button,modal,nav-link}.blade.php`; `tests/Feature/DashboardTest.php` |
| Files modified | `layouts/app.blade.php`, `layouts/guest.blade.php`, auth views, `dashboard` view + `Dashboard.php`, `Users/Index.php` + view, `Forms/UserForm.php` (`#[Locked]`), `User.php` (`initials()`), `routes/web.php`, `resources/css/app.css`, `vite.config.js` (Manrope), `tests/Feature/Users/UserManagementTest.php` |
| Files removed | `app/Livewire/Users/{Create,Edit}.php` and their views, `users/partials/form-fields.blade.php` |
| Database / services | No changes |
| Tests | `php artisan test --compact`: 47 passed, 176 assertions; also passes with no `public/build`. `npm run build` succeeds. Login page checked over HTTP with `php artisan serve` (fonts and CSS load). No visual screenshot review was possible in this session — check the screens in a browser. |
| Known issues | None known |
| Decisions | ADR-0011 |
| Next | Phase 2 — Customers and products using the same pattern |
