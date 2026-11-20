# norevel: Laravel on noredock

A complete Laravel development stack run by [noredock](https://github.com/zulfiismailovdemiri/noredock):
PostgreSQL, Mailpit, the app, a queue worker, the scheduler and Vite. There is no Docker and no
Sail, and nothing gets installed on your machine. PHP, Composer, Node and Postgres come as
checksum-verified noredock images.

| | |
|---|---|
| Laravel | 13 (`laravel/laravel` skeleton, framework 13.43 at the time of writing) |
| PHP | 8.5 + Composer 2.11 (noredock `php` + `composer` images) |
| Database | PostgreSQL 18 (noredock `postgres` image) |
| Queue / cache / sessions | Laravel's `database` drivers, in PostgreSQL |
| E-mail | Mailpit |
| Frontend | Vite + Tailwind (noredock `node` image) |

## Start

```sh
cd norevel
noredock compose up -d      # first start of a fresh clone: ~1 minute (composer + npm install)
noredock compose ps
```

| what | where |
|---|---|
| App | http://localhost:8300 |
| **noredock demo page** | http://localhost:8300/noredock: shows every service working together |
| Mailpit inbox | the `norevel-mail` port in `compose ps` (8025, or the next free one) |
| PostgreSQL | the `norevel-db` URL in `compose ps` (5432, or the next free one) |
| Names instead of ports | `noredock proxy up`, then http://app.norevel.localhost:7080 |

`noredock compose up` (without `-d`) shows all logs together, and Ctrl+C stops everything.

## The services

```
noredock compose up
 ├─ db         postgres image      database "norevel"              sandbox, localhost only
 ├─ mail       mailpit image       SMTP + web inbox                sandbox, localhost only
 ├─ app        php + composer      php artisan serve  (:8300)      sandbox, localhost only
 │               prepare: composer install · .env + key · migrate
 ├─ queue      php                 php artisan queue:work          no port, restarts on code changes
 ├─ scheduler  php                 php artisan schedule:work       no port
 └─ vite       node                npx vite  (:5173)               prepare: npm install
```

- **Start order:** `db` and `mail` start first. `app` starts once they accept connections, then
  `queue` and `scheduler`.
- **Configuration through the environment:** noredock passes the database and mail settings as
  environment variables (`noredock.env` plus the ports in `noredock.compose.toml`). Laravel prefers
  real environment variables over `app/.env`, so the app uses PostgreSQL and Mailpit while
  `app/.env` keeps Laravel's defaults. Ports are filled in automatically (`${db.port}`,
  `${mail.ports.smtp}`), so the stack also works when noredock had to move a service to another port.
- **First start:** the `prepare` step of `app` runs `composer install` (if `vendor/` is missing),
  creates `.env` and the app key (if missing), and runs the migrations on every start. `vite` runs
  `npm install` if `node_modules/` is missing.
- **Code changes:** the app picks them up on the next request (PHP's built-in server). The queue
  worker keeps code in memory, so it has `watch = ["app/*.php", "config/*.php", "routes/*.php", ".env"]`
  and noredock restarts it when those files change (`storage/` and `vendor/` are ignored). CSS/JS
  reload through Vite.
- **Isolation:** every service is sandboxed (`isolation = "sandbox"`, `network = "local"`). They can
  reach each other on localhost, but not the internet or the rest of your home folder. `composer install`
  and `npm install` run in `prepare` steps, which are not sandboxed, so downloads work.

## The demo page: /noredock

Every visit:
1. counts up a number in PostgreSQL (database cache store)
2. puts a job on the queue; the `queue` worker sends a mail "norevel: visit #N", which shows up in Mailpit
3. loads CSS/JS from the Vite dev server

The page also shows the last run of the scheduler (a heartbeat in `routes/console.php`, every
minute). `curl -H 'Accept: application/json' http://localhost:8300/noredock` returns the same as JSON.

## Everyday commands

```sh
noredock compose logs -f app queue              # follow logs
noredock compose exec app php artisan tinker
noredock compose exec app php artisan make:model Post -m
noredock compose exec app php artisan migrate
noredock compose exec app composer require laravel/sanctum
noredock compose exec vite npm install -D some-package
noredock compose exec db psql -h localhost -U postgres norevel
noredock compose exec app php artisan test      # tests: SQLite in memory, never the dev database
noredock compose restart queue                  # e.g. after installing packages
noredock compose down                           # stop (add -v to delete the database)
```

## Files

```
noredock.compose.toml    the 6 services
noredock.env             shared settings for app/queue/scheduler (no secrets)
app/                     the Laravel project (composer create-project laravel/laravel)
  routes/web.php         + /noredock demo route
  routes/console.php     + scheduler heartbeat
  resources/views/noredock.blade.php
```

How the project was created, also without installing anything:

```sh
noredock run php composer -- composer create-project laravel/laravel app
```

## Notes and limits

- **Tests and the database:** noredock sets `DB_CONNECTION=pgsql` for the app. PHPUnit's `<env>`
  values only apply when a variable isn't set yet, and Laravel reads `$_SERVER` first. So
  `app/phpunit.xml` forces `DB_CONNECTION`/`DB_DATABASE` with both `<env force="true">` and
  `<server force="true">`. That way tests always use SQLite in memory and `RefreshDatabase` can
  never wipe your development data. `tests/Feature/TestDatabaseTest.php` guards this.
- `php artisan serve` uses PHP's built-in server: fine for development, not for production.
- The PHP image has no `intl` extension. Laravel works without it, but helpers like
  `Number::format()` / `Number::currency()` need it.
- `jobs waiting` on the demo page is counted while the request runs, so it usually shows the job
  that was just queued. The worker picks it up within a second.
