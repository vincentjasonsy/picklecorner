<?php

namespace App\Services;

use App\Models\Court;
use App\Models\CourtTimeSlotSetting;

final class CourtSlotPricing
{
    /**
     * @return array{mode: string, cents: ?int, short_label: string}
     */
    public static function resolveForSlot(Court $court, int $dayOfWeek, int $slotStartHour): array
    {
        $court->loadMissing('courtClient');

        $setting = self::findSetting($court, $dayOfWeek, $slotStartHour);

        $mode = $setting?->mode ?? CourtTimeSlotSetting::MODE_NORMAL;

        if ($mode === CourtTimeSlotSetting::MODE_MANUAL) {
            $cents = $setting?->amount_cents;

            return [
                'mode' => $mode,
                'cents' => $cents,
                'short_label' => 'M',
            ];
        }

        if ($mode === CourtTimeSlotSetting::MODE_PEAK) {
            $cents = $court->effectivePeakHourlyRateCents();

            return [
                'mode' => $mode,
                'cents' => $cents,
                'short_label' => 'P',
            ];
        }

        $cents = $court->effectiveHourlyRateCents();

        return [
            'mode' => CourtTimeSlotSetting::MODE_NORMAL,
            'cents' => $cents,
            'short_label' => 'N',
        ];
    }

    /**
     * Hourly rate in stored minor units (PHP centavos) for a booking that starts at the given instant (venue timezone).
     */
    public static function estimatedHourlyCentsAtStart(Court $court, \DateTimeInterface $startsAt): ?int
    {
        $court->loadMissing(['courtClient', 'timeSlotSettings']);

        $tz = config('app.timezone', 'UTC');
        $local = \Carbon\Carbon::instance($startsAt instanceof \Carbon\Carbon ? $startsAt : \Carbon\Carbon::parse($startsAt))->timezone($tz);
        $day = (int) $local->dayOfWeek;
        $hour = (int) $local->format('G');

        $resolved = self::resolveForSlot($court, $day, $hour);

        return $resolved['cents']
            ?? $court->effectiveHourlyRateCents()
            ?? $court->courtClient?->hourly_rate_cents;
    }

    private static function findSetting(Court $court, int $dayOfWeek, int $slotStartHour): ?CourtTimeSlotSetting
    {
        if ($court->relationLoaded('timeSlotSettings')) {
            return $court->timeSlotSettings
                ->first(fn ($s) => $s->day_of_week === $dayOfWeek && $s->slot_start_hour === $slotStartHour);
        }

        return CourtTimeSlotSetting::query()
            ->where('court_id', $court->getKey())
            ->where('day_of_week', $dayOfWeek)
            ->where('slot_start_hour', $slotStartHour)
            ->first();
    }
}
