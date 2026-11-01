# Development Guide

## Prerequisites

- PHP ≥ 8.3 (8.4 used locally via Laragon) with the `pdo_sqlite` extension.
- Composer 2.
- Node.js and npm (local: Node 24, npm 11) for Vite/Tailwind.

## First-time setup

```sh
composer run setup
```

Runs, in order: `composer install`, copy `.env.example` → `.env` if missing, `php artisan key:generate`, `php artisan migrate --force`, `npm install --ignore-scripts`, `npm run build`. The database is SQLite at `database/database.sqlite`.

Then create accounts, either:

```sh
php artisan db:seed                 # local only: admin@example.com / cashier@example.com, password "password"
php artisan app:create-admin-user   # any environment: prompts for name, email, password
```

Reset everything locally with `php artisan migrate:fresh --seed`.

## Daily workflow

```sh
composer run dev        # php artisan dev: web server, Vite, queue worker, log tailing
php artisan test --compact
vendor/bin/pint --dirty --format agent
```

Password-reset emails go to `storage/logs/laravel.log` locally (`MAIL_MAILER=log`).

## Adding a feature

1. Read [ARCHITECTURE.md](ARCHITECTURE.md) and the relevant docs.
2. Migrations/models: `php artisan make:model Name -mf --no-interaction`; UUID PK + `HasUuids` ([UUID_STRATEGY.md](UUID_STRATEGY.md)); money as integer pesewas (ADR-0005).
3. Service: `php artisan make:class Services/NameService --no-interaction`; throw `DomainException` subclasses.
4. Policy: `php artisan make:policy NamePolicy --model=Name --no-interaction`.
5. UI: `php artisan make:livewire Area/Name --no-interaction` (class-based by config); register with `Route::livewire()`.
6. Tests: `php artisan make:test --phpunit --no-interaction NameTest`.
7. Run tests and Pint; update docs per AGENTS.md §12 and `CHANGELOG.md`.

## Useful inspection commands

```sh
php artisan about
php artisan route:list --except-vendor   # note: Route::livewire routes are listed only without --except-vendor
php artisan migrate:status
php artisan config:show database.default
composer show --direct
```
