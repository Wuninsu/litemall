# Database Schema

_Synchronised with `database/migrations` as of 2026-11-01. Update this file in the same change as any migration._

**Engine:** SQLite locally (`DB_CONNECTION=sqlite`), in-memory SQLite for tests.
**Key strategy:** all current tables use either auto-increment `bigint` (`id()`) or string natural keys. No UUID primary keys exist yet (see ADR-0002).
**Business tables:** none.

---

## Migration `0001_01_01_000000_create_users_table`

### `users`
Purpose: application accounts (no login UI yet). Model: `App\Models\User`.

| Column | Type | Null | Default | Notes |
|---|---|---|---|---|
| id | bigint unsigned, auto-increment | no | | PK |
| name | varchar(255) | no | | |
| email | varchar(255) | no | | **unique** |
| email_verified_at | timestamp | yes | null | cast `datetime` |
| password | varchar(255) | no | | cast `hashed` (bcrypt) |
| remember_token | varchar(100) | yes | null | |
| created_at, updated_at | timestamp | yes | null | |

### `password_reset_tokens`
Purpose: password-reset tokens (unused until auth is added).

| Column | Type | Null | Notes |
|---|---|---|---|
| email | varchar(255) | no | **PK** |
| token | varchar(255) | no | |
| created_at | timestamp | yes | |

### `sessions`
Purpose: database session driver storage (`SESSION_DRIVER=database`).

| Column | Type | Null | Notes |
|---|---|---|---|
| id | varchar(255) | no | **PK** |
| user_id | bigint unsigned | yes | **indexed**; logical reference to `users.id`, *no FK constraint* |
| ip_address | varchar(45) | yes | |
| user_agent | text | yes | |
| payload | longtext | no | |
| last_activity | integer | no | **indexed** |

## Migration `0001_01_01_000001_create_cache_table`

### `cache`
| Column | Type | Null | Notes |
|---|---|---|---|
| key | varchar(255) | no | **PK** |
| value | mediumtext | no | |
| expiration | bigint | no | **indexed** |

### `cache_locks`
| Column | Type | Null | Notes |
|---|---|---|---|
| key | varchar(255) | no | **PK** |
| owner | varchar(255) | no | |
| expiration | bigint | no | **indexed** |

## Migration `0001_01_01_000002_create_jobs_table`

### `jobs`
| Column | Type | Null | Notes |
|---|---|---|---|
| id | bigint unsigned, auto-increment | no | PK |
| queue | varchar(255) | no | **indexed** |
| payload | longtext | no | |
| attempts | smallint unsigned | no | |
| reserved_at | int unsigned | yes | |
| available_at | int unsigned | no | |
| created_at | int unsigned | no | Unix timestamp |

### `job_batches`
| Column | Type | Null | Notes |
|---|---|---|---|
| id | varchar(255) | no | **PK** |
| name | varchar(255) | no | |
| total_jobs, pending_jobs, failed_jobs | integer | no | |
| failed_job_ids | longtext | no | |
| options | mediumtext | yes | |
| cancelled_at | integer | yes | |
| created_at | integer | no | |
| finished_at | integer | yes | |

### `failed_jobs`
| Column | Type | Null | Default | Notes |
|---|---|---|---|---|
| id | bigint unsigned, auto-increment | no | | PK |
| uuid | varchar(255) | no | | **unique** |
| connection | varchar(255) | no | | |
| queue | varchar(255) | no | | |
| payload | longtext | no | | |
| exception | longtext | no | | |
| failed_at | timestamp | no | CURRENT_TIMESTAMP | |

Composite index: (`connection`, `queue`, `failed_at`).
