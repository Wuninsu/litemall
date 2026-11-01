# Development Guide

## Prerequisites

- PHP ≥ 8.3 (8.4 used locally via Laragon) with the `pdo_sqlite` extension.
- Composer 2.
- Node.js and npm (local: Node 24, npm 11) for Vite/Tailwind.

## First-time setup

The `composer setup` script performs a full install:

```sh
composer run setup
```

It runs, in order: `composer install`, copies `.env.example` to `.env` if missing, `php artisan key:generate`, `php artisan migrate --force`, `npm install --ignore-scripts`, `npm run build`.

The default database is SQLite at `database/database.sqlite`.

## Daily workflow

```sh
composer run dev        # runs `php artisan dev`: web server, Vite, queue worker, log tailing
php artisan test --compact
vendor/bin/pint --dirty --format agent
```

## Adding code

1. Generate files with `php artisan make:* --no-interaction` (see `CLAUDE.md` for Boost conventions).
2. Put business logic in services (see [SERVICE_LAYER.md](SERVICE_LAYER.md)).
3. Write feature tests: `php artisan make:test --phpunit --no-interaction SomethingTest`.
4. Run tests and Pint.
5. Update the docs listed in AGENTS.md §12 and `CHANGELOG.md`.

## Useful inspection commands

```sh
php artisan about
php artisan route:list --except-vendor
php artisan migrate:status
php artisan config:show database.default
composer show --direct
```
