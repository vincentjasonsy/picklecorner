<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\CourtClient;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

final class MemberBookingStats
{
    /**
     * Player booking insights for profile / motivation UI.
     *
     * @return array{
     *     total_hours: float,
     *     bookings_this_month: int,
     *     hours_this_week: float,
     *     favorite_venue: ?string,
     *     favorite_day_time: ?string,
     *     streak_weeks: int,
     *     this_week_has_booking: bool,
     * }
     */
    public static function forUser(User $user): array
    {
        $tz = config('app.timezone', 'UTC');
        $now = Carbon::now($tz);

        $base = self::statsBookingsQuery($user);

        $totalHours = self::sumHours($base->clone());

        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();
        $bookingsThisMonth = (clone $base)
            ->whereBetween('starts_at', [$monthStart, $monthEnd])
            ->count();

        $weekStart = $now->copy()->startOfWeek(CarbonInterface::MONDAY);
        $weekEnd = $now->copy()->endOfWeek(CarbonInterface::SUNDAY);
        $hoursThisWeek = self::sumHours(
            (clone $base)->whereBetween('starts_at', [$weekStart, $weekEnd]),
        );

        $thisWeekHasBooking = (clone $base)
            ->whereBetween('starts_at', [$weekStart, $weekEnd])
            ->exists();

        $favoriteVenue = self::favoriteVenueName($base->clone());

        $favoriteDayTime = self::favoriteDayAndTimeLabel($base->clone(), $tz);

        $streakWeeks = self::computeWeeklyStreakWeeks($user, $now, $thisWeekHasBooking);

        return [
            'total_hours' => round((float) $totalHours, 1),
            'bookings_this_month' => $bookingsThisMonth,
            'hours_this_week' => round((float) $hoursThisWeek, 1),
            'favorite_venue' => $favoriteVenue,
            'favorite_day_time' => $favoriteDayTime,
            'streak_weeks' => $streakWeeks,
            'this_week_has_booking' => $thisWeekHasBooking,
        ];
    }

    /** @param  Builder<Booking>  $query */
    private static function statsBookingsQuery(User $user): Builder
    {
        return Booking::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [
                Booking::STATUS_CONFIRMED,
                Booking::STATUS_COMPLETED,
            ])
            ->whereNotNull('starts_at')
            ->whereNotNull('ends_at');
    }

    /** @param  Builder<Booking>  $query */
    private static function sumHours(Builder $query): float
    {
        $minutes = 0.0;
        foreach ($query->get(['starts_at', 'ends_at']) as $booking) {
            $s = $booking->starts_at;
            $e = $booking->ends_at;
            if ($s === null || $e === null || $e->lte($s)) {
                continue;
            }
            $minutes += $s->diffInMinutes($e);
        }

        return $minutes / 60;
    }

    /** @param  Builder<Booking>  $query */
    private static function favoriteVenueName(Builder $query): ?string
    {
        $top = $query
            ->selectRaw('court_client_id, COUNT(*) as c')
            ->whereNotNull('court_client_id')
            ->groupBy('court_client_id')
            ->orderByDesc('c')
            ->first();

        if ($top === null || ! isset($top->court_client_id)) {
            return null;
        }

        $client = CourtClient::query()->find($top->court_client_id);

        return $client?->name;
    }

    /** @param  Builder<Booking>  $query */
    private static function favoriteDayAndTimeLabel(Builder $query, string $tz): ?string
    {
        /** @var Collection<string, int> $counts */
        $counts = collect();
        foreach ($query->get(['starts_at']) as $booking) {
            $local = $booking->starts_at?->timezone($tz);
            if ($local === null) {
                continue;
            }
            $key = $local->format('w').'-'.$local->format('G');
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        if ($counts->isEmpty()) {
            return null;
        }

        $max = $counts->max();
        $bestKey = $counts->filter(static fn (int $c): bool => $c === $max)->keys()->first();
        if ($bestKey === null || ! is_string($bestKey)) {
            return null;
        }

        [$w, $h] = array_pad(explode('-', $bestKey, 2), 2, '0');
        $sample = Carbon::now($tz)->startOfWeek(CarbonInterface::SUNDAY)->addDays((int) $w)->setTime((int) $h, 0);

        $day = $sample->isoFormat('dddd');
        $time = $sample->isoFormat('h:mm A');

        return $day.' · ~'.$time;
    }

    private static function computeWeeklyStreakWeeks(User $user, Carbon $now, bool $thisWeekHasBooking): int
    {
        $cursor = $now->copy()->startOfWeek(CarbonInterface::MONDAY);

        if (! $thisWeekHasBooking) {
            $cursor->subWeek();
        }

        $streak = 0;
        for ($i = 0; $i < 104; $i++) {
            $weekStart = $cursor->copy();
            $weekEnd = $cursor->copy()->endOfWeek(CarbonInterface::SUNDAY);

            $has = Booking::query()
                ->where('user_id', $user->id)
                ->whereIn('status', [
                    Booking::STATUS_CONFIRMED,
                    Booking::STATUS_COMPLETED,
                ])
                ->whereBetween('starts_at', [$weekStart, $weekEnd])
                ->exists();

            if (! $has) {
                break;
            }

            $streak++;
            $cursor->subWeek();
        }

        return $streak;
    }
}
