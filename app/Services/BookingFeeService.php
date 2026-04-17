<?php

namespace App\Services;

use App\Models\BookingFeeSetting;
use App\Models\Court;
use Carbon\Carbon;

final class BookingFeeService
{
    /**
     * Platform booking fee in major currency units (e.g. PHP). Only valid when the active rate uses
     * {@see BookingFeeSetting::FEE_BASIS_SUBTOTAL}; otherwise returns 0 (use {@see calculateCentsForSpecs}).
     */
    public static function calculate(float $subtotal): float
    {
        $setting = currentBookingFeeSetting();
        if (($setting->fee_basis ?? BookingFeeSetting::FEE_BASIS_SUBTOTAL) !== BookingFeeSetting::FEE_BASIS_SUBTOTAL) {
            return 0.0;
        }

        return self::feeMajorFromCourtSubtotal($setting, $subtotal);
    }

    /**
     * Same as {@see calculate} but from court subtotal cents. Use {@see calculateCentsForSpecs} when the
     * active rate is per court hour.
     */
    public static function calculateCentsFromCourtSubtotalCents(int $courtSubtotalCents): int
    {
        $setting = currentBookingFeeSetting();
        if (($setting->fee_basis ?? BookingFeeSetting::FEE_BASIS_SUBTOTAL) !== BookingFeeSetting::FEE_BASIS_SUBTOTAL) {
            throw new \LogicException(
                'Active booking fee uses per-court-hour pricing; use BookingFeeService::calculateCentsForSpecs().',
            );
        }

        if ($courtSubtotalCents <= 0) {
            $subtotal = 0.0;
        } else {
            $subtotal = $courtSubtotalCents / 100.0;
        }

        return (int) round(self::feeMajorFromCourtSubtotal($setting, $subtotal) * 100);
    }

    /**
     * Member checkout / PayMongo: full fee from booking line specs (required for per-court-hour rates).
     *
     * @param  list<array{court: Court, starts: Carbon, ends: Carbon, gross_cents: int, court_gross_cents?: int, hours: list<int>, coach_fee_cents?: int}>  $specs
     */
    public static function calculateCentsForSpecs(array $specs): int
    {
        if ($specs === []) {
            return 0;
        }

        $setting = currentBookingFeeSetting();
        $basis = $setting->fee_basis ?? BookingFeeSetting::FEE_BASIS_SUBTOTAL;

        if ($basis === BookingFeeSetting::FEE_BASIS_PER_COURT_HOUR) {
            return self::calculatePerCourtHourCents($setting, $specs);
        }

        $courtSubtotalCents = (int) array_sum(array_column($specs, 'court_gross_cents'));

        return self::calculateCentsFromCourtSubtotalCentsWithSetting($setting, $courtSubtotalCents);
    }

    /**
     * @param  list<array{court: Court, starts: Carbon, ends: Carbon, gross_cents: int, court_gross_cents?: int, hours: list<int>, coach_fee_cents?: int}>  $specs
     */
    protected static function calculatePerCourtHourCents(BookingFeeSetting $setting, array $specs): int
    {
        $mode = $setting->per_court_hour_mode ?? BookingFeeSetting::PER_COURT_HOUR_FIXED;
        $tz = config('app.timezone', 'UTC');

        $feeCents = 0;

        if ($mode === BookingFeeSetting::PER_COURT_HOUR_PERCENT) {
            $pct = (float) ($setting->per_court_hour_percent ?? BookingFeeSetting::DEFAULT_PER_COURT_HOUR_PERCENT);
            foreach ($specs as $spec) {
                /** @var Court $court */
                $court = $spec['court'];
                $dateYmd = $spec['starts']->copy()->timezone($tz)->format('Y-m-d');
                foreach ($spec['hours'] as $h) {
                    $slotStart = Carbon::parse($dateYmd.' '.sprintf('%02d:00:00', $h), $tz);
                    $hourly = CourtSlotPricing::estimatedHourlyCentsAtStart($court, $slotStart);
                    if ($hourly === null) {
                        $hourly = (int) ($court->courtClient?->hourly_rate_cents ?? 0);
                    }
                    $feeCents += (int) round($hourly * $pct);
                }
            }
        } else {
            $perHourMajor = (float) ($setting->per_court_hour_fixed ?? BookingFeeSetting::DEFAULT_PER_COURT_HOUR_FIXED);
            $perHourCents = (int) round($perHourMajor * 100);
            foreach ($specs as $spec) {
                $n = count($spec['hours']);
                $feeCents += $perHourCents * $n;
            }
        }

        $feeCents = self::applyMaxFeeCentsCap($feeCents, $setting);

        return max(0, $feeCents);
    }

    protected static function feeMajorFromCourtSubtotal(BookingFeeSetting $setting, float $subtotal): float
    {
        $fee = (float) $setting->base_fee + ($subtotal * (float) $setting->percentage_fee);

        $fee = self::applyMaxFeeMajorCap($fee, $setting);

        return round($fee, 2);
    }

    protected static function calculateCentsFromCourtSubtotalCentsWithSetting(BookingFeeSetting $setting, int $courtSubtotalCents): int
    {
        if ($courtSubtotalCents <= 0) {
            $subtotal = 0.0;
        } else {
            $subtotal = $courtSubtotalCents / 100.0;
        }

        return (int) round(self::feeMajorFromCourtSubtotal($setting, $subtotal) * 100);
    }

    /**
     * Empty max means no cap. A stored max of 0 previously behaved like a ₱0 cap and wiped fees; treat non-positive caps as absent.
     */
    protected static function effectiveMaxFeeMajor(?string $decimal): ?float
    {
        if ($decimal === null) {
            return null;
        }

        $m = (float) $decimal;

        return $m > 0 ? $m : null;
    }

    protected static function applyMaxFeeMajorCap(float $fee, BookingFeeSetting $setting): float
    {
        $cap = self::effectiveMaxFeeMajor($setting->max_fee !== null ? (string) $setting->max_fee : null);
        if ($cap === null) {
            return $fee;
        }

        return min($fee, $cap);
    }

    protected static function applyMaxFeeCentsCap(int $feeCents, BookingFeeSetting $setting): int
    {
        $capMajor = self::effectiveMaxFeeMajor($setting->max_fee !== null ? (string) $setting->max_fee : null);
        if ($capMajor === null) {
            return $feeCents;
        }

        return min($feeCents, (int) round($capMajor * 100));
    }
}
