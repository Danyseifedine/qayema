<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Broadcasting
    |--------------------------------------------------------------------------
    |
    | Orders placed in the menu reach the owner's dashboard, and their status
    | reaches the guest following them, through Pusher: the live connection
    | is Pusher's, so shared hosting only makes a short HTTPS call when
    | something changes (App\Events\OrdersChanged, OrderMoved). Without keys
    | (local, tests, the e2e suite) nothing is sent and the pages check once
    | a minute instead.
    |
    */

    'default' => env('BROADCAST_CONNECTION', 'null'),

    'connections' => [

        'pusher' => [
            'driver' => 'pusher',
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'app_id' => env('PUSHER_APP_ID'),
            'options' => [
                'cluster' => env('PUSHER_APP_CLUSTER'),
                'host' => 'api-'.env('PUSHER_APP_CLUSTER', 'mt1').'.pusher.com',
                'port' => 443,
                'scheme' => 'https',
                'encrypted' => true,
                'useTLS' => true,
            ],
            // A slow Pusher must never hold up a guest's order.
            'client_options' => [
                'connect_timeout' => 2,
                'timeout' => 3,
            ],
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],

    ],

];
