<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Legal documents — versions & effective dates (YYYY-MM-DD)
    |--------------------------------------------------------------------------
    |
    | Bump version when material terms change. Used in page headers and for
    | customer transparency (including Philippines consumer-facing practice).
    |
    */
    'terms' => [
        'version' => env('LEGAL_TERMS_VERSION', '1.1'),
        'effective_date' => env('LEGAL_TERMS_EFFECTIVE_DATE', '2026-04-18'),
    ],

    'refund' => [
        'version' => env('LEGAL_REFUND_VERSION', '1.1'),
        'effective_date' => env('LEGAL_REFUND_EFFECTIVE_DATE', '2026-04-18'),
    ],

    'booking_cancellation' => [
        'version' => env('LEGAL_BOOKING_POLICY_VERSION', '1.0'),
        'effective_date' => env('LEGAL_BOOKING_POLICY_EFFECTIVE_DATE', '2026-04-17'),
    ],

];
