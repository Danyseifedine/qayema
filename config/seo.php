<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Search and sharing
    |--------------------------------------------------------------------------
    |
    | The public pages' <head> tags (App\View\Components\Seo) and structured
    | data (App\Services\Portal\StructuredData). Each page names its own
    | title and description, in its language; the product is Qayema, made by
    | Lebify Group.
    |
    */

    'site_name' => 'Qayema',

    'title_separator' => '|',

    // The image a link shows when shared (WhatsApp, Facebook, X), 1200 x 630,
    // one per language. Made from resources/og/card.html.
    'images' => [
        'en' => 'images/og/qayema-en.jpg',
        'ar' => 'images/og/qayema-ar.jpg',
    ],

    // Open Graph wants a territory with the language.
    'og_locales' => [
        'en' => 'en_US',
        'ar' => 'ar_AR',
    ],

    // Optional: a Twitter/X handle for the twitter:site and twitter:creator tags.
    'twitter_username' => env('TWITTER_USERNAME'),

    'facebook_app_id' => env('FACEBOOK_APP_ID'),

    /*
    |--------------------------------------------------------------------------
    | The company (schema.org Organization)
    |--------------------------------------------------------------------------
    */

    'organization' => [
        'name' => 'Lebify Group',
        'contact' => [
            'telephone' => '+96103004699',
            'email' => env('CONTACT_PUBLIC_EMAIL', 'dany.a.seifeddine@gmail.com'),
        ],
        'address' => [
            'locality' => 'Barja',
            'country' => 'LB',
        ],
        // Lebanon first, and the Arab countries the Arabic pages speak to.
        'area_served' => ['LB', 'SA', 'AE', 'KW', 'QA', 'BH', 'OM', 'JO', 'IQ', 'SY', 'EG'],
        // Profiles that are the company's own, as full URLs, when there are any.
        'social_links' => [],
    ],

];
