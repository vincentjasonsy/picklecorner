<?php

namespace App\Services;

use App\Livewire\BookNow\VenueBookingPage;
use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtClient;
use App\Models\User;
use App\Support\VenueScheduleHours;
use Carbon\Carbon;

/**
 * Builds the same booking line specs as {@see VenueBookingPage::buildSpecsForSubmit()}
 * for server-side replay (e.g. PayMongo webhook after payment).
 */
final class VenueBookingSpecsBuilder
{
    /**
     * @param  list<array{id: string, day_of_week: int, is_closed: bool, opens_at: string, closes_at: string}>  $scheduleRows
     * @param  list<string>  $selectedSlots
     * @return list<array{court: Court, starts: Carbon, ends: Carbon, gross_cents: int, court_gross_cents: int, hours: list<int>, coach_fee_cents: int}>
     */
    public static function buildSpecsForSubmit(
        CourtClient $courtClient,
        array $scheduleRows,
        string $bookingCalendarDate,
        array $selectedSlots,
        string $coachUserId,
        int $coachPaidHours,
        bool $venueCheckoutShowCoach,
    ): array {
        $byCourt = self::selectedSlotsGroupedByCourt($selectedSlots);
        $allowed = self::slotHoursForSelectedDate($courtClient, $scheduleRows, $bookingCalendarDate);
        $date = self::normalizedBookingCalendarDate($bookingCalendarDate);
        if ($date === null) {
            return [];
        }

        $effectiveCoachId = $venueCheckoutShowCoach ? $coachUserId : '';

        $coachUser = null;
        if ($effectiveCoachId !== '') {
            $coachUser = User::query()->with('coachProfile')->find($effectiveCoachId);
            if (! $coachUser?->isCoach()) {
                return [];
            }
            $windows = self::selectedTimeWindows($selectedSlots, $date);
            $availableCoaches = CoachAvailabilityService::availableCoaches(
                $courtClient,
                $date,
                $selectedSlots,
                $windows,
            );
            if (! $availableCoaches->contains('id', $effectiveCoachId)) {
                return [];
            }
        }

        $tz = config('app.timezone', 'UTC');
        $specs = [];

        $rate = (int) ($coachUser?->coachProfile?->hourly_rate_cents ?? 0);
        $maxBillable = self::totalSelectedSlotHours($selectedSlots);
        $paidHours = $coachUser !== null
            ? min($maxBillable, max(0, $coachPaidHours))
            : 0;
        $coachTotalFee = $coachUser !== null ? $rate * $paidHours : 0;
        $coachFeeRemaining = $coachTotalFee;

        foreach ($byCourt as $courtId => $hours) {
            $court = Court::query()
                ->with(['courtClient', 'timeSlotSettings'])
                ->where('id', $courtId)
                ->where('court_client_id', $courtClient->id)
                ->first();
            if (! $court) {
                return [];
            }

            foreach ($hours as $h) {
                if (! in_array($h, $allowed, true)) {
                    return [];
                }
            }

            foreach (self::contiguousHourRuns($hours) as $run) {
                if ($run === []) {
                    continue;
                }
                $firstHour = $run[0];
                $lastHour = $run[count($run) - 1];
                $starts = Carbon::parse($date.' '.sprintf('%02d:00:00', $firstHour), $tz);
                $ends = Carbon::parse($date.' '.sprintf('%02d:00:00', $lastHour), $tz)->addHour();

                if (self::bookingOverlapsCourt($court->id, $starts, $ends)) {
                    return [];
                }

                if ($coachUser !== null) {
                    if (CoachAvailabilityService::coachHasOverlappingBooking((string) $coachUser->id, $starts, $ends)) {
                        return [];
                    }
                }

                $courtGross = 0;
                foreach ($run as $h) {
                    $slotStart = Carbon::parse($date.' '.sprintf('%02d:00:00', $h), $tz);
                    $hourly = CourtSlotPricing::estimatedHourlyCentsAtStart($court, $slotStart)
                        ?? $court->courtClient?->hourly_rate_cents
                        ?? 0;
                    $courtGross += $hourly;
                }
                $courtGross = (int) round($courtGross);

                $coachFeeThisSpec = 0;
                if ($coachFeeRemaining > 0) {
                    $coachFeeThisSpec = $coachFeeRemaining;
                    $coachFeeRemaining = 0;
                }

                $specs[] = [
                    'court' => $court,
                    'starts' => $starts,
                    'ends' => $ends,
                    'gross_cents' => $courtGross + $coachFeeThisSpec,
                    'court_gross_cents' => $courtGross,
                    'hours' => $run,
                    'coach_fee_cents' => $coachFeeThisSpec,
                ];
            }
        }

        return $specs;
    }

