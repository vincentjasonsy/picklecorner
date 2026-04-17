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

    /*
    |--------------------------------------------------------------------------
    | Quick login (seeded demo users)
    |--------------------------------------------------------------------------
    |
    | When enabled, the login and /try pages show one-click sign-in as the
    | seeded demo accounts (superadmin@, player@, openplayhost@, courtadmin@, desk@, coach@picklecorner.ph).
    | Requires migrate --seed. Set DEMO_QUICK_LOGIN_ENABLED=false in production.
    |
    */

    'quick_login_enabled' => filter_var(env('DEMO_QUICK_LOGIN_ENABLED', env('DEMO_REGISTRATION_ENABLED', true)), FILTER_VALIDATE_BOOLEAN),

];
