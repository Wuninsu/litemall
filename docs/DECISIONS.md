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
- **Date:** 2026-11-01 · **Status:** Proposed
- **Problem:** A customer payment not tied to one sale must be applied predictably; later price or promotion changes must not alter past sales.
- **Proposed:** Unallocated payments settle the customer's oldest outstanding sale first (FIFO). Each sale line stores the unit price, discount and promotion applied at the time of sale rather than referencing current product/promotion values.
- **Advantages:** Deterministic, explainable balances; historical accuracy.
- **Disadvantages:** Duplicated price data; customers cannot direct payments unless an explicit-allocation option is added.

## ADR-0007 — Design for future offline support
- **Date:** 2026-11-01 · **Status:** Proposed
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
