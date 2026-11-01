# Architecture Decision Records

Status values: **Accepted** (in force and reflected in code), **Proposed** (derived from requirements in `prompt.md`, not yet confirmed by the owner or implemented), **Superseded by ADR-xxxx**.

Never silently change an Accepted decision — add a new ADR that supersedes it.

---

## ADR-0001 — Documentation is part of every change
- **Date:** 2026-11-01 · **Status:** Accepted
- **Problem:** Future developers and AI agents must understand and safely extend the system without reverse-engineering it.
- **Alternatives:** Code comments only; a single README; a structured `docs/` knowledge base plus `AGENTS.md`.
- **Chosen:** `AGENTS.md` (concise rules) + `docs/` (detail), with mandatory update rules (AGENTS.md §12). Docs are only created when there is implemented behaviour to describe.
- **Advantages:** Fast onboarding; business rules have a single written reference.
- **Disadvantages:** Ongoing maintenance cost; risk of drift.
- **Implications:** Drift is mitigated by the rule that code wins and discrepancies must be fixed and recorded.

## ADR-0002 — UUID primary keys on all application tables
- **Date:** 2026-11-01 · **Status:** Accepted (owner chose "UUIDs everywhere, including users") — implemented for `users`
- **Problem:** Records may in future be created offline on several devices; auto-increment ids would collide when synchronised.
- **Alternatives:** Auto-increment ids + separate public UUID column; UUID PKs on business tables only; UUID PKs everywhere including `users`; ULIDs.
- **Chosen:** UUID primary keys (`HasUuids`, ordered UUIDv7) on every application table including `users`; `sessions.user_id` is a UUID. Framework infrastructure tables keep their defaults. Details: [UUID_STRATEGY.md](UUID_STRATEGY.md).
- **Advantages:** Client-side id generation; ids are not enumerable in URLs; one consistent key type across all foreign keys.
- **Disadvantages:** Larger indexes; less readable ids.
- **Implications:** Human-facing sequences (receipt numbers) need their own columns.

## ADR-0003 — Livewire 4 for the user interface
- **Date:** 2026-11-01 · **Status:** Accepted — `livewire/livewire` 4.4 installed in Phase 1
- **Problem:** Need an interactive UI for data entry without a separate SPA.
- **Alternatives:** Blade + controllers; Livewire 4; Inertia + Vue/React.
- **Chosen:** Livewire 4 with Tailwind CSS 4 (no Flux UI).
- **Advantages:** One language/stack; server-side validation and authorisation stay central.
- **Disadvantages:** Needs a server round-trip per interaction, which complicates offline support (ADR-0007).

## ADR-0004 — Business logic in services; financial records preserved
- **Date:** 2026-11-01 · **Status:** Accepted for the service layer (in use since Phase 1); the financial-record preservation part applies when financial tables are built
- **Problem:** The same rules must apply across UI, imports, API and future sync; financial history must be auditable.
- **Alternatives:** Logic in components/controllers; fat models; action classes; service classes.
- **Proposed:** Service classes in `app/Services` own all business and financial logic. Financial rows (sales, payments, allocations) are never hard-deleted or edited in place; corrections are made with reversals, refunds and status changes. Financial FKs use `restrictOnDelete`.
- **Advantages:** One implementation per rule; testable without UI; complete audit trail.
- **Disadvantages:** More classes; more rows over time.
- **Implications:** Reports must account for reversal rows and statuses.

## ADR-0005 — Money representation
- **Date:** 2026-11-01 · **Status:** Accepted (owner decision) — no money columns exist yet
- **Problem:** Floating-point errors are unacceptable in GHS amounts.
- **Alternatives:** Integer pesewas (1 GHS = 100 pesewas); `decimal(12,2)` with string/bcmath arithmetic; a money library.
- **Chosen:** Every money column is an integer number of pesewas (`unsignedBigInteger`/`bigInteger`, column names suffixed `_pesewas`, e.g. `total_pesewas`). Conversion to/from "GH₵ 12.50" happens only at input and display boundaries.
- **Advantages:** Exact arithmetic with native integers; simple sums.
- **Disadvantages:** Conversion required at input/output boundaries.

