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

    /*
    |--------------------------------------------------------------------------
    | Public review window (after booking ends)
    |--------------------------------------------------------------------------
    |
    | Members may submit or update a pending venue/coach review only when they
    | have a confirmed or completed booking whose end time is in the past, and
    | whose end time was not more than this many days ago.
    |
    */

    'review_window_days_after_booking_ends' => (int) env('BOOKING_REVIEW_WINDOW_DAYS', 2),

    /*
    |--------------------------------------------------------------------------
    | Signed “leave a review” link (email)
    |--------------------------------------------------------------------------
    |
    | How long signed URLs from {@see \App\Support\UserReviewMailLink} remain valid.
    | Should cover the post-booking review window plus buffer.
    |
    */

    'review_mail_link_ttl_days' => (int) env('BOOKING_REVIEW_MAIL_LINK_TTL_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Member account “book soon” popup (My Corner)
    |--------------------------------------------------------------------------
    |
    | Shown on member pages when the user has no upcoming booking as booker and
    | has not booked for at least member_booking_nudge_after_days (or registered
    | long enough with no bookings yet). Dismissal is cached per user for
    | member_booking_nudge_dismiss_hours.
    |
    */

    'member_booking_nudge_after_days' => (int) env('MEMBER_BOOKING_NUDGE_AFTER_DAYS', 10),

    'member_booking_nudge_never_booked_after_days' => (int) env('MEMBER_BOOKING_NUDGE_NEVER_BOOKED_DAYS', 2),

    'member_booking_nudge_dismiss_hours' => (int) env('MEMBER_BOOKING_NUDGE_DISMISS_HOURS', 72),

];
