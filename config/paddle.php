<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Purchasable feature catalog
    |--------------------------------------------------------------------------
    |
    | Maps each purchasable add-on to its Paddle prices and the package feature
    | it grants. This is the source of truth: the checkout endpoint resolves a
    | cart id to a Paddle price here, and the webhook resolves a paid price back
    | to the feature slug + step it should grant. The SPA catalog only mirrors it
    | for display. Keyed by a stable cart id.
    |
    | Price ids are public identifiers (they ride along in the browser checkout),
    | so they live here directly — add a new entry per product, no env needed.
    | The active id is picked by environment: `sandbox` when PADDLE_SANDBOX=true,
    | `production` otherwise. An entry whose active id is empty is not sellable
    | (hidden from checkout) until you fill it in.
    |
    | - price_id: the Paddle price per environment.
    | - slug:     the App\Models\Feature slug the purchase grants.
    | - kind:     "limit" grants the purchased amount; "boolean" enables it.
    | - step:     limit units per "pack" the owner buys, and the Paddle price's
    |             minimum quantity. The Paddle price is priced per unit (per dish),
    |             so the checkout multiplies the pack count by this to land on the
    |             unit quantity Paddle expects.
    | - max:      the Paddle price's maximum quantity (guards checkout up-front).
    |
    */

    'catalog' => [

        'dish' => [
            'price_id' => [
                'sandbox' => 'pri_01kx8va4ewcth8shdyjdab48jh',
                'production' => null,
            ],
            'slug' => 'dish_limit',
            'kind' => 'limit',
            'step' => 50,
            'max' => 1000,
        ],

        'social' => [
            'price_id' => [
                'sandbox' => 'pri_01kxbq0t0tcytsf2mtn12k41dt',
                'production' => null,
            ],
            'slug' => 'social_link_limit',
            'kind' => 'limit',
            'step' => 2,
            'max' => 2,
        ],

        'qr' => [
            'price_id' => [
                'sandbox' => 'pri_01kxc3vk9c27xnsbmhg2mthb90',
                'production' => null,
            ],
            'slug' => 'qr_studio',
            'kind' => 'boolean',
            'step' => 1,
            'max' => 1,
        ],

    ],

];