## ADR-0006 — Oldest-first payment allocation and historical price snapshots
- **Date:** 2026-11-01 · **Status:** Accepted (owner chose oldest-first on 2026-11-01) — implemented in Phase 3 (`PaymentService`, `sale_lines` snapshots)
- **Problem:** A customer payment not tied to one sale must be applied predictably; later price or promotion changes must not alter past sales.
- **Proposed:** Unallocated payments settle the customer's oldest outstanding sale first (FIFO). Each sale line stores the unit price, discount and promotion applied at the time of sale rather than referencing current product/promotion values.
- **Advantages:** Deterministic, explainable balances; historical accuracy.
- **Disadvantages:** Duplicated price data; customers cannot direct payments unless an explicit-allocation option is added.

## ADR-0007 — Design for future offline support
- **Date:** 2026-11-01 · **Status:** Proposed — partly in place (UUIDs, idempotency keys); see [OFFLINE_READINESS.md](OFFLINE_READINESS.md)
- **Problem:** The business may need to operate during connectivity loss.
- **Proposed:** Not built now, but keep it possible: UUIDs (ADR-0002), device identifiers and idempotency keys on financial writes, services usable by a sync endpoint, no logic that depends on server-generated sequential ids except receipt numbers assigned at sync time.
- **Disadvantages:** Extra columns and complexity before the feature exists.
- **Implications:** Detailed plan to be written in `OFFLINE_READINESS.md` when the first related code lands.

## ADR-0008 — Class-based Livewire components
- **Date:** 2026-11-01 · **Status:** Accepted
- **Problem:** Livewire 4 defaults to single-file components (PHP + Blade in one `⚡name.blade.php` file). We need components that are easy to locate, test, statically analyse and document.
- **Alternatives:** Single-file (default); multi-file; class-based.
- **Chosen:** Class-based — `app/Livewire/**` classes with views in `resources/views/livewire/**`. `config/livewire.php` sets `make_command.type = 'class'` and `emoji = false`.
- **Advantages:** Standard PSR-4 classes; IDE and Pint support; clear separation of logic and markup.
- **Disadvantages:** Two files per component; differs from most Livewire 4 docs examples.

## ADR-0009 — Hand-built authentication on Laravel's session guard
- **Date:** 2026-11-01 · **Status:** Accepted (owner asked for "well-structured custom authentication")
- **Problem:** Staff need sign-in, sign-out and password reset with roles, without public registration.
- **Alternatives:** Laravel Livewire starter kit; Fortify; custom Livewire components + services.
- **Chosen:** Custom: `LoginService` and `PasswordResetService` own the logic; thin Livewire components and a `LogoutController`; Laravel's `web` guard and password broker underneath; accounts created by admins or `app:create-admin-user`.
- **Advantages:** Only the features needed; follows the project's service-layer rule; no Flux/starter-kit scaffolding to maintain.
- **Disadvantages:** We own security-sensitive code (mitigated by tests in `tests/Feature/Auth`); features like 2FA must be built ourselves.

## ADR-0010 — Roles as an enum column with policies
- **Date:** 2026-11-01 · **Status:** Accepted (owner chose Admin + Cashier)
- **Problem:** Two staff roles with different permissions.
- **Alternatives:** Permissions package (e.g. spatie/laravel-permission); role enum + policies.
- **Chosen:** `users.role` holding `App\Enums\UserRole` (`admin`, `cashier`); permissions expressed in policies.
- **Advantages:** No dependency; type-safe; trivial to test.
- **Disadvantages:** One role per user; fine-grained per-user permissions would need a new ADR.
- **Implications:** Adding a role (e.g. Manager) means a new enum case and policy review.

