<?php

use App\Models\BookingFeeSetting;
use App\Models\User;

/**
 * Guest/member-facing ratings, review lists, and compose UI (controlled by BOOKING_PUBLIC_REVIEWS_ENABLED).
 */
function public_reviews_enabled(): bool
{
    return (bool) config('booking.public_reviews_enabled', false);
}

/**
 * Admin UI for venue subscription tier (gift cards / CRM tier). Super-admin-only when configured.
 */
function booking_gift_subscription_controls_visible(?User $user = null): bool
{
    if (! (bool) config('booking.gift_subscription_controls_super_admin_only', false)) {
        return true;
    }

    $user ??= auth()->user();

    return $user instanceof User && $user->isSuperAdmin();
}

/**
 * Active booking fee row, or an unsaved model using built-in defaults when none exist.
 */
function currentBookingFeeSetting(): BookingFeeSetting
{
    $active = BookingFeeSetting::query()->where('is_active', true)->first();
    if ($active !== null) {
        return $active;
    }

    return new BookingFeeSetting([
        'base_fee' => BookingFeeSetting::DEFAULT_BASE_FEE,
        'percentage_fee' => BookingFeeSetting::DEFAULT_PERCENTAGE_FEE,
        'max_fee' => BookingFeeSetting::DEFAULT_MAX_FEE,
        'is_active' => false,
        'fee_basis' => BookingFeeSetting::FEE_BASIS_SUBTOTAL,
        'per_court_hour_mode' => BookingFeeSetting::PER_COURT_HOUR_FIXED,
        'per_court_hour_fixed' => BookingFeeSetting::DEFAULT_PER_COURT_HOUR_FIXED,
        'per_court_hour_percent' => BookingFeeSetting::DEFAULT_PER_COURT_HOUR_PERCENT,
    ]);
}
