<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\CoachCourt;
use App\Models\CoachHourAvailability;
use App\Models\CourtClient;
use App\Models\User;
use App\Models\UserType;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class CoachAvailabilityService
{
    /**
     * Coaches that can take this request: must be linked to every selected court, have hour availability on each
     * court, and not double-booked across the requested time windows.
     *
     * @param  list<string>  $selectedSlots  courtId-hour keys
     * @param  list<array{starts: Carbon, ends: Carbon}>  $timeWindows  merged specs for overlap check
     */
    public static function availableCoaches(
        CourtClient $venue,
        string $dateYmd,
        array $selectedSlots,
        array $timeWindows,
    ): Collection {
        if ($selectedSlots === [] || $timeWindows === []) {
            return collect();
        }

        $byCourt = self::groupSlotsByCourt($selectedSlots);
        if ($byCourt === []) {
            return collect();
        }

        $courtIds = array_keys($byCourt);
        sort($courtIds);

        /** @var list<string>|null $coachIdsIntersect */
        $coachIdsIntersect = null;
        foreach ($courtIds as $cid) {
            $ids = CoachCourt::query()
                ->where('court_id', $cid)
                ->pluck('coach_user_id')
                ->map(fn ($id): string => (string) $id)
                ->all();
            $coachIdsIntersect = $coachIdsIntersect === null
                ? $ids
                : array_values(array_intersect($coachIdsIntersect, $ids));
        }

        if ($coachIdsIntersect === [] || $coachIdsIntersect === null) {
            return collect();
        }

        $coaches = User::query()
            ->whereIn('id', $coachIdsIntersect)
            ->whereHas('userType', fn ($q) => $q->where('slug', UserType::SLUG_COACH))
            ->with('coachProfile')
            ->orderBy('name')
            ->get();

        return $coaches->filter(function (User $coach) use ($byCourt, $dateYmd, $timeWindows): bool {
            foreach ($byCourt as $courtId => $hours) {
                $hoursSorted = array_values(array_unique(array_map('intval', $hours)));
                sort($hoursSorted);
                foreach ($hoursSorted as $h) {
                    $exists = CoachHourAvailability::query()
                        ->where('coach_user_id', $coach->id)
                        ->where('court_id', $courtId)
                        ->whereDate('date', $dateYmd)
                        ->where('hour', $h)
                        ->exists();
                    if (! $exists) {
                        return false;
                    }
                }
            }

            foreach ($timeWindows as $w) {
                if (self::coachHasOverlappingBooking($coach->id, $w['starts'], $w['ends'])) {
                    return false;
                }
            }

            return true;
        })->values();
    }

    /**
     * @param  list<string>  $selectedSlots
     * @return array<string, list<int>>
     */
    public static function groupSlotsByCourt(array $selectedSlots): array
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
        foreach ($by as $cid => $hs) {
            $by[$cid] = array_values(array_unique(array_map('intval', $hs)));
            sort($by[$cid]);
        }

        return $by;
    }

    public static function coachHasOverlappingBooking(
        string $coachUserId,
        Carbon $starts,
        Carbon $ends,
        ?string $exceptBookingId = null,
    ): bool {
        $q = Booking::query()
            ->where('coach_user_id', $coachUserId)
            ->whereIn('status', Booking::statusesBlockingCourtAvailability())
            ->where('starts_at', '<', $ends)
            ->where('ends_at', '>', $starts);

        if ($exceptBookingId !== null && $exceptBookingId !== '') {
            $q->where('id', '!=', $exceptBookingId);
        }

        return $q->exists();
    }

    /**
     * Coach fee in centavos for the given hours on one court.
     *
     * @param  list<int>  $hours
     */
    public static function coachFeeCentsForHours(User $coach, array $hours): int
    {
        $rate = (int) ($coach->coachProfile?->hourly_rate_cents ?? 0);

        return $rate * count($hours);
    }
}
