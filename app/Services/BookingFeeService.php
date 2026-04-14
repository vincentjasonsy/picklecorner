<?php

namespace App\Services;

final class BookingFeeService
{
    /**
     * Platform booking fee in major currency units (e.g. PHP), from court-booking subtotal only.
     */
    public static function calculate(float $subtotal): float
    {
        $setting = currentBookingFeeSetting();

        $fee = (float) $setting->base_fee + ($subtotal * (float) $setting->percentage_fee);

        if ($setting->max_fee !== null) {
            $fee = min($fee, (float) $setting->max_fee);
        }

        return round($fee, 2);
    }

    public static function calculateCentsFromCourtSubtotalCents(int $courtSubtotalCents): int
    {
        if ($courtSubtotalCents <= 0) {
            $subtotal = 0.0;
        } else {
            $subtotal = $courtSubtotalCents / 100.0;
        }

        return (int) round(self::calculate($subtotal) * 100);
    }
}
