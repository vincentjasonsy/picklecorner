<?php

return [

    /*
    |--------------------------------------------------------------------------
    | GCash (checkout instructions)
    |--------------------------------------------------------------------------
    |
    | Shown on booking checkout flows when the user selects GCash, so they know
    | which number to send payment to. Set in .env as PAYMENTS_GCASH_NUMBER.
    |
    */

    'gcash_number' => env('PAYMENTS_GCASH_NUMBER', ''),

];
