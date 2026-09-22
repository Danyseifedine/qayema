# Qayema — Database ER Diagram

Generated from the live schema (2026-09-22). 23 migrations, one per table.

Notation: `||--o{` one-to-many (FK) · `|o--o{` optional/nullable FK · dotted `..`
no real FK (polymorphic `media`, `sessions.user_id`) · **PK** primary · **FK**
foreign · **UK** unique.

Translatable columns (`name`, `description`, `address`, `ingredients`) are
spatie/laravel-translatable JSON: `{"en": "…", "ar": "…"}`. The owner only ever
writes the locale in `restaurants.default_locale`.

## 1. Core menu domain

```mermaid
erDiagram
    users {
        bigint id PK
        varchar name
        varchar email UK
        varchar role "admin or menu_owner"
        varchar password "nullable - Google-only accounts"
        tinyint onboarding_step "0-3"
        timestamp onboarding_completed_at "nullable"
        timestamp email_verified_at "nullable"
        varchar remember_token "nullable"
        timestamp deleted_at "soft delete"
        timestamps created_updated
    }

    restaurants {
        bigint id PK
        bigint user_id FK "UK - one restaurant per owner"
        bigint template_id FK "nullable, set null - null until chosen"
        bigint package_id FK "nullable, set null - the plan in force"
        timestamp package_started_at "nullable"
        timestamp package_ends_at "nullable = no expiry; past = falls back to default"
        json name "translatable"
        json description "translatable, nullable"
        varchar slug UK "the public menu URL"
        json address "translatable, nullable"
        varchar google_maps_url "nullable"
        char country_code "2, nullable"
        varchar phone "30, nullable"
        char currency "3, default USD"
        char default_locale "2, ar or en"
        varchar timezone "default UTC"
        tinyint is_active "default 1"
        json template_settings "owner's choices for the active template"
        json qr_settings "QR card design"
        timestamps created_updated
    }

    categories {
        bigint id PK
        bigint restaurant_id FK "cascade"
        json name "translatable"
        int display_order "default 0"
        timestamps created_updated
    }

    dishes {
        bigint id PK
        bigint restaurant_id FK "cascade"
        bigint category_id FK "nullable, set null"
        json name "translatable"
        json ingredients "translatable, nullable"
        decimal price "10,2 nullable"
        tinyint is_available "default 1"
        int display_order "default 0"
        timestamps created_updated
    }

    restaurant_social_links {
        bigint id PK
        bigint restaurant_id FK "cascade"
        varchar platform "instagram, x, facebook, tiktok"
        varchar url
        timestamps created_updated
    }

    social_accounts {
        bigint id PK
        bigint user_id FK "cascade"
        varchar provider "google"
        varchar provider_user_id "UK with provider"
        text access_token "nullable, encrypted"
        text refresh_token "nullable, encrypted"
        timestamp token_expires_at "nullable"
        text avatar "nullable"
        timestamps created_updated
    }

    media {
        bigint id PK
        varchar model_type "polymorphic"
        bigint model_id "polymorphic"
        char uuid UK
        varchar collection_name "logo, cover_image, image, thumbnail"
        varchar file_name
        varchar disk
        bigint size
        timestamps created_updated
    }

    users ||--o| restaurants : "owns (1:1)"
    users ||--o{ social_accounts : "Google OAuth"
    restaurants ||--o{ categories : "has"
    restaurants ||--o{ dishes : "has"
    categories |o--o{ dishes : "groups (set null)"
    restaurants ||--o{ restaurant_social_links : "has"
    restaurants |o..o{ media : "logo, cover_image"
    dishes |o..o{ media : "image"
```

## 2. Templates, packages & entitlements

What an owner gets in one line: **the package their restaurant is on, plus any
grants on top.** Templates are pure design and grant nothing.

```mermaid
erDiagram
    templates {
        bigint id PK
        varchar slug UK "matches the Blade view name"
        json name "translatable"
        json description "translatable, nullable"
        json settings_schema "what the owner may customize"
        tinyint is_active "default 1"
        int sort_order "default 0"
        timestamps created_updated
    }

    packages {
        bigint id PK
        varchar slug UK "32 - free, pro, premium, custom"
        json name "translatable"
        json description "translatable, nullable"
        int price_cents "nullable = contact us; 0 = free"
        char currency "3, default USD"
        tinyint is_contact_only "default 0"
        tinyint is_default "default 0 - exactly one row"
        int sort_order "default 0"
        json features "feature slug to int or null for unlimited"
        timestamps created_updated
    }

    restaurant_features {
        bigint id PK
        bigint restaurant_id FK "cascade"
        varchar feature "an App\\Enums\\Feature case"
        int value "added on top of the package"
        varchar source "admin, purchase"
        varchar reference "nullable - UK with restaurant+feature"
        timestamp ends_at "nullable = never expires"
        timestamps created_updated
    }

    packages ||--o{ restaurants : "entitles"
    templates |o--o{ restaurants : "styles (nullable)"
    restaurants ||--o{ restaurant_features : "granted"
    templates |o..o{ media : "thumbnail"
```

**How a limit is resolved** — `App\Services\Global\Entitlements`:

```
effective value = packages.features[feature] + Σ (active restaurant_features grants)
```

Limits add up; flags (`qr_studio`) are on if anything says on; a `null` value is
unlimited and stays unlimited however many grants sit on it. A feature missing
from a package's map falls back to `App\Enums\Feature::defaultValue()`.

Cached as `entitlements:{id}` for 300s, flushed by `RestaurantFeature`
saved/deleted hooks, by a restaurant whose `package_id`/`package_ends_at`
changed, and for **every** restaurant when a package itself is saved.

The four rows are seeded by the `packages` migration from
`config('package.catalog')` and edited at `/admin → Packages`. Nothing is sold
in-app: an owner asks through `POST /api/packages/request`, which writes a
`contact_messages` row carrying `user_id` and `package_id`.

## 3. Analytics, contact & framework tables

```mermaid
erDiagram
    menu_sessions {
        bigint id PK
        bigint restaurant_id FK "cascade"
        varchar session_id "indexed"
        varchar device_type "mobile, tablet, desktop"
        varchar browser "nullable"
        varchar os "nullable"
        timestamp viewed_at "indexed with restaurant_id"
        int time_spent "nullable, seconds"
        int page_views "default 1"
        tinyint via_qr "default 0 - set from ?qr=1"
        int whatsapp_orders "default 0"
        timestamps created_updated
    }

    blocked_ips {
        bigint id PK
        varchar ip "indexed"
        varchar reason "nullable"
        timestamp expires_at "nullable = permanent"
        timestamps created_updated
    }

    contact_messages {
        bigint id PK
        varchar name
        varchar email
        text message
        varchar ip_address "indexed, 45"
        bigint user_id FK "nullable, set null - set for a package request"
        bigint package_id FK "nullable, set null - which package was asked for"
        timestamps created_updated
    }

    restaurants ||--o{ menu_sessions : "visits"
    users |o--o{ contact_messages : "asked for a package"
    packages |o--o{ contact_messages : "requested"
```

Also present, unchanged from the framework defaults: `sessions`,
`password_reset_tokens`, `cache`, `cache_locks`, `jobs`, `job_batches`,
`failed_jobs`, and `telescope_*` in local.

## Regenerating this file

```bash
php artisan migrate:fresh            # a clean schema
php artisan db:show --counts         # tables at a glance
php artisan db:table restaurants     # one table's columns and indexes
```
