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

Use `composer serve` (or `composer dev`, which runs it with the queue), not
`php artisan serve`. The app accepts images up to 20 MB and turns each into a
small WebP, but a stock PHP install caps `upload_max_filesize` at 2 MB, and
PHP discards a larger file before Laravel ever sees it. `php artisan serve`
cannot fix that even with `php -d …` in front: it starts the real server as a
second PHP process with the stock limits. `composer serve` runs PHP's built-in
server directly, with the limits below.

A deployed host needs the same values. With PHP-FPM, `public/.user.ini` sets
them already; otherwise put them in `php.ini`. The web server must also let
the body through (nginx: `client_max_body_size 25m;`).

| Setting | Value | Why |
|---|---|---|
| `upload_max_filesize` | `20M` | The largest image the app accepts (`UploadLimits::APP_MAX_BYTES`) |
| `post_max_size` | `25M` | The file plus the rest of the form |
| `memory_limit` | `512M` | GD decodes the original before resizing; a 6000 × 6000 photo needs a few hundred MB. `MediaService` also raises it for the one request when a server is set lower |

The seed creates an admin (`admin@admin.com` / `password`) and the `classic`
design. The four packages (Free, Pro, Premium, Custom) are seeded by their
migration from `config/package.php`, so a fresh database already has working
limits.

Sign in to the admin panel at `/admin`.

## How it fits together

| Concept | Where it lives |
|---|---|
| What a package allows | `App\Enums\Feature` + `packages.features` + `feature_grants` |
| Resolving a limit | `App\Services\Packages\Entitlements` — package + Σ grants, in force between its dates |
| Moving a restaurant up | `/admin → Restaurants → Package` (Change package / Extend) or "Apply this package" on a request — all through `PackageAssigner`, with history |
| Asking for a package | `POST /api/packages/request` → a `contact_messages` row + an email |
| A menu design | a `templates` row + `resources/views/menu/templates/{slug}.blade.php` |

## Common tasks

```bash
composer test                                 # every unit + feature test
composer test:coverage                        # the same, failing under the coverage floor
php artisan test --filter=EntitlementsTest
vendor/bin/pint --dirty                       # format before committing

php artisan make:menu-template midnight       # scaffold a design
php artisan stats:rollup                      # prune old menu_sessions
```

Unit tests live in `tests/Unit`, feature/journey tests in `tests/Feature`.
Coverage needs the `pcov` PHP extension (`sudo apt install php8.4-pcov`, or
any `pcov.so` loaded from an ini file).

### End-to-end tests

The browser suite lives in `../qayema-dashboard/e2e` (Playwright) and drives
this app, the dashboard, the public menu and the admin together. It runs this
app with `APP_ENV=e2e`, which loads `.env.e2e`: its own SQLite file
(`database/e2e.sqlite`), logged mail, media on the `e2e` disk, on port 8001 —
never your `.env` or MySQL.

```bash
composer serve:e2e    # the e2e server (Playwright starts it for you)
composer e2e:reset    # fresh e2e database; refuses anywhere but APP_ENV=e2e
```

`routes/e2e.php` (only loaded in that environment) lets a test build the owner
it needs (`POST /__e2e/scenario`) and sign in without the form.

## Going live — the checklist

1. `APP_ENV=production`, `APP_DEBUG=false`, and set `APP_NAME=Qayema` (it still
   reads `Laravel`, which shows in the public menu footer).
2. Check what each package contains and set real prices at `/admin →
   Packages` (the contents are decided, the prices are placeholders). Nothing
   is sold in-app: owners request a package and an
   admin assigns it, so set `CONTACT_RECIPIENT_EMAIL` or those requests reach
   nobody.
3. Set `CORS_ALLOWED_ORIGINS` and `SANCTUM_STATEFUL_DOMAINS` to the dashboard's
   subdomain, and `SESSION_DOMAIN` to the shared registrable domain
   (e.g. `.qayema.com`) so the session cookie is shared.
4. Point the R2 (`s3`) disk at the production bucket.
5. Schedule the task runner so `stats:rollup` runs nightly.

## Status

The API, the dashboard and the admin are built and tested. Only the `classic`
design has a view of its own. Nothing is sold in-app: packages are assigned by
an admin.
