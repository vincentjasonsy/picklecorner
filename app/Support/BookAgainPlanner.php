<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\CourtClient;
use App\Services\VenueBookingSlotProbe;
use Carbon\Carbon;

final class BookAgainPlanner
{
    /**
     * Try to deep-link account venue booking with the nearest matching slots.
     *
     * @return array{url: string, flash: ?string}
     */
    public static function redirectPayload(Booking $booking): array
    {
        $courtClient = CourtClient::query()->find($booking->court_client_id);

        if ($courtClient === null || ! $courtClient->is_active || $booking->court_id === null) {
            return [
                'url' => route('account.book'),
                'flash' => 'That venue isn’t available to repeat automatically — browse Book Now.',
            ];
        }

        $courtClient->loadMissing(['weeklyHours']);

        $hours = self::hourlyStartHoursFromBooking($booking);
        if ($hours === []) {
            return [
                'url' => route('account.book.venue', $courtClient),
                'flash' => 'Choose a fresh time — we couldn’t read slot hours from your last booking.',
            ];
        }

        $tz = config('app.timezone', 'UTC');

        $lastStart = $booking->starts_at?->timezone($tz);
        if ($lastStart === null) {
            return [
                'url' => route('account.book.venue', $courtClient),
                'flash' => null,
            ];
        }

        $targetWeekday = (int) $lastStart->format('w');

        $primaryDate = self::firstCalendarDateMatchingWeekdayAfterToday($tz, $targetWeekday);

        $courtId = (string) $booking->court_id;

        $candidates = self::candidatePlans($tz, $primaryDate, $hours);

        /** @var array{date: string, hours: list<int>, label: string}|null $winner */
        $winner = null;
        $fallbackLabels = [];

        foreach ($candidates as $candidate) {
            $ok = VenueBookingSlotProbe::canSelectSlots(
                $courtClient,
                $candidate['date'],
                $courtId,
                $candidate['hours'],
            );

            if ($ok && $winner === null) {
                $winner = $candidate;
                break;
            }

            if (! $ok && count($fallbackLabels) < 6) {
                $fallbackLabels[] = $candidate['label'];
            }
        }

        if ($winner !== null) {
            $slots = array_map(static fn (int $h): string => $courtId.'-'.$h, $winner['hours']);
            $query = http_build_query([
                'book_date' => $winner['date'],
                'book_slots' => implode(',', $slots),
            ]);

            return [
                'url' => route('account.book.venue', $courtClient).'?'.$query,
                'flash' => 'Opened '.$courtClient->name.' · '.$winner['label'].'. Tap a slot if you want to tweak it.',
            ];
        }

        $fallback = '';
        if ($fallbackLabels !== []) {
            $fallback = ' Typical angles we tried from that session: '.implode(' · ', array_slice(array_unique($fallbackLabels), 0, 3)).'.';
        }

        return [
            'url' => route('account.book.venue', $courtClient).'?'.http_build_query([
                'book_date' => $primaryDate->format('Y-m-d'),
            ]),
            'flash' => 'Your usual window wasn’t open — pick the closest slot on this day.'.$fallback,
        ];
    }

    /**
     * Next calendar day at or after tomorrow whose weekday (`w`) matches target (0 Sun … 6 Sat).
     */
    protected static function firstCalendarDateMatchingWeekdayAfterToday(string $tz, int $targetWeekday): Carbon
    {
        $scan = Carbon::now($tz)->addDay()->startOfDay();

        for ($i = 0; $i < 400; $i++) {
            if ((int) $scan->format('w') === $targetWeekday) {
                return $scan;
            }
            $scan->addDay();
        }

        return Carbon::now($tz)->addWeek();
    }

    /**
     * @return list<int>
     */
    protected static function hourlyStartHoursFromBooking(Booking $booking): array
    {
        $tz = config('app.timezone', 'UTC');
        $start = $booking->starts_at?->timezone($tz);
        $end = $booking->ends_at?->timezone($tz);

        if ($start === null || $end === null || $end->lte($start)) {
            return [];
        }

        $hours = [];
        $cursor = $start->copy()->startOfHour();

        while ($cursor < $end && count($hours) < 24) {
            $hours[] = (int) $cursor->format('G');
            $cursor->addHour();
        }

        sort($hours);

        return array_values(array_unique($hours));
    }

    /**
     * @param  list<int>  $hours
     * @return list<array{date: string, hours: list<int>, label: string}>
     */
    protected static function candidatePlans(string $tz, Carbon $primaryDate, array $hours): array
    {
        /** @var list<array{date: string, hours: list<int>, label: string}> $out */
        $out = [];

        $humanRange = static function (Carbon $day, array $hs) use ($tz): string {
            $first = min($hs);
            $last = max($hs);
            $t0 = Carbon::parse($day->format('Y-m-d').' '.sprintf('%02d:00:00', $first), $tz);
            $t1 = Carbon::parse($day->format('Y-m-d').' '.sprintf('%02d:00:00', $last), $tz)->addHour();

            return $t0->isoFormat('ddd MMM D · h:mm').'–'.$t1->isoFormat('h:mm A');
        };

        $primaryYmd = $primaryDate->format('Y-m-d');

        // 1) Preferred: same weekday / next occurrence, original hours.
        $out[] = [
            'date' => $primaryYmd,
            'hours' => $hours,
            'label' => $humanRange($primaryDate, $hours),
        ];

        // 2–3) Same calendar day — shift blocks earlier / later while keeping duration.
        foreach ([-1, 1] as $delta) {
            $shifted = array_values(array_filter(array_map(
                static fn (int $h): int => $h + $delta,
                $hours,
            ), static fn (int $h): bool => $h >= 0 && $h <= 23));

            sort($shifted);

            if ($shifted !== [] && VenueScheduleHours::areContiguous($shifted) && count($shifted) === count($hours)) {
                $label = ($delta < 0 ? 'Earlier · ' : 'Later · ').$humanRange($primaryDate, $shifted);

                $out[] = [
                    'date' => $primaryYmd,
                    'hours' => $shifted,
                    'label' => $label,
                ];
            }
        }

        // 4) Next calendar day — same clock hours when available.
        $tomorrow = $primaryDate->copy()->addDay();
        $out[] = [
            'date' => $tomorrow->format('Y-m-d'),
            'hours' => $hours,
            'label' => 'Next calendar day · '.$humanRange($tomorrow, $hours),
        ];

        // 5) Same weekday next week (+7).
        $nextWeek = $primaryDate->copy()->addDays(7);
        $out[] = [
            'date' => $nextWeek->format('Y-m-d'),
            'hours' => $hours,
            'label' => 'Next week · '.$humanRange($nextWeek, $hours),
        ];

        return $out;
    }
}
