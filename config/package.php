<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Packages
    |--------------------------------------------------------------------------
    |
    | A restaurant's limits and features come from the package it is on, plus
    | any grants in `restaurant_features` that stack on top. The registry of
    | what a feature IS lives in App\Enums\Feature; the numbers live in the
    | `packages` table so they're editable from /admin → Packages without a
    | deploy. Templates are pure design and grant nothing.
    |
    */

    'default' => 'free',

    /*
    |--------------------------------------------------------------------------
    | Catalog
    |--------------------------------------------------------------------------
    |
    | TODO(packages): PLACEHOLDER contents. What each package includes is not
    | decided yet. This seeds a fresh database only — after that /admin →
    | Packages owns the numbers, and changing this file does not move them.
    | Keep the marketing copy in lang/{en,ar}/portal.php in step with it.
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
                'category_limit' => 10,
                'social_link_limit' => 2,
                'qr_studio' => 1,
                'ordering' => 1,
                'advanced_analytics' => 1,
            ],
        ],
        [
            'slug' => 'pro',
            'name' => ['en' => 'Pro', 'ar' => 'برو'],
            'description' => [
                'en' => 'More room on the menu, plus the QR studio.',
                'ar' => 'مساحة أكبر للقائمة، مع استوديو رمز QR.',
            ],
            'price_cents' => 1200,
            'is_contact_only' => false,
            'is_default' => false,
            'sort_order' => 1,
            'features' => [
                'dish_limit' => 120,
                'category_limit' => 25,
                'social_link_limit' => 6,
                'qr_studio' => 1,
                'ordering' => 1,
                'advanced_analytics' => 1,
            ],
        ],
        [
            'slug' => 'premium',
            'name' => ['en' => 'Premium', 'ar' => 'مميّز'],
            'description' => [
                'en' => 'For a large menu that changes often.',
                'ar' => 'لقائمة كبيرة تتغيّر باستمرار.',
            ],
            'price_cents' => 2900,
            'is_contact_only' => false,
            'is_default' => false,
            'sort_order' => 2,
            'features' => [
                'dish_limit' => 300,
                'category_limit' => 50,
                'social_link_limit' => 12,
                'qr_studio' => 1,
                'ordering' => 1,
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
                'qr_studio' => 1,
                'ordering' => 1,
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