## ADR-0011 — Visual design and the list + modal screen pattern
- **Date:** 2026-11-01 · **Status:** Accepted (owner picked sign-in "A" and dashboard "1" from a mockup gallery)
- **Problem:** The Phase 1 screens were plain; the owner wanted modern auth pages, a sidebar dashboard, and one modal for both creating and editing records, reused for customers and products.
- **Alternatives:** Auth: split brand panel (A), centred card (B), dark immersive (C). Dashboard: light white sidebar (1), navy sidebar with coloured cards (2), dark icon rail (3). CRUD: separate create/edit pages vs. list + single modal.
- **Chosen:** A + 1. Teal brand colour (Tailwind `teal`), `slate` neutrals, Manrope self-hosted via the Vite fonts plugin, shared Blade components (`x-icon`, `x-input`, `x-button`, `x-modal`, `x-nav-link`). Each resource gets one `Index` Livewire component with filters, table and a single create/edit modal (pattern documented in [LIVEWIRE_COMPONENTS.md](LIVEWIRE_COMPONENTS.md)). Separate `/users/create` and `/users/{user}/edit` pages were removed.
- **Advantages:** Fast data entry without leaving the list; one pattern for every resource; consistent styling from a few components; no external font requests.
- **Disadvantages:** Modal-based edits are not deep-linkable; very large forms (e.g. sales with many lines) will need a full page instead of a modal.
- **Implications:** Customers and products copy the users pattern. Sales entry should be reconsidered as a full page when designed.

**ADR-0011 amendment (2026-11-01):** the owner asked for the auth-page colours to become the system colour. The deep teal is now a theme token (`--color-brand`, used as `bg-brand`); the signed-in sidebar and the dashboard banner use it as a flat colour without grid or glow (`brand-texture` and the glows stay on the auth pages only), while tables, forms and panels stay on white/`bg-canvas` for legibility. Sign-in pages changed from split screen to a full brand background with a centred translucent card.

## ADR-0012 — Only administrators manage products and prices
- **Date:** 2026-11-01 · **Status:** Accepted as the default (owner was asked twice without an answer; easy to change)
- **Problem:** Who may add products and change selling prices?
- **Alternatives:** Admins only; admins and cashiers; cashiers may add products but only admins change prices.
- **Chosen:** `ProductPolicy` allows `viewAny` to everyone and `create`/`update`/`changeStatus` to admins only. Cashiers see a read-only list with the note "Prices are set by an administrator."
- **Advantages:** Prices cannot be changed at the counter; matches the intended role split (ADR-0010).
- **Disadvantages:** Cashiers must ask an admin to add a new item.
- **Implications:** To let cashiers manage products, change `ProductPolicy::create/update` (and the tests in `ProductManagementTest`), and record a superseding ADR.

## ADR-0013 — Ghana phone numbers normalised to E.164 and unique per customer; helpers in app/Support
- **Date:** 2026-11-01 · **Status:** Accepted
- **Problem:** Staff type the same number many ways (`024 412 3456`, `+233244123456`), which would create duplicate customers and split balances.
- **Alternatives:** Store as typed; store digits only; store E.164 (`+233XXXXXXXXX`). Uniqueness: none, warn only, enforce.
- **Chosen:** `App\Support\PhoneNumber::normalise()` converts every accepted form to `+233XXXXXXXXX` (national number starting 2, 3 or 5); the value is unique in `customers.phone`. Display uses the local format. Stateless helpers like this and `App\Support\Money` live in a new `app/Support` folder.
- **Advantages:** One customer per number; reliable search; E.164 is ready for SMS integrations.
- **Disadvantages:** Two people sharing one phone (e.g. a couple) cannot both be registered with that number; foreign numbers are rejected.
- **Implications:** If shared or foreign numbers become necessary, relax the unique index and the `[235]` check with a new ADR.

