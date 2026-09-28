# Qayema: Project Overview

> **Qayema** is a bilingual (English/Arabic) SaaS for digital restaurant menus:
> Laravel 12 API + Filament v4 admin, with a separate React dashboard SPA
> (`qayema-dashboard/`). Owners build their menu, pick a design, and share it as
> a QR code. What an owner gets comes from the **package** their restaurant is
> on: Free, Pro, Premium or Custom.

Agent-facing architecture notes live in [CLAUDE.md](CLAUDE.md). The schema
diagram lives in [docs/database-erd.md](docs/database-erd.md). This file is the
short human-facing map.

## Surfaces

| Surface | Where | Stack |
|---|---|---|
| Public portal | `/` (landing), `/contact`, legal pages | Blade + static CSS under `public/portal/` |
| Auth + onboarding | `/get-started`, Google OAuth, `/onboarding` (3 steps) | Blade + Alpine, `routes/auth.php` |
| **Public menu** | `/{slug}`, what the QR code opens | Blade, one view per template |
| Owner dashboard | separate repo `qayema-dashboard/`, on a sibling subdomain | React 19 + Vite + TS, Sanctum cookie auth against `routes/api.php` |
| Admin panel | `/admin` | Filament v4 (`app/Filament/Admin`) |

## Core domain

- **Restaurant**: one per owner. Translatable name/description, a Google Maps link, logo and
  cover via Spatie medialibrary on Cloudflare R2, plus phone, currency, timezone
  and the Google Maps link.
- **Menu**: categories (name, an optional one-line description, order)
  containing dishes (name, price, ingredients, one image, availability, order).
  Deliberately minimal: no category images, no tags.
- **Designs** (`Template` rows): how the menu looks. A new restaurant has none
  until the owner picks one, and the dashboard stays locked until they do. A
  design marked premium needs a package with premium designs.

## Packages

Four of them, **Free, Pro, Premium and Custom**, each holding its own limits
and features in a JSON map, edited at **/admin → Packages**. Every restaurant
points at one and starts on Free. Free is a plain English menu; Pro adds a
second language, the owner's own look and analytics; Premium adds ordering,
the QR studio, premium designs and advanced analytics; Custom is unlimited.

Nothing is sold in the app yet. An owner asks for a package from the dashboard,
the request lands in **/admin → Contact Messages** with the package on it and an
email goes out, and an admin applies it: from a date, for some months or
forever. Every change is kept in the restaurant's package history, and the
admin home lists the packages ending soon.

## Limits

`effective limit = the package's value + every grant on that restaurant`.

A limit of `null` means unlimited, which is how Custom is expressed.
Per-restaurant top-ups live on each restaurant's "Extra slots & add-ons" tab.
The registry of what a feature even *is* is the `App\Enums\Feature` enum, one
case per feature.

## Adding a template

```bash
php artisan make:menu-template midnight
```

That writes the database row and a Blade view at
`resources/views/menu/templates/midnight.blade.php`. Design the view; set the
thumbnail and which settings owners may change (colours, text, toggles) in the
admin panel. No other code changes.

## Security

Named rate limiters (`api`, `mutations`, `uploads`, `auth`, `contact`) with an
abuse auto-ban (`AbuseGuard` + `blocked_ips`), `SecurityHeaders` middleware,
reCAPTCHA v3 on contact, encrypted OAuth tokens, and Sanctum stateful SPA auth
with a cross-domain CSRF token endpoint.

## Testing

- PHPUnit here in three suites: `tests/Unit` (plain PHP), `tests/Integration`
  (app and database, no request) and `tests/Feature` (HTTP, admin, journeys). `composer test`, `composer test:coverage` (fails under the
  coverage floor); format with `vendor/bin/pint --dirty`.
- Vitest in the dashboard repo for its components, hooks and pages.
- Playwright end-to-end in `../qayema-dashboard/e2e`, against this app running
  with `APP_ENV=e2e`; its support code and settings are all in `tests/E2e/`
  (see README).

## Known gaps

- Only the `classic` design has a view of its own.
- **No payment.** Pro, Premium and Custom are requested, not bought; an admin
  assigns them by hand. There is no checkout or billing provider.
- **Real prices.** Package prices are placeholders; the landing page reads
  them (and everything else it lists) from /admin → Packages.
