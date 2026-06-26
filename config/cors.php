<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Only the dashboard SPA endpoints and the CSRF-cookie route are exposed to
    | cross-origin requests. Because `supports_credentials` is true (cookies are
    | sent), `allowed_origins` MUST be an explicit allow-list — never "*". The
    | list is env-driven and fails closed: an empty CORS_ALLOWED_ORIGINS blocks
    | all cross-origin browser access rather than opening it up.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', ''))
    ))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
