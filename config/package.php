<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Feature defaults
    |--------------------------------------------------------------------------
    |
    | The floor limits applied to every restaurant live in the database (the
    | `feature_defaults` table, read via App\Models\FeatureDefault) so they're
    | editable from the admin panel without a deploy. Grants in
    | `restaurant_features` stack on top. The registry of what a feature IS lives
    | in App\Enums\Feature. Templates are pure design and grant nothing.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    */

    'cache_ttl' => 300,

];
