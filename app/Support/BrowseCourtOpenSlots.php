<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtClientClosedDay;
use App\Models\CourtDateSlotBlock;
use App\Models\CourtTimeSlotBlock;
use App\Models\VenueWeeklyHour;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Detects courts that have at least one bookable single-hour slot in an upcoming window (browse / Book Now filter).
 */
final class BrowseCourtOpenSlots
{
    /**
     * Court IDs that have at least one bookable single-hour slot within the next N calendar days (starting tomorrow).
     *
     * @param  Collection<int, Court>  $courts
     * @return array<string, true>
     */
    public static function courtIdsWithAnyOpenSlot(Collection $courts, int $lookaheadDays = 14): array
    {
        if ($courts->isEmpty()) {
            return [];
        }

        $lookaheadDays = max(1, $lookaheadDays);

        $tz = config('app.timezone', 'UTC');
        $tomorrow = Carbon::now($tz)->addDay()->startOfDay();
        $lastDay = $tomorrow->copy()->addDays($lookaheadDays - 1);

        $courtIds = $courts->pluck('id')->unique()->values()->all();
        $clientIds = $courts->pluck('court_client_id')->unique()->values()->all();

        $scheduleByClient = self::scheduleRowsByClientId($clientIds);
        $closedClientDates = self::closedDateSetByClient($clientIds, $tomorrow, $lastDay);
        $dateBlocks = self::dateBlockSet($courtIds, $tomorrow, $lastDay);
        $weeklyBlocks = self::weeklyBlocksByCourt($courtIds);
        $bookingsByCourt = self::bookingsGrouped($courtIds, $tomorrow, $lastDay);

        $out = [];

        foreach ($courts as $court) {
            $clientKey = (string) $court->court_client_id;
            $schedule = $scheduleByClient[$clientKey] ?? [];
            if ($schedule === []) {
                continue;
            }

            $found = false;
            for ($d = 0; $d < $lookaheadDays && ! $found; $d++) {
                $day = $tomorrow->copy()->addDays($d);
                $dateYmd = $day->format('Y-m-d');
                if (isset($closedClientDates[$clientKey.'|'.$dateYmd])) {
                    continue;
                }

                $dow = (int) $day->format('w');
                $hours = VenueScheduleHours::slotStartHoursForDay($schedule, $dow);
                foreach ($hours as $h) {
                    $h = (int) $h;
                    if (self::weeklyBlocked($weeklyBlocks, $court->id, $dow, $h)) {
                        continue;
                    }
                    if (isset($dateBlocks[(string) $court->id.'|'.$dateYmd.'|'.$h])) {
                        continue;
                    }
                    if (self::bookingBlocksSlot($bookingsByCourt, $court->id, $dateYmd, $h, $tz)) {
                        continue;
                    }
                    $out[(string) $court->id] = true;
                    $found = true;
                    break;
                }
            }
        }

        return $out;
    }

