<?php

use App\Models\BookingFeeSetting;

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
