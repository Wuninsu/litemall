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

## ADR-0002 — UUID primary keys for business tables
- **Date:** 2026-11-01 · **Status:** Proposed
- **Problem:** Records may in future be created offline on several devices; auto-increment ids would collide when synchronised.
- **Alternatives:** Auto-increment ids + separate public UUID column; UUID PKs (`HasUuids`, ordered UUIDv7); ULIDs.
- **Proposed:** UUID primary keys on all business tables via Eloquent `HasUuids`; framework tables keep their defaults.
- **Advantages:** Client-side id generation; ids are not enumerable in URLs.
- **Disadvantages:** Larger indexes; less readable ids; `users` would be inconsistent unless also migrated.
- **Implications:** Must be decided before the first business migration; changing later requires data migration.

## ADR-0003 — Livewire 4 for the user interface
- **Date:** 2026-11-01 · **Status:** Proposed (Livewire not installed)
- **Problem:** Need an interactive UI for data entry without a separate SPA.
- **Alternatives:** Blade + controllers; Livewire 4; Inertia + Vue/React.
- **Proposed:** Livewire 4 with Tailwind CSS (already in `package.json`).
- **Advantages:** One language/stack; server-side validation and authorisation stay central.
- **Disadvantages:** Needs a server round-trip per interaction, which complicates offline support (ADR-0007).
- **Implications:** Installing it is a dependency change requiring approval.

## ADR-0004 — Business logic in services; financial records preserved
- **Date:** 2026-11-01 · **Status:** Proposed
- **Problem:** The same rules must apply across UI, imports, API and future sync; financial history must be auditable.
- **Alternatives:** Logic in components/controllers; fat models; action classes; service classes.
- **Proposed:** Service classes in `app/Services` own all business and financial logic. Financial rows (sales, payments, allocations) are never hard-deleted or edited in place; corrections are made with reversals, refunds and status changes. Financial FKs use `restrictOnDelete`.
- **Advantages:** One implementation per rule; testable without UI; complete audit trail.
- **Disadvantages:** More classes; more rows over time.
- **Implications:** Reports must account for reversal rows and statuses.

## ADR-0005 — Money representation
- **Date:** 2026-11-01 · **Status:** Proposed (open question)
- **Problem:** Floating-point errors are unacceptable in GHS amounts.
- **Alternatives:** Integer pesewas (1 GHS = 100 pesewas); `decimal(12,2)` with string/bcmath arithmetic; a money library.
- **Proposed:** Integer pesewas in the database, formatted to GHS only for display. Needs owner confirmation.
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
