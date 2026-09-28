<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SEO Default Settings
    |--------------------------------------------------------------------------
    |
    | The portal's <head> tags (App\View\Components\Seo), for Qayema by
    | Lebify Group. Each page names its own title and description.
    |
    */

    'default_author' => 'Lebify Group',

    'title_separator' => '|',

    'keywords' => 'Qayema, Lebify, Lebify Group, Lebify team, digital menu, restaurant menu, online menu, menu creator, food menu, Lebanon, Barja',

    // Optional: a Twitter/X handle for the twitter:site and twitter:creator tags.
    'twitter_username' => env('TWITTER_USERNAME'),

    'facebook_app_id' => env('FACEBOOK_APP_ID'),

    /*
    |--------------------------------------------------------------------------
    | Organization Information (Schema.org)
    |--------------------------------------------------------------------------
    |
    | The landing page's Organization structured data, and the site name in
    | every page's title.
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

];
