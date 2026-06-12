# Qayema — Database ER Diagram

Engine: MySQL · database `menux` · 23 tables. Generated from the live schema (2026-06-09).

Split into three diagrams for readability (GitHub renders each Mermaid block natively).

Notation: `||--o{` one-to-many (FK) · `|o--o{` optional/nullable FK · dotted `..` no real FK
(polymorphic `media`, `sessions.user_id`) · **PK** primary · **FK** foreign · **UK** unique.

## 1. Core menu domain

```mermaid
erDiagram
    users {
        bigint id PK
        varchar name
        varchar email UK
        varchar role "admin or menu_owner"
        timestamp email_verified_at "nullable"
        varchar password "nullable - Google-only"
        varchar remember_token "nullable"
        tinyint onboarding_step "default 0"
        timestamp onboarding_completed_at "nullable"
        timestamp deleted_at "soft delete"
        timestamp created_at
        timestamp updated_at
    }

    restaurants {
        bigint id PK
        bigint user_id FK "cascade delete"
        bigint restaurant_type_id FK "nullable, set null"
        bigint template_id FK "nullable, set null"
        varchar name
        text description "nullable"
        varchar slug UK "public menu URL"
        varchar country_code "nullable"
        varchar phone "nullable"
        text address "nullable"
        varchar google_maps_url "nullable"
        varchar currency "default USD"
        varchar preferred_language "default en"
        tinyint is_active "default 1"
        int dish_limit "default 40"
        int category_limit "default 10"
        int social_link_limit "default 4"
        json template_settings "nullable"
        json qr_settings "nullable"
        timestamp created_at
        timestamp updated_at
    }

    categories {
        bigint id PK
        bigint restaurant_id FK "cascade delete"
        varchar name
        text description "nullable"
        int display_order "default 0"
        timestamp created_at
        timestamp updated_at
    }

    dishes {
        bigint id PK
        bigint restaurant_id FK "cascade delete"
        bigint category_id FK "nullable, set null"
        varchar name
        decimal price "10,2 nullable"
        text ingredients "nullable"
        json tags "nullable"
        tinyint is_available "default 1"
        int display_order "default 0"
        timestamp created_at
        timestamp updated_at
    }

    restaurant_social_links {
        bigint id PK
        bigint restaurant_id FK "cascade delete"
        varchar platform "instagram, x, facebook, tiktok"
        varchar url
        timestamp created_at
        timestamp updated_at
    }

    social_accounts {
        bigint id PK
        bigint user_id FK "cascade delete"
        varchar provider "google"
        varchar provider_user_id UK "unique with provider"
        text access_token "nullable, encrypted"
        text refresh_token "nullable, encrypted"
        timestamp token_expires_at "nullable"
        text avatar "nullable"
        timestamp created_at
        timestamp updated_at
    }

    media {
        bigint id PK
        varchar model_type "polymorphic"
        bigint model_id "polymorphic"
        char uuid UK
        varchar collection_name "logo, cover_image, images, image, thumbnail"
        varchar name
        varchar file_name
        varchar mime_type "nullable"
        varchar disk
        varchar conversions_disk "nullable"
        bigint size
        json manipulations
        json custom_properties
        json generated_conversions
        json responsive_images
        int order_column "nullable"
        timestamp created_at
        timestamp updated_at
    }

    users ||--o| restaurants : "owns"
    users ||--o{ social_accounts : "Google OAuth"
    restaurants ||--o{ categories : "has"
    restaurants ||--o{ dishes : "has"
    categories |o--o{ dishes : "groups (set null)"
    restaurants ||--o{ restaurant_social_links : "has"
    restaurants |o..o{ media : "logo, cover (polymorphic)"
    dishes |o..o{ media : "images (polymorphic)"
    categories |o..o{ media : "image (polymorphic)"
```

## 2. Templates, tags & analytics

