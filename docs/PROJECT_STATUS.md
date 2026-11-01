# Project Status

_Last updated: 2026-11-01 (end of Phase 4 — Returns and cancellations)_

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
- **Phase 3 — Sales and payments:** sale entry with discounts (cashier limit), credit and counter payments; receipts `R-000001`; later payments allocated oldest-first; customer balances; dashboard money figures; idempotency keys. 178 tests passing.
- **POS and wholesale (ADR-0018, ADR-0019):** full-screen point of sale with product grid, customer dialog, Take payment dialog with change, animated receipt printer; wholesale prices, wholesale customers and a Retail/Wholesale switch. 198 tests passing.
- **Phase 4 — Returns and cancellations (ADR-0020):** admin-only item returns and cancellations from the receipt page with reason, discount-aware values, debt-first refunds with method, per-item restock, `RT-` numbers; all balances net of returns. 212 tests passing.

## In progress

Nothing.

## Not yet implemented

- Reversing a single wrongly recorded payment (wrong customer/amount); exchanges; store credit.
- Promotions and promotional prices.
- Reports (daily cash-up by method, sales by product/staff, debtors aging), bulk imports.
- Audit logging of edits (who changed prices, stock, customers, users).
- Stock movement history (stock is changed by admins and by sales, but changes are not logged).
- Offline operation beyond UUIDs and idempotency keys (see OFFLINE_READINESS.md).
- Production mailer configuration (reset emails currently go to the log).

## Needs attention

1. A payment recorded against the wrong customer or with the wrong amount still cannot be reversed (sales can now be returned/cancelled). Decide whether payment reversal is needed before go-live.
2. ADR-0012 (admins only manage products/prices) was taken as a default without an owner answer — confirm.
3. "Admin-approved" discounts are implemented as "an admin enters the sale" (ADR-0015); confirm this is enough or whether an approval request flow is wanted.
4. `symfony/mime` advisory CVE-2026-45070 (medium) — `composer update symfony/mime` when a fixed release is resolvable.
5. No audit log of edits; decide before go-live.

## Recommended next task

**Phase 5 — Reports:** daily cash-up (money in by method minus refunds by method, per cashier), sales by day/product/staff, debtors list with aging, stock report (low/out, stock value). Create `DASHBOARD_AND_REPORTING.md` and `modules/reports.md`. Consider basic audit logging alongside.

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

## Handover — Phase 3: Sales and payments (2026-11-01)

| Item | Detail |
|---|---|
| Implemented | New sale page, sales list, printable receipt, payments page with oldest-first allocation, customer balances, dashboard money tiles, demo sales |
| Files created | Migrations `2026_11_01_200957`…`201001` (sales, sale_lines, payments, payment_allocations, counters); `app/Models/{Sale,SaleLine,Payment,PaymentAllocation}.php`; `app/Enums/{SaleStatus,PaymentMethod}.php`; `app/Services/{SaleService,PaymentService,ReceiptNumberService}.php`; `app/Exceptions/{Sales/SaleException,Payments/PaymentException}.php`; `app/Policies/{SalePolicy,PaymentPolicy}.php`; `app/Livewire/Sales/{Create,Index,Show}.php`, `app/Livewire/Payments/Index.php` + views; `resources/views/components/sale-status.blade.php`; `config/sales.php`; factories `SaleFactory`, `PaymentFactory`; tests `tests/Feature/Services/{SaleServiceTest,PaymentServiceTest}.php`, `tests/Feature/Sales/SalesScreensTest.php`, `tests/Feature/Payments/PaymentsScreenTest.php`, `tests/Unit/SaleStatusTest.php`; docs `SALES_MANAGEMENT.md`, `PAYMENT_SYSTEM.md`, `OFFLINE_READINESS.md`, `modules/sales.md`, `modules/payments.md` |
| Files modified | `Customer.php` (relations, balance), `Money.php` (percent helpers), `Dashboard.php` + view, `Customers/Index.php` + view (balance, owing filter), `layouts/app.blade.php` (links, print styles), `routes/web.php`, `DatabaseSeeder.php`, `tests/Unit/MoneyTest.php`; docs listed in CHANGELOG |
| Database changes | 5 new tables (see DATABASE_SCHEMA.md) |
| Services | `SaleService`, `PaymentService`, `ReceiptNumberService` |
| Livewire components | `Sales\Create`, `Sales\Index`, `Sales\Show`, `Payments\Index` |
| Business rules | S1–S12, R1–R2, PY1–PY7 |
| Tests executed | `php artisan test --compact`; `vendor/bin/pint`; `npm run build`; `php artisan migrate:fresh --seed` (seeded allocations checked) |
| Test results | 178 passed, 690 assertions |
| Known bugs | None known. Screens not reviewed visually in a browser in this session |
| Decisions | ADR-0006 accepted; ADR-0007 partly in place; new ADR-0015 (discounts/credit), ADR-0016 (receipt counter), ADR-0017 (idempotency) |
| Next phase | Phase 4 — Refunds and cancellations, then reports |

## Handover — Phase 4: Returns and cancellations (2026-11-01)

| Item | Detail |
|---|---|
| Implemented | Admin-only item returns and cancellations from the receipt page; discount-aware values; debt reduced first, rest refunded with method; per-item restock; `RT-` numbers; return history on receipts; Cancelled status; balances, payments, dashboard and lists net of returns |
| Files created | Migration `2026_11_01_215330_create_sale_returns`; `app/Models/{SaleReturn,SaleReturnLine}.php`; `app/Services/ReturnService.php`; `app/Exceptions/Sales/SaleReturnException.php`; tests `tests/Feature/Services/ReturnServiceTest.php`, `tests/Feature/Sales/ReturnsScreenTest.php`; doc `REFUNDS_AND_CANCELLATIONS.md` |
| Files modified | `Sale.php` (returns, net total, balance, `recalculateStatus`, `OWED_SQL`), `SaleLine.php` (`returned_quantity`), `SaleStatus.php` (Cancelled), `ReceiptNumberService.php` (return numbers), `PaymentService.php`, `Customer.php`, `Dashboard.php`, `Customers/Index.php`, `Payments/Index.php`, `Sales/Index.php`, `Sales/Show.php` + view, `SalePolicy.php` (`refund`), `sale-status` component, `config/sales.php` |
| Database changes | `sale_returns`, `sale_return_lines`; `sales.returned_pesewas`; `sale_lines.returned_quantity` |
| Services | `ReturnService` (new); `ReceiptNumberService::nextReturnNumber` |
| Livewire components | `Sales\Show` return panel |
| Business rules | RF1–RF9 |
| Tests executed | `php artisan test --compact`; `vendor/bin/pint`; `npm run build`; `php artisan migrate` |
| Test results | 212 passed, 912 assertions |
| Known bugs | None known. Return panel not reviewed visually in a browser |
| Decisions | ADR-0020 |
| Next phase | Phase 5 — Reports |
