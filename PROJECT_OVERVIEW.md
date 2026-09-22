# Qayema — Project Overview

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
| **Public menu** | `/{slug}` — what the QR code opens | Blade, one view per template |
| Owner dashboard | separate repo `qayema-dashboard/`, on a sibling subdomain | React 19 + Vite + TS, Sanctum cookie auth against `routes/api.php` |
| Admin panel | `/admin` | Filament v4 (`app/Filament/Admin`) |

## Core domain

- **Restaurant** — one per owner. Translatable name/description/address, logo and
  cover via Spatie medialibrary on Cloudflare R2, plus phone, currency, timezone
  and the Google Maps link.
- **Menu** — categories (name + order) containing dishes (name, price,
  ingredients, one image, availability, order). Deliberately minimal: no
  descriptions, no category images, no tags.
- **Templates** — the menu designs. A new restaurant has none until the owner
  picks one, and the dashboard stays locked until they do.

## Packages

Four of them — **Free, Pro, Premium and Custom** — each holding its own limits
and features in a JSON map, edited at **/admin → Packages**. Every restaurant
points at one and starts on Free. Templates carry no price: every design is
available on every package.

Nothing is sold in the app yet. An owner asks for a package from the dashboard,
the request lands in **/admin → Contact Messages** with the package on it and an
email goes out, and an admin assigns it on the restaurant. An admin-set
`package_ends_at` drops the restaurant back to Free when it passes.

## Limits

`effective limit = the package's value + every grant on that restaurant`.

A limit of `null` means unlimited, which is how Custom is expressed.
Per-restaurant top-ups live on each restaurant's "Extra slots & add-ons" tab.
The registry of what a feature even *is* is the `App\Enums\Feature` enum — one
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

About 580 PHPUnit tests: unit (`tests/Unit`) plus feature, admin and end-to-end
journey tests (`tests/Feature`). Run `php artisan test`; format with
`vendor/bin/pint --dirty`.

## Known gaps

- The dashboard SPA is partially built — the API it consumes is complete.
- Only the `classic` template design exists.
- **No payment.** Pro, Premium and Custom are requested, not bought; an admin
  assigns them by hand. There is no checkout or billing provider.
- **What each package contains is undecided.** The seeded numbers in
  `config/package.php` and the landing copy in `lang/{en,ar}/portal.php` are
  marked `TODO(packages)` placeholders.
