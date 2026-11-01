# Changelog

All notable changes to this project are recorded here. Newest entries at the top of each section; never rewrite released history.

## [Unreleased]

### Added
- Phase 3 — Sales and payments (2026-11-01):
  - New sale page: product search, cart with quantities, optional customer (search by name or phone, shows what they owe), one discount per sale (GH₵ or %, cashiers up to 10%), payment at the counter (Cash, Mobile Money, Bank transfer, Card + reference), live totals, "Pay in full". Stock is reduced on save.
  - Credit sales for named customers with no minimum deposit; walk-in sales must be paid in full.
  - Receipt numbers `R-000001`… from a locked counter (no duplicates, no gaps from failed sales).
  - Printable receipt page with lines, totals, discount, payments applied and balance.
  - Sales list with period, status and search filters and totals.
  - Payments page: list with filters and period total; "Receive payment" panel that applies money to the customer's oldest unpaid sales first and previews the split.
  - Customer balances: Balance column and "Owing money" filter on the customers list.
  - Dashboard: sales today, received today, owed by customers, low stock, recent sales, quick actions.
  - Tables `sales`, `sale_lines`, `payments`, `payment_allocations`, `counters`; `SaleService`, `PaymentService`, `ReceiptNumberService`; `SaleStatus`, `PaymentMethod` enums; `config/sales.php`.
  - Idempotency keys on sales and payments (duplicate-submit protection, offline-ready).
  - `Money::parsePercent()` and `Money::percentOf()` (half-up rounding).
  - Demo seed: 4 sales and a later payment.
  - 39 new tests (178 total).
  - Docs: `SALES_MANAGEMENT.md`, `PAYMENT_SYSTEM.md`, `OFFLINE_READINESS.md`, `modules/sales.md`, `modules/payments.md`; `FINANCIAL_LOGIC.md` rewritten with worked GH₵ examples; ADR-0006 accepted; ADR-0015–0017.
- Phase 2 — Customers and products (2026-11-01):
  - `customers` and `products` tables (UUID keys, `created_by` → users, prices as integer pesewas).
  - Customers screen: search by name, email or phone in any format; status filter; one panel to add/edit; admins can deactivate/reactivate. Cashiers and admins can add and edit.
  - Products screen: search, category and status filters; price entered in cedis with a `GH₵` prefix; admins only can add, edit, change prices and deactivate; cashiers see a read-only list (ADR-0012).
  - `CustomerService`, `ProductService`, `CustomerPolicy`, `ProductPolicy`, `CustomerException`, `ProductException`.
  - `App\Support\Money` (cedi ↔ pesewas, no floats) and `App\Support\PhoneNumber` (Ghana numbers → `+233…`, ADR-0013).
  - Dashboard cards for active customers and products; Customers and Products enabled in the sidebar.
  - `<x-input prefix>` and new `<x-textarea>` components.
  - Demo seed data: 14 customers, 8 products.
  - 73 new tests (unit tests for money and phone helpers, service tests, screen tests).
  - Docs: `FINANCIAL_LOGIC.md`, `modules/customers.md`, `modules/products.md`; ADR-0012, ADR-0013.
  - Product photos (JPG/PNG/WebP up to 2 MB, stored on the `public` disk; preview, replace and remove in the panel; thumbnails in the list).
  - Quantity in stock per product with In / Low (1–5) / Out badges, a stock filter on the list, and stock value (quantity × price) (ADR-0014).
  - Product details page `/products/{product}` with photo, price, stock, stock value, description and record; admins edit in place with the same panel (shared `ManagesProductPanel` trait).
  - `<x-stock-badge>` component; `eye` and `arrow-left` icons.
  - 12 more tests (139 total).
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
