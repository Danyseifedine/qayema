<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default package limits
    |--------------------------------------------------------------------------
    |
    | The floor limits applied when a restaurant has no granting package now
    | live in the database (the `package_defaults` table, read via the
    | App\Models\PackageDefault model) so they're editable without a deploy.
    | Per-package values live in `template_feature`; purchased slots overlay via
    | `restaurant_features`.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Grace period
    |--------------------------------------------------------------------------
    |
    | Days after a failed renewal (status past_due) during which the
    | subscription still grants access before it expires.
    |
    */

    'grace_days' => 7,

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    */

    'cache_ttl' => 300,

];
