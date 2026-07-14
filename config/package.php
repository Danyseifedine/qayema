<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default package limits
    |--------------------------------------------------------------------------
    |
    | The floor limits applied to every restaurant live in the database (the
    | `package_defaults` table, read via the App\Models\PackageDefault model) so
    | they're editable without a deploy. Each restaurant snapshots them as its own
    | `restaurant_features` grants on creation; purchased slots overlay the same
    | table. Templates are pure design and carry no features.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    */

    'cache_ttl' => 300,

];
