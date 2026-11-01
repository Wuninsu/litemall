# Troubleshooting

| Symptom | Cause | Fix |
|---|---|---|
| `Unable to locate file in Vite manifest` | Assets not built (`public/build` missing) | `npm run build`, or run `composer run dev` |
| `npm run build` fails with `Cannot find native binding` / `Cannot find module '@rolldown/binding-win32-x64-msvc'` | npm optional-dependency bug left out rolldown's platform binary (seen 2026-11-01 on Windows) | `rm -rf node_modules package-lock.json && npm install`, then build again |
| `Database file at path [...] does not exist` | SQLite file missing | Create empty `database/database.sqlite`, then `php artisan migrate` |
| Config/route changes ignored | Cached bootstrap files | `php artisan optimize:clear` |
| Tests touch the real database | Cached config overriding `phpunit.xml` | `composer test` (clears config first) |
| `No application encryption key has been specified` | Empty `APP_KEY` | `php artisan key:generate` |
| Cannot sign in, "Too many login attempts" | 5 failures in a minute for that email+IP | Wait for the countdown, or `php artisan cache:clear` locally |
| Password reset email never arrives locally | `MAIL_MAILER=log` | Copy the link from `storage/logs/laravel.log` |
| Locked out — no active administrator can sign in | Should be impossible via the UI (rule U7); can happen after manual DB edits | `php artisan app:create-admin-user` |
| `route:list --except-vendor` doesn't show Livewire pages | `Route::livewire()` routes are handled by a Livewire (vendor) controller | Run `php artisan route:list` without the flag |

Add an entry whenever a non-obvious problem is diagnosed.
