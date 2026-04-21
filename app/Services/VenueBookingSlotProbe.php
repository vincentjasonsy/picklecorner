<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtClient;
use App\Models\CourtDateSlotBlock;
use App\Models\VenueWeeklyHour;
use App\Support\VenueScheduleHours;
use Carbon\Carbon;

/**
 * Mirrors public venue grid rules so Book Again / deep links can pre-select valid slots.
 */
final class VenueBookingSlotProbe
{
    /**
     * Whether the player can reserve every listed hour slot on one court on this calendar date.
     *
     * @param  list<int>  $sortedUniqueHours  contiguous hourly start hours (24h), e.g. [18, 19].
     */
    public static function canSelectSlots(CourtClient $courtClient, string $dateYmd, string $courtId, array $sortedUniqueHours): bool
    {
        if ($sortedUniqueHours === []) {
            return false;
        }

        sort($sortedUniqueHours);
        if (! VenueScheduleHours::areContiguous($sortedUniqueHours)) {
            return false;
        }

        $courtClient->loadMissing(['weeklyHours']);

        if ($courtClient->isClosedOnDate($dateYmd)) {
            return false;
        }

        $court = Court::query()
            ->where('id', $courtId)
            ->where('court_client_id', $courtClient->id)
            ->where('is_available', true)
            ->first();

        if ($court === null) {
            return false;
        }

        $scheduleRows = self::scheduleRowsFromClient($courtClient);

        $allowed = VenueBookingSpecsBuilder::slotHoursForSelectedDate($courtClient, $scheduleRows, $dateYmd);
        if ($allowed === []) {
            return false;
        }

        $tz = config('app.timezone', 'UTC');
        $dow = (int) Carbon::parse($dateYmd.' 12:00:00', $tz)->format('w');

        foreach ($sortedUniqueHours as $h) {
            if (! in_array($h, $allowed, true)) {
                return false;
            }
            if ($court->isWeeklySlotBlocked($dow, $h)) {
                return false;
            }
        }

        $blockedHours = CourtDateSlotBlock::query()
            ->where('court_id', $courtId)
            ->whereDate('blocked_date', $dateYmd)
            ->pluck('slot_start_hour');

        foreach ($sortedUniqueHours as $h) {
            if ($blockedHours->contains($h)) {
                return false;
            }
        }

        foreach ($sortedUniqueHours as $h) {
            $slotStart = Carbon::parse($dateYmd.' '.sprintf('%02d:00:00', $h), $tz);
            $slotEnd = $slotStart->copy()->addHour();

            $occupied = Booking::query()
                ->where('court_id', $courtId)
                ->whereIn('status', Booking::statusesBlockingCourtAvailability())
                ->where('starts_at', '<', $slotEnd)
                ->where('ends_at', '>', $slotStart)
                ->exists();

            if ($occupied) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<array{id: string, day_of_week: int, is_closed: bool, opens_at: string, closes_at: string}>
     */
    protected static function scheduleRowsFromClient(CourtClient $courtClient): array
    {
        return VenueWeeklyHour::query()
            ->where('court_client_id', $courtClient->id)
            ->orderBy('day_of_week')
            ->get()
            ->map(static fn (VenueWeeklyHour $r) => [
                'day_of_week' => (int) $r->day_of_week,
                'is_closed' => (bool) $r->is_closed,
                'opens_at' => $r->opens_at ?? '09:00',
                'closes_at' => $r->closes_at ?? '21:00',
            ])
            ->values()
            ->all();
    }
}
