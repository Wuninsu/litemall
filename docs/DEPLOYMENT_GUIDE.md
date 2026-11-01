# Deployment Guide

> No production environment has been set up or tested yet. The application has authentication and user management but no business features, and no audit log (see [SECURITY.md](SECURITY.md)). This guide records verified requirements and the standard Laravel procedure.

## Server requirements

- PHP ≥ 8.3 (`composer.json`), with the extensions Laravel 13 requires plus the PDO driver for the chosen database.
- Composer 2.
- Node.js + npm on the build machine (Vite output goes to `public/build`).
- A web server (Nginx/Apache) whose document root is `public/`.
- HTTPS is required (session cookies, password entry, future financial operations).

## Database

SQLite is the only database configured and tested so far. Choosing a production database (e.g. MySQL/PostgreSQL) needs an ADR; UUID columns map to `char(36)` on MySQL and `uuid` on PostgreSQL.

## Environment configuration

Start from `.env.example`. In production set at least:

```dotenv
APP_NAME=...
APP_ENV=production       # also enables the stronger password rules
APP_DEBUG=false
APP_URL=https://your-domain   # used in password-reset links
APP_KEY=                 # generate once: php artisan key:generate
DB_CONNECTION=...
SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
MAIL_MAILER=...          # a real mailer, or reset emails are only logged
MAIL_FROM_ADDRESS=...
```

## First deployment

```sh
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan optimize          # caches config, routes, views, events
php artisan app:create-admin-user
```

Do **not** run `php artisan db:seed` in production (it refuses to, but don't rely on that).

## Subsequent deployments

`php artisan down`, pull code, `composer install --no-dev --optimize-autoloader`, `npm ci && npm run build`, `php artisan migrate --force`, `php artisan optimize`, `php artisan up`. Use `php artisan optimize:clear` if configuration changes do not take effect.

## Queues

`QUEUE_CONNECTION=database`. No queued jobs exist yet (reset-link notifications are sent synchronously). When jobs are added, run a supervised `php artisan queue:work` process.

## Scheduler

No scheduled tasks exist. When added, run `php artisan schedule:run` every minute via cron.
