# Troubleshooting

| Symptom | Cause | Fix |
|---|---|---|
| `Unable to locate file in Vite manifest` | Assets not built (`public/build` missing) | `npm install && npm run build`, or run `composer run dev` |
| `Database file at path [...] does not exist` | SQLite file missing | Create `database/database.sqlite` (empty file), then `php artisan migrate` |
| Config/route changes ignored | Cached bootstrap files | `php artisan optimize:clear` |
| Tests touch the real database | `.env` overriding test settings / cached config | `composer test` (clears config first); tests should use in-memory SQLite from `phpunit.xml` |
| `No application encryption key has been specified` | Empty `APP_KEY` | `php artisan key:generate` |

Add an entry whenever a non-obvious problem is diagnosed.
