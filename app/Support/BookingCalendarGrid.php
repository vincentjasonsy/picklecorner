<?php

namespace App\Support;

use App\Models\Booking;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final class BookingCalendarGrid
{
    /**
     * @return array{0: Carbon, 1: Carbon, 2: Carbon, 3: Carbon}
     */
    public static function visibleGridBounds(Carbon $monthStartInTz, string $tz): array
    {
        $monthStart = $monthStartInTz->copy()->timezone($tz)->startOfMonth()->startOfDay();
        $monthEnd = $monthStart->copy()->endOfMonth()->endOfDay();

        $gridStart = $monthStart->copy()->startOfWeek(CarbonInterface::MONDAY)->startOfDay();
        $gridEnd = $monthEnd->copy()->endOfWeek(CarbonInterface::SUNDAY)->endOfDay();

        return [$gridStart, $gridEnd, $monthStart, $monthEnd];
    }

    /**
     * @param  Collection<int, Booking>  $bookings  Bookings whose {@see Booking::$starts_at} falls within the grid (caller should query by grid bounds).
     * @return array{
     *     weeks: list<list<array{date: Carbon, in_month: bool, bookings: Collection<int, Booking>}>>,
     *     grid_start: Carbon,
     *     grid_end: Carbon,
     *     month_start: Carbon,
     *     month_end: Carbon,
     * }
     */
    public static function build(Carbon $monthStartInTz, string $tz, Collection $bookings): array
    {
        [$gridStart, $gridEnd, $monthStart, $monthEnd] = self::visibleGridBounds($monthStartInTz, $tz);

        /** @var Collection<string, Collection<int, Booking>> $byDay */
        $byDay = $bookings->groupBy(function (Booking $b) use ($tz): string {
            if ($b->starts_at === null) {
                return '';
            }

            return $b->starts_at->timezone($tz)->format('Y-m-d');
        })->filter(fn (Collection $_, string $k): bool => $k !== '');

        $weeks = [];
        $cursor = $gridStart->copy();
        $week = [];

        while ($cursor->lte($gridEnd)) {
            $key = $cursor->format('Y-m-d');
            $week[] = [
                'date' => $cursor->copy(),
                'in_month' => $cursor->between($monthStart, $monthEnd, true),
                'bookings' => $byDay->get($key, collect())->sortBy('starts_at')->values(),
            ];

            if (count($week) === 7) {
                $weeks[] = $week;
                $week = [];
            }

            $cursor->addDay();
        }

        return [
            'weeks' => $weeks,
            'grid_start' => $gridStart,
            'grid_end' => $gridEnd,
            'month_start' => $monthStart,
            'month_end' => $monthEnd,
        ];
    }
}
