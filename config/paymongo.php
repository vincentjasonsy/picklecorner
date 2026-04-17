<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PayMongo (Philippines)
    |--------------------------------------------------------------------------
    |
    | Enables hosted checkout for venue bookings. Default methods: GCash and QRPh only.
    | Create keys at https://dashboard.paymongo.com — use test keys in non-production.
    |
    */

    'enabled' => (bool) env('PAYMONGO_ENABLED', false),

    'secret_key' => env('PAYMONGO_SECRET_KEY', ''),

    'webhook_secret' => env('PAYMONGO_WEBHOOK_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Checkout session defaults
    |--------------------------------------------------------------------------
    */

    'payment_method_types' => array_values(array_filter(array_map('trim', explode(',', (string) env(
        'PAYMONGO_PAYMENT_METHOD_TYPES',
        'gcash,qrph',
    ))))),

];
