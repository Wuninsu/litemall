# UUID Strategy

_Decision: ADR-0002 (Accepted 2026-11-01)._

## Rule

Every **application** table — including `users` and all future business tables — uses a UUID primary key column named `id`. Framework infrastructure tables (`cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `password_reset_tokens`, `sessions`) keep Laravel's defaults; `sessions.user_id` is a UUID because it references `users.id`.

## How

- Migration: `$table->uuid('id')->primary();` and `$table->foreignUuid('x_id')` for references.
- Model: `use HasUuids;` (`Illuminate\Database\Eloquent\Concerns\HasUuids`). Laravel generates **ordered UUIDv7** values on `creating`, so ids sort roughly by creation time and index inserts stay efficient.
- Route model binding and `findOrFail()` work on the UUID (e.g. `Users\Index::edit($userId)`).
- Factories need no change — ids are generated automatically.

## Why

- Records can be created on a device without asking the server for the next number, which future offline operation needs (ADR-0007).
- Ids in URLs are not guessable or enumerable.

## Implications

- Never rely on `id` order as a strict creation sequence; use `created_at`.
- Human-facing sequential numbers (e.g. receipt numbers) must be separate columns, not the primary key.
- Storage: `char(36)` on MySQL, `varchar` on SQLite, native `uuid` on PostgreSQL.

## Implemented

| Table | UUID PK | Model |
|---|---|---|
| users | yes | `App\Models\User` (`HasUuids`) |