```mermaid
erDiagram
    restaurants {
        bigint id PK "see diagram 1 for all fields"
        varchar name
        bigint template_id FK
        bigint restaurant_type_id FK
    }

    templates {
        bigint id PK
        varchar name
        varchar slug UK
        text description "nullable"
        json fields "nullable"
        tinyint is_active "default 1"
        tinyint has_logo
        tinyint has_cover_image
        tinyint has_description
        tinyint has_phone
        tinyint has_address
        tinyint has_map
        tinyint has_schedule
        tinyint has_social_links
        tinyint has_dish_images
        tinyint has_dish_ingredients
        tinyint has_dish_prices
        tinyint has_dish_tags
        tinyint has_category_images
        tinyint has_category_description
        tinyint has_search
        tinyint has_search_title
        tinyint has_order_page_title
        tinyint has_final_price_show
        tinyint has_share_button
        tinyint has_qr_code
        varchar default_direction "ltr or rtl"
        tinyint allows_direction_change
        timestamp created_at
        timestamp updated_at
    }

    tags {
        bigint id PK
        varchar name
        varchar slug UK
        enum category "cuisine, dietary, vibe, style"
        timestamp created_at
        timestamp updated_at
    }

    restaurant_tag {
        bigint restaurant_id PK, FK "cascade"
        bigint tag_id PK, FK "cascade"
    }

    template_tag {
        bigint template_id PK, FK "cascade"
        bigint tag_id PK, FK "cascade"
    }

    restaurant_types {
        bigint id PK
        varchar name
        varchar slug UK
        varchar icon "nullable"
        timestamp created_at
        timestamp updated_at
    }

    restaurant_statistics {
        bigint id PK
        bigint restaurant_id FK "cascade delete"
        varchar session_id "indexed"
        varchar device_type "nullable"
        varchar browser "nullable"
        varchar os "nullable"
        timestamp viewed_at "indexed with restaurant_id"
        int time_spent "nullable, seconds"
        int page_views "default 1"
        int whatsapp_orders "default 0"
        tinyint via_qr "default 0"
        timestamp created_at
        timestamp updated_at
    }

    menu_scans {
        bigint id PK
        bigint restaurant_id FK "cascade delete"
        varchar status "pending, processing, completed, failed"
        varchar image_path "nullable"
        json result "nullable, AI-extracted menu"
        text error "nullable"
        timestamp created_at
        timestamp updated_at
    }

    templates |o--o{ restaurants : "styles (nullable)"
    restaurant_types |o--o{ restaurants : "classifies (nullable)"
    restaurants ||--o{ restaurant_tag : "tagged"
    tags ||--o{ restaurant_tag : ""
    templates ||--o{ template_tag : "recommended by"
    tags ||--o{ template_tag : ""
    restaurants ||--o{ restaurant_statistics : "tracks visits"
    restaurants ||--o{ menu_scans : "AI scans"
```

## 3. Security & framework tables

```mermaid
erDiagram
    blocked_ips {
        bigint id PK
        varchar ip "indexed"
        varchar reason "nullable"
        timestamp expires_at "nullable - null is permanent"
        timestamp created_at
        timestamp updated_at
    }

    contact_messages {
        bigint id PK
        varchar name
        varchar email
        text message
        varchar ip_address "indexed, max 45"
        timestamp created_at
        timestamp updated_at
    }

    sessions {
        varchar id PK
        bigint user_id "nullable, indexed, no FK to users"
        varchar ip_address "nullable, max 45"
        text user_agent "nullable"
        longtext payload
        int last_activity "indexed"
    }

    password_reset_tokens {
        varchar email PK
        varchar token
        timestamp created_at "nullable"
    }

    cache {
        varchar key PK
        mediumtext value
        int expiration
    }

    cache_locks {
        varchar key PK
        varchar owner
        int expiration
    }

    jobs {
        bigint id PK
        varchar queue "indexed"
        longtext payload
        tinyint attempts
        int reserved_at "nullable"
        int available_at
        int created_at
    }

    job_batches {
        varchar id PK
        varchar name
        int total_jobs
        int pending_jobs
        int failed_jobs
        longtext failed_job_ids
        mediumtext options "nullable"
        int cancelled_at "nullable"
        int created_at
        int finished_at "nullable"
    }

    failed_jobs {
        bigint id PK
        varchar uuid UK
        text connection
        text queue
        longtext payload
        longtext exception
        timestamp failed_at "default now"
    }

    migrations {
        int id PK
        varchar migration
        int batch
    }
```
