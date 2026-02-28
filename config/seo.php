<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SEO Default Settings - MenuX
    |--------------------------------------------------------------------------
    |
    | Default SEO settings for MenuX digital menu platform
    |
    */

    'default_author' => 'MenuX',

    'title_separator' => '|',

    'twitter_username' => env('TWITTER_USERNAME'),

    'facebook_app_id' => env('FACEBOOK_APP_ID'),

    /*
    |--------------------------------------------------------------------------
    | Organization Information (Schema.org)
    |--------------------------------------------------------------------------
    |
    | Used for generating Organization structured data
    |
    */

    'organization' => [
        'name' => 'MenuX',
        'url' => env('APP_URL', 'http://localhost'),
        'logo' => env('APP_URL', 'http://localhost').'/images/logo/logo.png',
        'description' => [
            'en' => 'MenuX - Create beautiful digital menus for your restaurant. Free up to 20 items, upgrade for more.',
        ],
        'contact' => [
            '@type' => 'ContactPoint',
            'telephone' => '+96103004699',
            'contactType' => 'Customer Service',
            'areaServed' => 'LB',
            'availableLanguage' => ['English', 'Arabic'],
        ],
        'social_links' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Meta Tags (Multi-language)
    |--------------------------------------------------------------------------
    */

    'defaults' => [
        'title' => [
            'en' => 'MenuX - Create Beautiful Digital Menus',
        ],
        'description' => [
            'en' => 'Create beautiful digital menus for your restaurant. Free up to 20 menu items. Share your menu with a simple link. Mobile optimized and easy to manage.',
        ],
        'keywords' => [
            'en' => 'digital menu, restaurant menu, online menu, menu creator, food menu, restaurant menu online, digital menu maker, menu sharing, restaurant technology',
        ],
        'image' => '/images/logo/logo.png',
    ],

    /*
    |--------------------------------------------------------------------------
    | Product-Specific Defaults (e.g. for dish/menu schema)
    |--------------------------------------------------------------------------
    */

    'product' => [
        'default_currency' => 'USD',
        'default_availability' => 'InStock',
    ],

];
