<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default entitlements
    |--------------------------------------------------------------------------
    |
    | Floor values used when a restaurant has no explicit entitlement for a
    | limit (no template assigned yet, mid-onboarding, or an unseeded
    | database). Boolean features default to false unless granted.
    |
    */

    'defaults' => [
        'dish_limit' => 40,
        'category_limit' => 10,
        'social_link_limit' => 4,
    ],

    /*
    |--------------------------------------------------------------------------
    | Grace period
    |--------------------------------------------------------------------------
    |
    | Days after a failed renewal (status past_due) during which the
    | subscription still counts as entitling before it expires.
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
