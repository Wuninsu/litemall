# Backup and Recovery

_No backup automation exists. This records what must be protected today._

## What to back up

| Item | Location | Why |
|---|---|---|
| Database | `database/database.sqlite` (local SQLite) | All application data |
| Environment secrets | `.env` (not in git) | `APP_KEY` is needed to decrypt encrypted values and sessions; losing it invalidates them |
| Uploaded files | `storage/app` | None exist yet |

## SQLite backup

Stop writes (`php artisan down`) or use SQLite's online backup, then copy the file:

```sh
php artisan down
cp database/database.sqlite backups/database-$(date +%F).sqlite
php artisan up
```

Restore by replacing the file with a backup copy while the app is down, then run `php artisan migrate:status` to confirm the schema matches the code.

## To decide

Production database choice, backup frequency, off-site storage, and restore testing — required before real financial data is stored.