    /**
     * Courts that have at least {@see $minContiguousHours} contiguous bookable start hours on one calendar day,
     * counting only hours in {@code [$windowStartHourInclusive, $windowEndHourExclusive)} (24h clock).
     *
     * @param  Collection<int, Court>  $courts
     * @return array<string, true> court id => true
     */
    public static function courtIdsWithMinContiguousHoursOnDate(
        Collection $courts,
        string $dateYmd,
        int $minContiguousHours,
        int $windowStartHourInclusive,
        int $windowEndHourExclusive,
    ): array {
        if ($courts->isEmpty()) {
            return [];
        }

        $tz = config('app.timezone', 'UTC');

        try {
            $day = Carbon::parse($dateYmd, $tz)->startOfDay();
        } catch (\Throwable) {
            return [];
        }

        $windowStartHourInclusive = max(0, min(23, $windowStartHourInclusive));
        $windowEndHourExclusive = max(1, min(24, $windowEndHourExclusive));
        if ($windowStartHourInclusive >= $windowEndHourExclusive) {
            return [];
        }

        $minContiguousHours = max(1, min(24, $minContiguousHours));

        $dateStr = $day->format('Y-m-d');

        $courtIds = $courts->pluck('id')->unique()->values()->all();
        $clientIds = $courts->pluck('court_client_id')->unique()->values()->all();

        $scheduleByClient = self::scheduleRowsByClientId($clientIds);
        $closedClientDates = self::closedDateSetByClient($clientIds, $day, $day);
        $dateBlocks = self::dateBlockSet($courtIds, $day, $day);
        $weeklyBlocks = self::weeklyBlocksByCourt($courtIds);
        $bookingsByCourt = self::bookingsGrouped($courtIds, $day, $day);

        $out = [];

        foreach ($courts as $court) {
            $clientKey = (string) $court->court_client_id;
            $schedule = $scheduleByClient[$clientKey] ?? [];
            if ($schedule === []) {
                continue;
            }

            if (isset($closedClientDates[$clientKey.'|'.$dateStr])) {
                continue;
            }

            $dow = (int) $day->format('w');
            $hours = VenueScheduleHours::slotStartHoursForDay($schedule, $dow);

            $available = [];

            foreach ($hours as $h) {
                $h = (int) $h;
                if ($h < $windowStartHourInclusive || $h >= $windowEndHourExclusive) {
                    continue;
                }
                if (self::weeklyBlocked($weeklyBlocks, $court->id, $dow, $h)) {
                    continue;
                }
                if (isset($dateBlocks[(string) $court->id.'|'.$dateStr.'|'.$h])) {
                    continue;
                }
                if (self::bookingBlocksSlot($bookingsByCourt, $court->id, $dateStr, $h, $tz)) {
                    continue;
                }
                $available[] = $h;
            }

            if ($available === []) {
                continue;
            }

            sort($available);
            $available = array_values(array_unique($available));

            if (self::longestContiguousRun($available) >= $minContiguousHours) {
                $out[(string) $court->id] = true;
            }
        }

        return $out;
    }

    /**
     * @param  list<int>  $sortedUniqueHours
     */
    private static function longestContiguousRun(array $sortedUniqueHours): int
    {
        $n = count($sortedUniqueHours);
        if ($n === 0) {
            return 0;
        }

        $best = 1;
        $run = 1;

        for ($i = 1; $i < $n; $i++) {
            if ($sortedUniqueHours[$i] === $sortedUniqueHours[$i - 1]) {
                continue;
            }
            if ($sortedUniqueHours[$i] === $sortedUniqueHours[$i - 1] + 1) {
                $run++;
                if ($run > $best) {
                    $best = $run;
                }
            } else {
                $run = 1;
            }
        }

        return $best;
    }

    /**
     * @param  list<string>  $clientIds
     * @return array<string, list<array{day_of_week: int, is_closed: bool, opens_at: string, closes_at: string}>>
     */
    private static function scheduleRowsByClientId(array $clientIds): array
    {
        if ($clientIds === []) {
            return [];
        }

        $rows = VenueWeeklyHour::query()
            ->whereIn('court_client_id', $clientIds)
            ->orderBy('day_of_week')
            ->get();

        $map = [];
        foreach ($rows as $r) {
            $id = (string) $r->court_client_id;
            $map[$id] ??= [];
            $map[$id][] = [
                'day_of_week' => (int) $r->day_of_week,
                'is_closed' => (bool) $r->is_closed,
                'opens_at' => $r->opens_at ?? '09:00',
                'closes_at' => $r->closes_at ?? '21:00',
            ];
        }

        return $map;
    }

