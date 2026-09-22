# Qayema

Bilingual (Arabic/English) digital menus for restaurants. Owners build a menu,
pick a design, and share it as a QR code; guests open it at `qayema.com/{slug}`.

- **This repo** — Laravel 12 API, public portal, public menu and Filament v4 admin.
- **`../qayema-dashboard`** — the owner dashboard SPA (React 19 + Vite).

New here? Read [PROJECT_OVERVIEW.md](PROJECT_OVERVIEW.md) first, then
[docs/database-erd.md](docs/database-erd.md).

## Requirements

PHP 8.2+, Composer, MySQL 8, Node (for the separate dashboard repo only — this
app ships static CSS and has no build step).

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
composer serve
```

Use `composer serve`, not `php artisan serve`. The app accepts images up to
10 MB, but a stock PHP install caps `upload_max_filesize` at 2 MB, and PHP
discards a larger file before Laravel ever sees it. The script starts the
built-in server with limits that match the app.

A deployed host needs the same three values set in its own `php.ini`:

| Setting | Minimum | Why |
|---|---|---|
| `upload_max_filesize` | `10M` | The largest image the app accepts |
| `post_max_size` | `12M` | The file plus the rest of the form |
| `memory_limit` | `256M` | GD decodes the original before resizing, and a 6000 × 6000 image needs roughly 144 MB |

The seed creates an admin (`admin@admin.com` / `password`) and the `classic`
template. The four packages (Free, Pro, Premium, Custom) are seeded by their
migration from `config/package.php`, so a fresh database already has working
limits.

Sign in to the admin panel at `/admin`.

## How it fits together

| Concept | Where it lives |
|---|---|
| What a package allows | `App\Enums\Feature` + `packages.features` + `restaurant_features` |
| Resolving a limit | `App\Services\Global\Entitlements` — package + Σ grants |
| Moving a restaurant up | `/admin → Restaurants → Package` (an owner asks, an admin assigns) |
| Asking for a package | `POST /api/packages/request` → a `contact_messages` row + an email |
| A menu design | a `templates` row + `resources/views/menu/templates/{slug}.blade.php` |

## Common tasks

```bash
php artisan test                              # ~580 tests, unit + feature
php artisan test --filter=EntitlementsTest
vendor/bin/pint --dirty                       # format before committing

php artisan make:menu-template midnight       # scaffold a design
php artisan stats:rollup                      # prune old menu_sessions
```

Unit tests live in `tests/Unit`, feature/journey tests in `tests/Feature`.

## Going live — the checklist

1. `APP_ENV=production`, `APP_DEBUG=false`, and set `APP_NAME=Qayema` (it still
   reads `Laravel`, which shows in the public menu footer).
2. Set what each package contains at `/admin → Packages` — the seeded numbers
   are placeholders. Nothing is sold in-app: owners request a package and an
   admin assigns it, so set `CONTACT_RECIPIENT_EMAIL` or those requests reach
   nobody.
3. Set `CORS_ALLOWED_ORIGINS` and `SANCTUM_STATEFUL_DOMAINS` to the dashboard's
   subdomain, and `SESSION_DOMAIN` to the shared registrable domain
   (e.g. `.qayema.com`) so the session cookie is shared.
4. Point the R2 (`s3`) disk at the production bucket.
5. Schedule the task runner so `stats:rollup` runs nightly.

## Status

The API is complete and tested. The dashboard SPA is not: it currently contains
only an auth bootstrap, so owners can sign up and onboard but have no UI to
build their menu with yet. Only the free `classic` design exists.