    /**
     * @param  list<string>  $selectedSlots
     * @return list<array{starts: Carbon, ends: Carbon}>
     */
    public static function selectedTimeWindows(array $selectedSlots, string $dateYmd): array
    {
        $byCourt = self::selectedSlotsGroupedByCourt($selectedSlots);
        $tz = config('app.timezone', 'UTC');
        $windows = [];
        foreach ($byCourt as $hours) {
            foreach (self::contiguousHourRuns($hours) as $run) {
                if ($run === []) {
                    continue;
                }
                $firstHour = $run[0];
                $lastHour = $run[count($run) - 1];
                $starts = Carbon::parse($dateYmd.' '.sprintf('%02d:00:00', $firstHour), $tz);
                $ends = Carbon::parse($dateYmd.' '.sprintf('%02d:00:00', $lastHour), $tz)->addHour();
                $windows[] = ['starts' => $starts, 'ends' => $ends];
            }
        }

        return $windows;
    }

    /**
     * @param  list<string>  $selectedSlots
     * @return array<string, list<int>>
     */
    public static function selectedSlotsGroupedByCourt(array $selectedSlots): array
    {
        $by = [];
        foreach ($selectedSlots as $key) {
            if (! preg_match('/^(.*)-(\d+)$/', $key, $m)) {
                continue;
            }
            $cid = $m[1];
            $h = (int) $m[2];
            if (! isset($by[$cid])) {
                $by[$cid] = [];
            }
            $by[$cid][] = $h;
        }
        foreach ($by as $cid => $hours) {
            $hours = array_values(array_unique(array_map('intval', $hours)));
            sort($hours);
            $by[$cid] = $hours;
        }

        return $by;
    }

    /**
     * @param  list<int>  $sortedUnique
     * @return list<list<int>>
     */
    public static function contiguousHourRuns(array $sortedUnique): array
    {
        if ($sortedUnique === []) {
            return [];
        }
        $runs = [];
        $run = [$sortedUnique[0]];
        for ($i = 1, $n = count($sortedUnique); $i < $n; $i++) {
            if ($sortedUnique[$i] === $sortedUnique[$i - 1] + 1) {
                $run[] = $sortedUnique[$i];
            } else {
                $runs[] = $run;
                $run = [$sortedUnique[$i]];
            }
        }
        $runs[] = $run;

        return $runs;
    }

    /**
     * @param  list<string>  $selectedSlots
     */
    public static function totalSelectedSlotHours(array $selectedSlots): int
    {
        $n = 0;
        foreach (self::selectedSlotsGroupedByCourt($selectedSlots) as $hours) {
            $n += count($hours);
        }

        return $n;
    }

    /**
     * @param  list<array{id: string, day_of_week: int, is_closed: bool, opens_at: string, closes_at: string}>  $scheduleRows
     * @return list<int>
     */
    public static function slotHoursForSelectedDate(CourtClient $courtClient, array $scheduleRows, string $bookingCalendarDate): array
    {
        $date = self::normalizedBookingCalendarDate($bookingCalendarDate);
        if ($date !== null && $courtClient->isClosedOnDate($date)) {
            return [];
        }

        try {
            $dow = (int) Carbon::parse($bookingCalendarDate.' 12:00:00', config('app.timezone', 'UTC'))->format('w');
        } catch (\Throwable) {
            return [];
        }

        $hours = VenueScheduleHours::slotStartHoursForDay($scheduleRows, $dow);

        return self::filterPastSlotHoursForCalendarDate($date ?? '', $hours);
    }

    /**
     * Public venue booking: no past calendar days; on today, omit slot rows at or before the current hour (app TZ).
     *
     * @param  list<int>  $hours
     * @return list<int>
     */
    public static function filterPastSlotHoursForCalendarDate(string $dateYmd, array $hours): array
    {
        if ($dateYmd === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateYmd)) {
            return [];
        }

        $tz = config('app.timezone', 'UTC');

        try {
            $picked = Carbon::parse($dateYmd.' 12:00:00', $tz)->startOfDay();
            $today = Carbon::now($tz)->startOfDay();
        } catch (\Throwable) {
            return [];
        }

        if ($picked->lt($today)) {
            return [];
        }

        if ($picked->greaterThan($today)) {
            return array_values($hours);
        }

        $currentHour = (int) Carbon::now($tz)->hour;

        return array_values(array_filter($hours, static fn (int $h): bool => $h > $currentHour));
    }

    public static function normalizedBookingCalendarDate(string $bookingCalendarDate): ?string
    {
        try {
            return Carbon::parse($bookingCalendarDate, config('app.timezone', 'UTC'))->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    public static function bookingOverlapsCourt(string $courtId, Carbon $starts, Carbon $ends): bool
    {
        return Booking::query()
            ->where('court_id', $courtId)
            ->whereIn('status', Booking::statusesBlockingCourtAvailability())
            ->where('starts_at', '<', $ends)
            ->where('ends_at', '>', $starts)
            ->exists();
    }
}