## ADR-0014 — Product stock as a simple quantity; photos on the public disk
- **Date:** 2026-11-01 · **Status:** Accepted
- **Problem:** The owner asked for a product photo, an in-stock value and a product details page.
- **Alternatives (stock):** a single `stock_quantity` column edited by admins; a `stock_movements` ledger (every receipt, sale and adjustment as a row) with the quantity derived from it.
- **Chosen (stock):** `products.stock_quantity` (unsigned int, default 0), edited in the product panel by admins; levels In / Low (≤ 5) / Out; stock value = quantity × selling price.
- **Alternatives (photos):** public disk with `storage:link`; private disk served through a controller; external object storage.
- **Chosen (photos):** one image per product on the `public` disk under `products/`, validated (JPG/PNG/WebP, ≤ 2 MB, no SVG), old files deleted on replace/remove, no resizing.
- **Advantages:** Small and immediately useful; no extra packages.
- **Disadvantages:** No audit trail of who changed stock or why; sales will not reduce stock until Phase 3 adds that; full-size photos are served as uploaded.
- **Implications:** When sales are built, decrement stock inside the sale transaction with a row lock, and consider introducing a `stock_movements` ledger (new ADR) so stock changes are auditable. Deployments must run `php artisan storage:link`.

## ADR-0015 — Sale-level discounts with a cashier limit; credit without deposit
- **Date:** 2026-11-01 · **Status:** Accepted (owner choices)
- **Problem:** How discounts and credit sales work.
- **Alternatives:** Discounts per sale or per line; fixed or %; who may give them. Credit: allowed with no minimum, with a minimum deposit, or cash only.
- **Chosen:** One discount per sale, entered as GH₵ or % (stored as the resulting pesewas plus an optional reason). Cashiers may give up to `sales.cashier_max_discount_percent` (default 10%) of the subtotal; administrators up to 100%. "Admin-approved" is implemented as *an administrator enters the sale* — there is no request/approve workflow yet. Credit is allowed with no deposit, but only for a named customer; walk-ins pay in full.
- **Advantages:** Simple receipts; limit is configurable without code changes.
- **Disadvantages:** A cashier needing a larger discount must hand over to an admin; no per-line discounts.
- **Implications:** An approval workflow (admin approves a cashier's pending sale) would need a sale state before completion and a new ADR.

## ADR-0016 — Receipt numbers from a locked counter table
- **Date:** 2026-11-01 · **Status:** Accepted (owner chose the R-000184 format)
- **Problem:** Receipts need short, sequential, never-reused numbers, safe under concurrent sales.
- **Alternatives:** `max(receipt_number) + 1` (race-prone); database sequences/auto-increment (not portable, gaps on rollback); a `counters` table row incremented under `lockForUpdate` inside the sale transaction.
- **Chosen:** `counters` table + `ReceiptNumberService`; format `config('sales.receipt_prefix')` + zero-padded `receipt_digits` (`R-000001`).
- **Advantages:** No duplicates; no gaps from failed sales (the increment rolls back with the sale); portable across SQLite/MySQL/PostgreSQL.
- **Disadvantages:** All sales serialise briefly on one row (fine at shop scale).
- **Implications:** Offline sales will need provisional numbers replaced at sync (OFFLINE_READINESS.md).

## ADR-0017 — Idempotency keys on sales and payments
- **Date:** 2026-11-01 · **Status:** Accepted
- **Problem:** Double clicks, browser retries and (later) offline sync replays must not record a sale or payment twice.
- **Alternatives:** Rely on disabled buttons only; unique idempotency key per operation.
- **Chosen:** Nullable unique `idempotency_key` (UUID) on `sales` and `payments`. The screens generate one per form; the services return the existing record when the key was already used.
- **Advantages:** Safe retries; ready for sync.
- **Disadvantages:** One more column; keys must be regenerated for each new operation (done when the page or panel opens).