    /**
     * @return array<string, true>
     */
    private static function closedDateSetByClient(array $clientIds, Carbon $start, Carbon $end): array
    {
        $set = [];
        if ($clientIds === []) {
            return $set;
        }

        $rows = CourtClientClosedDay::query()
            ->whereIn('court_client_id', $clientIds)
            ->whereDate('closed_on', '>=', $start->toDateString())
            ->whereDate('closed_on', '<=', $end->toDateString())
            ->get(['court_client_id', 'closed_on']);

        foreach ($rows as $r) {
            $d = Carbon::parse($r->closed_on)->format('Y-m-d');
            $set[(string) $r->court_client_id.'|'.$d] = true;
        }

        return $set;
    }

    /**
     * @return array<string, true> key courtId|Y-m-d|hour
     */
    private static function dateBlockSet(array $courtIds, Carbon $start, Carbon $end): array
    {
        $set = [];
        if ($courtIds === []) {
            return $set;
        }

        $rows = CourtDateSlotBlock::query()
            ->whereIn('court_id', $courtIds)
            ->whereDate('blocked_date', '>=', $start->toDateString())
            ->whereDate('blocked_date', '<=', $end->toDateString())
            ->get(['court_id', 'blocked_date', 'slot_start_hour']);

        foreach ($rows as $r) {
            $d = Carbon::parse($r->blocked_date)->format('Y-m-d');
            $set[(string) $r->court_id.'|'.$d.'|'.(int) $r->slot_start_hour] = true;
        }

        return $set;
    }

    /**
     * @return array<string, array<int, array<int, true>>>
     */
    private static function weeklyBlocksByCourt(array $courtIds): array
    {
        if ($courtIds === []) {
            return [];
        }

        $rows = CourtTimeSlotBlock::query()
            ->whereIn('court_id', $courtIds)
            ->get(['court_id', 'day_of_week', 'slot_start_hour']);

        $map = [];
        foreach ($rows as $r) {
            $cid = (string) $r->court_id;
            $map[$cid] ??= [];
            $dow = (int) $r->day_of_week;
            $map[$cid][$dow] ??= [];
            $map[$cid][$dow][(int) $r->slot_start_hour] = true;
        }

        return $map;
    }

    /**
     * @return array<string, Collection<int, Booking>>
     */
    private static function bookingsGrouped(array $courtIds, Carbon $start, Carbon $end): array
    {
        if ($courtIds === []) {
            return [];
        }

        $rangeStart = $start->copy()->startOfDay();
        $rangeEnd = $end->copy()->endOfDay();

        $bookings = Booking::query()
            ->whereIn('court_id', $courtIds)
            ->whereIn('status', Booking::statusesBlockingCourtAvailability())
            ->where('starts_at', '<', $rangeEnd)
            ->where('ends_at', '>', $rangeStart)
            ->get(['court_id', 'starts_at', 'ends_at']);

        /** @var array<string, Collection<int, Booking>> */
        return $bookings->groupBy(fn ($b) => (string) $b->court_id)->all();
    }

    /**
     * @param  array<string, array<int, array<int, true>>>  $weeklyBlocks
     */
    private static function weeklyBlocked(array $weeklyBlocks, mixed $courtId, int $dow, int $hour): bool
    {
        $cid = (string) $courtId;

        return isset($weeklyBlocks[$cid][$dow][$hour]);
    }

    /**
     * @param  array<string, Collection<int, Booking>>  $bookingsByCourt
     */
    private static function bookingBlocksSlot(array $bookingsByCourt, mixed $courtId, string $dateYmd, int $hour, string $tz): bool
    {
        $cid = (string) $courtId;
        $bookings = $bookingsByCourt[$cid] ?? null;
        if ($bookings === null || $bookings->isEmpty()) {
            return false;
        }

        $slotStart = Carbon::parse($dateYmd.' '.sprintf('%02d:00:00', $hour), $tz);
        $slotEnd = $slotStart->copy()->addHour();

        foreach ($bookings as $b) {
            if ($b->starts_at !== null && $b->ends_at !== null
                && $b->starts_at < $slotEnd && $b->ends_at > $slotStart) {
                return true;
            }
        }

        return false;
    }
}
