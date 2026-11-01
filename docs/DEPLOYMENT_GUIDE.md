# Deployment Guide

> **The application is not production-ready** (no authentication, see [SECURITY.md](SECURITY.md)). This guide records the verified requirements and the standard Laravel procedure for when it is. No production environment has been set up or tested yet.

## Server requirements

- PHP ≥ 8.3 (`composer.json`), with the extensions Laravel 13 requires plus the PDO driver for the chosen database.
- Composer 2.
- Node.js + npm on the build machine (assets are built with Vite; output goes to `public/build`).
- A web server (Nginx/Apache) whose document root is `public/`.
- HTTPS is required (sessions and future financial operations).

## Database

SQLite is the only database configured and tested so far. Choosing a production database (e.g. MySQL/PostgreSQL) needs an ADR; set `DB_CONNECTION` and related `DB_*` variables accordingly.

## Environment configuration

Start from `.env.example`. In production set at least:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain
APP_KEY=            # generate once: php artisan key:generate
DB_CONNECTION=...
SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
```

## Procedure

```sh
composer install --no-dev --optimize-autoloader
npm ci            # once package-lock.json exists; otherwise npm install
npm run build
php artisan migrate --force
php artisan optimize          # caches config, routes, views, events
```

On subsequent deploys: `php artisan down`, pull code, repeat the above, `php artisan up`. Clear caches with `php artisan optimize:clear` if configuration changes do not take effect.

## Queues

`QUEUE_CONNECTION=database`. No jobs exist yet; when they do, run a supervised `php artisan queue:work` process.

## Scheduler

No scheduled tasks exist. When added, run `php artisan schedule:run` every minute via cron.
