<?php

namespace App\Services;

use App\Models\Court;
use Carbon\Carbon;

/**
 * Prevents double booking when two requests race for the same court and time range.
 * Call inside an open DB transaction: locks each affected court row (ordered by id), then re-runs overlap checks
 * so only one transaction can commit overlapping bookings.
 */
final class CourtBookingConcurrency
{
    /**
     * @param  list<array{court: Court, starts: Carbon, ends: Carbon}>  $specs
     */
    public static function lockCourtsAndAssertNoOverlap(array $specs): void
    {
        if ($specs === []) {
            return;
        }

        $courtIds = [];
        foreach ($specs as $spec) {
            $courtIds[] = $spec['court']->id;
        }
        $courtIds = array_values(array_unique($courtIds));
        sort($courtIds, SORT_STRING);

        foreach ($courtIds as $courtId) {
            Court::query()->whereKey($courtId)->lockForUpdate()->firstOrFail();
        }

        foreach ($specs as $spec) {
            $court = $spec['court'];
            $starts = $spec['starts'];
            $ends = $spec['ends'];

            if (! $starts instanceof Carbon || ! $ends instanceof Carbon) {
                throw new \InvalidArgumentException('Invalid booking time range.');
            }

            if (VenueBookingSpecsBuilder::bookingOverlapsCourt($court->id, $starts, $ends)) {
                throw new \InvalidArgumentException(
                    'That court time was just taken. Refresh the schedule and choose different slots.',
                );
            }
        }
    }
}
