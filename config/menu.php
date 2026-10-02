<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Dish variants and add-ons
    |--------------------------------------------------------------------------
    |
    | How many a single dish can carry, whatever the package: enough for any
    | real menu, and small enough that the guest's sheet stays readable.
    | Whether a restaurant has them at all is the `variants` and `addons`
    | package flags (App\Enums\Feature).
    |
    */

    'dish_options' => [
        'variants' => 5,
        'options' => 10,
        'addons' => 20,
        'name_max' => 60,
        'price_max' => 99999.99,
    ],

];
