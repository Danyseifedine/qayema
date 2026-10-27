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
        // As a dish's own price: a menu in Lebanese pounds runs to millions.
        'price_max' => 99999999.99,
    ],

    /*
    |--------------------------------------------------------------------------
    | Build your own
    |--------------------------------------------------------------------------
    |
    | A dish's "build your own" (App\Models\DishBuild): how many one dish may
    | carry, the most parts a guest may fill (a pizza in eighths), and the
    | most choices in a build's own list. A category's dishes are as many as
    | the category holds. On no package: an admin switches the
    | `build_your_own` flag on for a restaurant.
    |
    */

    'dish_builds' => [
        'builds' => 3,
        'parts' => 8,
        'choices' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Tables
    |--------------------------------------------------------------------------
    |
    | The most tables one restaurant can have, each with its own QR code for
    | ordering from the seat, and the most added in one go. Ordering at the
    | table is a package feature of its own (the `dine_in` flag).
    |
    */

    'tables' => [
        'max' => 300,
        'batch' => 100,
        'name_max' => 40,
    ],

];
