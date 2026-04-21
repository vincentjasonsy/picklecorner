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
    | Public “write a review” form (member-facing pages)
    |--------------------------------------------------------------------------
    |
    | When false, venue/court pages and signed email links hide the compose form.
    | Approved reviews remain visible; submitting via Livewire is blocked.
    |
    */

    'public_review_form_enabled' => (bool) env('BOOKING_PUBLIC_REVIEW_FORM_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Public ratings & reviews (browse, courts, venues)
    |--------------------------------------------------------------------------
    |
    | When false, star ratings and member review panels stay hidden everywhere
    | on member and guest surfaces (including landing quotes). Site navigation
    | skips review anchors. Internal admin moderation is unchanged.
    |
    */

    'public_reviews_enabled' => (bool) env('BOOKING_PUBLIC_REVIEWS_ENABLED', false),

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

    /*
    |--------------------------------------------------------------------------
    | New / upcoming court emails (members)
    |--------------------------------------------------------------------------
    |
    | When enabled, eligible members (same home city as the venue, marketing
    | emails opted in, player-type accounts) receive one email when a partner
    | court goes live or is scheduled to open (see opens_at on courts).
    |
    */

    'court_opening_emails' => (bool) env('BOOKING_COURT_OPENING_EMAILS', true),

    /*
    |--------------------------------------------------------------------------
    | Gift subscription controls (Basic / Premium) — super admin only
    |--------------------------------------------------------------------------
    |
    | When true, Subscription tier fields that mention gift cards / CRM are
    | shown only to super admins on admin venue edit and admin user edit.
    |
    */

    'gift_subscription_controls_super_admin_only' => (bool) env(
        'BOOKING_GIFT_SUBSCRIPTION_CONTROLS_SUPER_ADMIN_ONLY',
        false,
    ),

    /*
    |--------------------------------------------------------------------------
    | Gift card coach & venue portals (non–super-admin)
    |--------------------------------------------------------------------------
    |
    | When true, coaches and venue admins can use gift card management routes
    | and navigation (subject to venue Premium tier for venue routes). When false,
    | only super_admin users may access those coach and venue gift card pages;
    | platform admin gift cards under /admin remain available to super admins.
    |
    */

    'gift_card_module_for_non_super_admins' => (bool) env(
        'BOOKING_GIFT_CARD_MODULE_FOR_NON_SUPER_ADMINS',
        true,
    ),

];
