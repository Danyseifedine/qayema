# Qayema — Project Overview

> **Qayema** is a bilingual (English/Arabic) SaaS for digital restaurant menus, built as a Laravel 12 API + Filament v4 admin panel with a separate React 19 dashboard SPA (`qayema-dashboard/`). Owners build their menu in the SPA; billing is one-time feature purchases through Paddle (Laravel Cashier Paddle).

> The detailed, agent-facing architecture notes live in [CLAUDE.md](CLAUDE.md) (see the "Qayema — Project Architecture" section). The database diagram lives in [docs/database-erd.md](docs/database-erd.md). This file is the short human-facing map.

## Surfaces

| Surface | Where | Stack |
|---|---|---|
| Public portal | `/` (landing), `/contact`, legal pages | Blade under `resources/views/portal/**` |
| Auth + onboarding | `/get-started`, Google OAuth, `/onboarding` wizard | Blade + Alpine, `routes/auth.php` |
| Owner dashboard | separate repo folder `qayema-dashboard/`, served on a sibling subdomain | React 19 + Vite + TS + Tailwind + shadcn/ui, Sanctum cookie auth against `routes/api.php` |
| Admin panel | `/admin` | Filament v4 (`app/Filament/Admin`) |

The old Blade owner dashboard, public QR menu page, and AI menu scanner (Gemini) were removed; the React SPA + a public menu to be rebuilt per-template supersede them.

## Core domain

- **Restaurant** (per owner `User`) with translatable name/description/address, media (logo/cover via Spatie medialibrary on Cloudflare R2), categories → dishes, social links, statistics.
- **Templates** are the menu layouts; a new restaurant has none until the owner picks one (SPA locks every tab except Templates until then).
- **Features & limits**: `features` (limit or boolean) bundled to templates via `template_feature`, granted to restaurants via `restaurant_features`. Effective package resolved by `App\Services\Global\Package` — plan limits merge MAX, purchased grants stack additively. Editable floor defaults in `package_defaults`.

## Billing (Paddle, one-time only)

`config/paddle.php` maps each purchasable add-on (e.g. dish-slot packs of 50) to a Paddle price. Flow: SPA cart → `POST /api/checkout` → Paddle.js overlay → `POST /paddle/webhook` → `TransactionCompleted` → `GrantPurchasedFeatures` → `FeatureFulfillment` writes an idempotent `restaurant_features` grant (`reference` = Paddle transaction id).

## Security layer

Named rate limiters (`api`, `mutations`, `uploads`, `auth`, `contact`) in `AppServiceProvider`, abuse auto-ban (`AbuseGuard` + `blocked_ips`), `SecurityHeaders` middleware, reCAPTCHA v3 on contact, Sanctum stateful SPA auth with cross-domain CSRF token endpoint.

## Testing

PHPUnit feature tests under `tests/Feature` (API CRUD, package/limits, purchases, onboarding, auth, security). Run `php artisan test`; format with `vendor/bin/pint --dirty`.
