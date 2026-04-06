<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Demo registration
    |--------------------------------------------------------------------------
    |
    | When enabled, guests can create a time-limited demo account at /try.
    | The account and normal member data (bookings, saved sessions, etc.)
    | are removed after the TTL elapses. Set DEMO_REGISTRATION_ENABLED=false
    | in production if you do not want public demo signups.
    |
    */

    'registration_enabled' => env('DEMO_REGISTRATION_ENABLED', true),

    'ttl_hours' => max(1, (int) env('DEMO_TTL_HOURS', 24)),

];
