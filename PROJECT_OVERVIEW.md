# Qayema — Project Overview

> **Qayema** is a bilingual (English/Arabic) SaaS for digital restaurant menus:
> Laravel 12 API + Filament v4 admin, with a separate React dashboard SPA
> (`qayema-dashboard/`). Owners build their menu, pick a design, and share it as
> a QR code. Money works in two steps — Paddle sells **Qayema coins**, and coins
> buy things inside the app.

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

## Coins

Paddle sells `coin_packs` and nothing else. `coin_transactions` is an
append-only ledger; `users.coin_balance` is a cached total kept in step inside
the same transaction, so the two can't drift. A re-delivered webhook can't
double-credit — the unique `(type, reference)` index is the idempotency key.

Owners spend coins on paid **templates**. Unlocking is permanent, so switching
between owned designs is always free.

## Limits

`effective limit = the admin-set default + every grant on that restaurant`.

Defaults (dishes, categories, social links, QR Studio) are edited at
**/admin → Plan Limits** and apply to everyone at once; per-restaurant top-ups
live on each restaurant's "Extra slots & add-ons" tab. The registry of what a
feature even *is* is the `App\Enums\Feature` enum — one case per feature.

## Adding a template

```bash
php artisan make:menu-template midnight --price=650
```

That writes the database row and a Blade view at
`resources/views/menu/templates/midnight.blade.php`. Design the view; set the
price, thumbnail and which settings owners may change (colours, text, toggles)
in the admin panel. No other code changes.

## Security

Named rate limiters (`api`, `mutations`, `uploads`, `auth`, `contact`) with an
abuse auto-ban (`AbuseGuard` + `blocked_ips`), `SecurityHeaders` middleware,
reCAPTCHA v3 on contact, encrypted OAuth tokens, and Sanctum stateful SPA auth
with a cross-domain CSRF token endpoint.

## Testing

About 650 PHPUnit tests: unit (`tests/Unit`) plus feature, admin and end-to-end
journey tests (`tests/Feature`). Run `php artisan test`; format with
`vendor/bin/pint --dirty`.

## Known gaps

- The dashboard SPA is an auth bootstrap only — the API it consumes is complete.
- Only the free `classic` template design exists.
- The seeded coin packs have no production Paddle price ids yet, so nothing is
  sellable live until those are filled in.
