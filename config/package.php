<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Packages
    |--------------------------------------------------------------------------
    |
    | A restaurant's limits and features come from the package it is on, plus
    | any grants in `feature_grants` that stack on top. The registry of
    | what a feature IS lives in App\Enums\Feature; the numbers live in the
    | `packages` table so they're editable from /admin → Packages without a
    | deploy. A template grants nothing; one marked premium needs the
    | premium_designs flag.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Catalog
    |--------------------------------------------------------------------------
    |
    | What each package includes, as decided with the owner (2026-09). Prices
    | are still placeholders. This seeds a fresh database only; after that
    | /admin → Packages owns the numbers, and changing this file does not move
    | them. The landing page's pricing reads the table too
    | (App\Services\Portal\PricingCards).
    |
    | A feature value of null means unlimited. A flag is 0 or 1. A key left out
    | falls back to App\Enums\Feature::defaultValue().
    |
    */

    'catalog' => [
        [
            'slug' => 'free',
            'name' => ['en' => 'Free', 'ar' => 'مجاني'],
            'description' => [
                'en' => 'Everything you need to take one menu live.',
                'ar' => 'كل ما تحتاجه لإطلاق قائمة واحدة.',
            ],
            'price_cents' => 0,
            'is_contact_only' => false,
            'is_default' => true,
            'sort_order' => 0,
            'features' => [
                'dish_limit' => 40,
                'category_limit' => 8,
                'social_link_limit' => 1,
                'multiple_languages' => 0,
                'appearance' => 0,
                'premium_designs' => 0,
                'qr_studio' => 0,
                'ordering' => 0,
                'analytics' => 0,
                'advanced_analytics' => 0,
            ],
        ],
        [
            'slug' => 'pro',
            'name' => ['en' => 'Pro', 'ar' => 'برو'],
            'description' => [
                'en' => 'Your own look, two languages and your numbers.',
                'ar' => 'مظهرك الخاص ولغتان وأرقامك.',
            ],
            'price_cents' => 1200,
            'is_contact_only' => false,
            'is_default' => false,
            'sort_order' => 1,
            'features' => [
                'dish_limit' => 150,
                'category_limit' => 15,
                'social_link_limit' => 2,
                'multiple_languages' => 1,
                'appearance' => 1,
                'premium_designs' => 0,
                'qr_studio' => 0,
                'ordering' => 0,
                'analytics' => 1,
                'advanced_analytics' => 0,
            ],
        ],
        [
            'slug' => 'premium',
            'name' => ['en' => 'Premium', 'ar' => 'مميّز'],
            'description' => [
                'en' => 'Everything, with ordering and the QR studio.',
                'ar' => 'كل شيء، مع الطلبات واستوديو QR.',
            ],
            'price_cents' => 2900,
            'is_contact_only' => false,
            'is_default' => false,
            'sort_order' => 2,
            'is_featured' => true,
            'features' => [
                'dish_limit' => 500,
                'category_limit' => 30,
                'social_link_limit' => 10,
                'multiple_languages' => 1,
                'appearance' => 1,
                'premium_designs' => 1,
                'qr_studio' => 1,
                'ordering' => 1,
                'analytics' => 1,
                'advanced_analytics' => 1,
            ],
        ],
        [
            'slug' => 'custom',
            'name' => ['en' => 'Custom', 'ar' => 'مخصّص'],
            'description' => [
                'en' => 'Built around what your group needs. Talk to us.',
                'ar' => 'مصمّم حسب احتياجات مجموعتك. تواصل معنا.',
            ],
            'price_cents' => null,
            'is_contact_only' => true,
            'is_default' => false,
            'sort_order' => 3,
            'features' => [
                'dish_limit' => null,
                'category_limit' => null,
                'social_link_limit' => null,
                'multiple_languages' => 1,
                'appearance' => 1,
                'premium_designs' => 1,
                'qr_studio' => 1,
                'ordering' => 1,
                'analytics' => 1,
                'advanced_analytics' => 1,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    */

    'cache_ttl' => 300,

];
