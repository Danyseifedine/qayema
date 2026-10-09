<?php

/*
|--------------------------------------------------------------------------
| Media library
|--------------------------------------------------------------------------
|
| Only what differs from spatie/laravel-medialibrary's own config, which is
| merged under this. Everything else (the disk, MEDIA_DISK) stays the
| package's.
|
*/

return [

    /*
     * Sent with every upload to the media disk (R2). An uploaded photo is
     * never written over: a new photo gets a new address. So a phone and the
     * CDN can keep each one for a year without asking again. The package's
     * default was a week.
     */
    'remote' => [
        'extra_headers' => [
            'CacheControl' => 'public, max-age=31536000, immutable',
        ],
    ],

    /*
     * Every image address ends with ?v=<when the record last changed>. A
     * small version remade in place (a new crop for the cards) keeps its
     * path, and is kept a year by phones and Cloudflare; the new ?v= is what
     * makes them fetch it. Applies wherever an address is built: menus,
     * search data, the dashboard and the admin.
     */
    'version_urls' => true,

];
