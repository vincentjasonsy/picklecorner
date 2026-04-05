<?php

namespace App\Support;

/**
 * Bookable start hours for a venue weekday from schedule row data (same rules as admin slot grids).
 */
final class VenueScheduleHours
{
    /**
     * @param  iterable<int, array{day_of_week: int, is_closed?: bool, opens_at?: string|null, closes_at?: string|null}>  $scheduleRows
     * @return list<int>
     */
    public static function slotStartHoursForDay(iterable $scheduleRows, int $dayOfWeek): array
    {
        $row = collect($scheduleRows)->firstWhere('day_of_week', $dayOfWeek);
        if (! $row || ! empty($row['is_closed'])) {
            return [];
        }

        $opens = (string) ($row['opens_at'] ?? '00:00');
        $closes = (string) ($row['closes_at'] ?? '00:00');
        $openH = (int) substr($opens, 0, 2);
        $openM = (int) substr($opens, 3, 2);
        $closeH = (int) substr($closes, 0, 2);
        $closeM = (int) substr($closes, 3, 2);

        $start = $openM > 0 ? $openH + 1 : $openH;
        $end = $closeM > 0 ? $closeH : $closeH - 1;
        if ($end < $start) {
            return [];
        }

        return range($start, $end);
    }

    /**
     * @param  list<int>  $sortedUniqueHours
     */
    public static function areContiguous(array $sortedUniqueHours): bool
    {
        $n = count($sortedUniqueHours);
        if ($n <= 1) {
            return true;
        }

        for ($i = 1; $i < $n; $i++) {
            if ($sortedUniqueHours[$i] !== $sortedUniqueHours[$i - 1] + 1) {
                return false;
            }
        }

        return true;
    }
}
