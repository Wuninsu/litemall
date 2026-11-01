# Project Overview

## What the application does (implemented)

A staff-only web application where:
- Staff sign in with email and password (and can reset a forgotten password by email).
- Administrators create and manage staff accounts (Administrator or Cashier) and can deactivate leavers.
- Staff register and look up customers (one customer per Ghana phone number); administrators can deactivate them.
- Everyone can browse products with photos, GH₵ prices and stock levels, and open a details page; administrators add products and set prices and stock.
- Staff ring up sales (products, quantities, optional customer, one discount, payment now or on credit), print receipts, and receive later payments that settle the customer's oldest unpaid sales first. Stock goes down with each sale.
- The dashboard shows today's sales and payments, what customers owe, low stock and recent sales.

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

## User roles

| Role | Today | Planned |
|---|---|---|
| Administrator | Everything, incl. users, product prices/stock and larger discounts | Also promotions, refunds/cancellations |
| Cashier | Dashboard; add/edit customers; view products; record sales (discount ≤ 10%); receive payments | — |

See [AUTHENTICATION_AND_AUTHORIZATION.md](AUTHENTICATION_AND_AUTHORIZATION.md).

## Technology stack

| Component | Installed version | Notes |
|---|---|---|
| PHP | 8.4.18 (local, Laragon) | `composer.json` requires `^8.3` |
| Laravel | 13.34 | |
| Livewire | 4.4 | class-based components (ADR-0008) |
| Tailwind CSS / Vite | 4 / 8 | `npm run build` → `public/build` |
| PHPUnit | 12.5 | |
| Laravel Pint | 1.32 | code style |
| Laravel Boost | 2.10 (dev) | AI agent guidelines in `CLAUDE.md` |
| Database | SQLite | local file `database/database.sqlite`; production DB not yet chosen |

## System boundaries

- Single Laravel monolith; server-rendered Livewire UI.
- Outbound email only for password-reset links (`MAIL_MAILER=log` locally).
- No public API (`routes/api.php` does not exist); no payment gateways or SMS.

## Current development status

Phases 1–3 (foundations, UI, customers, products, sales and payments) complete — see [PROJECT_STATUS.md](PROJECT_STATUS.md).

## Known limitations

- Sales and payments cannot be corrected yet (no refunds/cancellations); no reports.
- No audit log, no 2FA.
- `composer audit` reports CVE-2026-45070 in `symfony/mime` (medium), unresolved.

## Planned future improvements

See *Not yet implemented* in [PROJECT_STATUS.md](PROJECT_STATUS.md).
