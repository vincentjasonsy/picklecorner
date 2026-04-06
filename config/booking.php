<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Show coach on public venue checkout (review step)
    |--------------------------------------------------------------------------
    |
    | When true, members can optionally add a coach to the same booking request
    | on the venue book/checkout page. When false, the coach section is hidden and
    | coach fields are ignored on submit.
    |
    */

    'venue_checkout_show_coach' => env('VENUE_BOOKING_CHECKOUT_SHOW_COACH', false),

];
