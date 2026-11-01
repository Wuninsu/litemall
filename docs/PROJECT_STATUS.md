# Project Status

_Last updated: 2026-11-01 (end of Phase 2 — Customers and products)_

## Complete

- **Phase 0:** documentation framework (`AGENTS.md`, `docs/`), Laravel Boost.
- **Phase 1 — Foundations:**
  - Livewire 4.4 (class-based), Tailwind 4 layouts, Vite build working.
  - UUID primary key on `users`; roles `admin` / `cashier`; `is_active`; `last_login_at`.
  - Custom authentication: sign-in, sign-out, forgot/reset password, rate limiting, deactivated-user lockout.
  - Admin user management (list/search, create, edit, activate/deactivate) with last-admin protection.
  - `app:create-admin-user` command; local demo seeder.
  - 44 passing feature tests at the end of the phase.
- **UI refresh (ADR-0011):** brand auth pages, brand-teal sidebar shell, dashboard, users list with a single create/edit panel, shared Blade UI components including `x-button` with loader.
- **Phase 2 — Customers and products:** customer register (Ghana phone normalisation, one customer per number), products with photos, GH₵ prices stored as pesewas, stock quantities and a details page; admin-only product management; dashboard counts. 139 tests passing.

## In progress

Nothing.

## Not yet implemented

- Sales, discounts, promotions, receipts.
- Payments, allocation, outstanding balances.
- Refunds and cancellations.
- Dashboard content, reports, bulk imports.
- Audit logging of administrative and financial actions.
- Offline readiness beyond UUIDs (device ids, idempotency, sync).
- Production mailer configuration (reset emails currently go to the log).

## Needs attention

1. **Decisions needed before Phase 3 (sales and payments):** ADR-0006 (oldest-first payment allocation; price snapshots on sale lines) and ADR-0007 (offline design) are still Proposed. Also undecided: how discounts work (per line or per sale; fixed or %; who may give them), receipt number format, and whether part-payment (credit) sales need a minimum deposit.
2. ADR-0012 (admins only manage products/prices) was taken as a default without an owner answer — confirm.
3. `symfony/mime` advisory CVE-2026-45070 (medium) — `composer update symfony/mime` when a fixed release is resolvable.
4. Audit logging is absent; it should exist before any financial feature ships.

## Recommended next task

**Phase 3 — Sales:** after the decisions above, build `sales` and `sale_lines` (UUID keys, `_pesewas` amounts, unit-price snapshot per line, `restrictOnDelete` FKs), a `SaleService` with transactional totals and stock reduction (row-locked; consider a `stock_movements` ledger, ADR-0014), receipt numbering, and a full-page sale entry screen (too large for a side panel, see ADR-0011). Create `SALES_MANAGEMENT.md`, `modules/sales.md`, and extend `FINANCIAL_LOGIC.md` with worked GH₵ examples. Consider adding basic audit logging in the same phase.

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

## Handover — Phase 2: Customers and products (2026-11-01)

| Item | Detail |
|---|---|
| Implemented | Customers and products modules (list + right-hand panel), product photos, stock quantity and value, product details page, money and phone helpers, dashboard counts, demo data |
| Files created | Migrations `2026_11_01_194210_create_customers_table`, `2026_11_01_194211_create_products_table`; `app/Models/{Customer,Product}.php`; `app/Services/{CustomerService,ProductService}.php`; `app/Policies/{CustomerPolicy,ProductPolicy}.php`; `app/Exceptions/{Customers/CustomerException,Products/ProductException}.php`; `app/Support/{Money,PhoneNumber}.php`; `app/Livewire/{Customers,Products}/Index.php` + views; `app/Livewire/Forms/{CustomerForm,ProductForm}.php`; `resources/views/components/textarea.blade.php`; factories for both models; tests `tests/Unit/{MoneyTest,PhoneNumberTest}.php`, `tests/Feature/Services/{CustomerServiceTest,ProductServiceTest}.php`, `tests/Feature/{Customers/CustomerManagementTest,Products/ProductManagementTest}.php`; migration `2026_11_01_195455_add_image_and_stock_to_products_table`; `app/Livewire/Products/Show.php` + view; `app/Livewire/Products/Concerns/ManagesProductPanel.php`; `resources/views/livewire/products/partials/panel.blade.php`; `resources/views/components/stock-badge.blade.php`; `tests/Feature/Products/ProductImageAndStockTest.php`; docs `FINANCIAL_LOGIC.md`, `modules/customers.md`, `modules/products.md` |
| Files modified | `routes/web.php`, `layouts/app.blade.php` (sidebar links), `components/input.blade.php` (`prefix`), `Dashboard.php` + view (`shopStats`), `DatabaseSeeder.php`; docs listed in CHANGELOG |
| Database changes | New tables `customers`, `products`; `products.stock_quantity` and `products.image_path` (migration `2026_11_01_195455`) |
| Services | `CustomerService`, `ProductService` |
| Livewire components | `Customers\Index`, `Products\Index`, `Products\Show`, trait `Products\Concerns\ManagesProductPanel`, `Forms\CustomerForm`, `Forms\ProductForm` |
| Business rules | C1–C6, P1–P8 in BUSINESS_RULES.md |
| Tests executed | `php artisan test --compact`; `vendor/bin/pint --dirty`; `npm run build`; `php artisan migrate:fresh --seed`; `php artisan storage:link` |
| Test results | 139 passed, 498 assertions |
| Known bugs | None known. No visual browser review was done in this session |
| Decisions | ADR-0012 (admin-only products/prices — default, to confirm), ADR-0013 (phone normalisation, `app/Support`), ADR-0014 (simple stock quantity, photos on public disk) |
| Next phase | Phase 3 — Sales (decisions listed under *Needs attention* first) |
