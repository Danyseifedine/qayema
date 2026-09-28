<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SEO Default Settings
    |--------------------------------------------------------------------------
    |
    | Default SEO settings for the Qayema portal (by Lebify Group).
    |
    */

    'default_author' => 'Lebify Group',

    'title_separator' => '|',

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
            'en' => 'Lebify Group builds Qayema: bilingual digital menus for restaurants, shared as a QR code. Free to start, easy to use. Based in Lebanon.',
        ],
        'contact' => [
            '@type' => 'ContactPoint',
            'telephone' => '+96103004699',
            'email' => env('CONTACT_PUBLIC_EMAIL', 'dany.a.seifeddine@gmail.com'),
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
            'en' => 'Qayema by Lebify - Digital Menus for Restaurants',
        ],
        'description' => [
            'en' => 'Qayema by Lebify Group: bilingual digital menus for your restaurant, shared as a QR code. Free to start, easy to use. Built by the Lebify team in Lebanon.',
        ],
        'keywords' => [
            'en' => 'Qayema, Lebify, Lebify Group, Lebify team, digital menu, restaurant menu, online menu, menu creator, food menu, Lebanon, Barja',
        ],
        'image' => '/images/logo/logo.png',
    ],

];
