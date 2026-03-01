<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SEO Default Settings - MenuX by Lebify
    |--------------------------------------------------------------------------
    |
    | Default SEO settings for MenuX digital menu platform (by Lebify Group).
    |
    */

    'default_author' => 'Lebify Group',

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
        'name' => 'Lebify Group',
        'url' => env('APP_URL', 'http://localhost'),
        'logo' => env('APP_URL', 'http://localhost').'/images/logo/logo.png',
        'description' => [
            'en' => 'Lebify Group builds MenuX: create beautiful digital menus for your restaurant. Free to start, easy to use. Based in Lebanon.',
        ],
        'contact' => [
            '@type' => 'ContactPoint',
            'telephone' => '+96103004699',
            'email' => 'dany.a.seifeddine@gmail.com',
            'contactType' => 'Customer Service',
            'areaServed' => 'LB',
            'address' => [
                '@type' => 'PostalAddress',
                'addressLocality' => 'Barja',
                'addressCountry' => 'Lebanon',
            ],
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
            'en' => 'MenuX by Lebify - Create Beautiful Digital Menus',
        ],
        'description' => [
            'en' => 'MenuX by Lebify Group: create beautiful digital menus for your restaurant. Free to start, easy to use. Built by the Lebify team in Lebanon.',
        ],
        'keywords' => [
            'en' => 'MenuX, Lebify, Lebify Group, Lebify team, digital menu, restaurant menu, online menu, menu creator, food menu, Lebanon, Barja',
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
