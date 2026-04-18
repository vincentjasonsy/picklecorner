<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtClient;
use Carbon\Carbon;

/**
 * Google Calendar links and RFC 5545 iCalendar (.ics) for member bookings / venue checkout review.
 */
final class BookingCalendar
{
    public static function googleCalendarUrl(Booking $booking): ?string
    {
        if ($booking->starts_at === null || $booking->ends_at === null) {
            return null;
        }

        $booking->loadMissing(['courtClient', 'court', 'coach']);

        $start = $booking->starts_at->clone()->utc();
        $end = $booking->ends_at->clone()->utc();
        $dates = $start->format('Ymd\THis\Z').'/'.$end->format('Ymd\THis\Z');

        return 'https://calendar.google.com/calendar/render?'.http_build_query([
            'action' => 'TEMPLATE',
            'text' => self::eventTitle($booking),
            'dates' => $dates,
            'details' => self::eventDescription($booking),
            'location' => self::locationLine($booking->courtClient),
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public static function icsFromBooking(Booking $booking): string
    {
        $booking->loadMissing(['courtClient', 'court', 'coach']);

        if ($booking->starts_at === null || $booking->ends_at === null) {
            return self::wrapCalendar([]);
        }

        $start = $booking->starts_at->clone()->utc();
        $end = $booking->ends_at->clone()->utc();

        $event = self::veventBlocks(
            uid: self::stableUid('booking', $booking->id),
            dtStampUtc: Carbon::now('UTC'),
            dtStartUtc: $start,
            dtEndUtc: $end,
            summary: self::eventTitle($booking),
            description: self::eventDescription($booking),
            location: self::locationLine($booking->courtClient),
        );

        return self::wrapCalendar([$event]);
    }

    /**
     * @param  list<array{court: Court, starts: Carbon, ends: Carbon}>  $specs
     */
    public static function icsFromVenueSpecs(CourtClient $venue, array $specs): string
    {
        if ($specs === []) {
            return self::wrapCalendar([]);
        }

        $blocks = [];
        foreach ($specs as $i => $spec) {
            $court = $spec['court'];
            $starts = $spec['starts']->clone()->utc();
            $ends = $spec['ends']->clone()->utc();
            $title = trim($court->name).' · '.trim((string) $venue->name);
            $desc = trim((string) $venue->name)."\n".$court->name;
            $blocks[] = self::veventBlocks(
                uid: self::stableUid('spec', $venue->id.'-'.$court->id.'-'.$starts->timestamp.'-'.$i),
                dtStampUtc: Carbon::now('UTC'),
                dtStartUtc: $starts,
                dtEndUtc: $ends,
                summary: $title,
                description: $desc,
                location: self::locationLine($venue),
            );
        }

        return self::wrapCalendar($blocks);
    }

    private static function eventTitle(Booking $booking): string
    {
        $venue = $booking->courtClient?->name ?? 'Court booking';
        $court = $booking->court?->name;

        return $court !== null && $court !== '' ? $court.' · '.$venue : $venue;
    }

    private static function eventDescription(Booking $booking): string
    {
        $lines = [];
        $lines[] = 'Ref: '.$booking->transactionReference();
        if ($booking->court?->name) {
            $lines[] = 'Court: '.$booking->court->name;
        }
        if ($booking->coach?->name) {
            $lines[] = 'Coach: '.$booking->coach->name;
        }
        if ($booking->courtClient?->name) {
            $lines[] = 'Venue: '.$booking->courtClient->name;
        }

        return implode("\n", $lines);
    }

    private static function locationLine(?CourtClient $client): string
    {
        if ($client === null) {
            return '';
        }

        return implode(', ', array_values(array_filter([
            $client->address !== null ? trim((string) $client->address) : '',
            $client->city !== null ? trim((string) $client->city) : '',
        ], fn (string $s): bool => $s !== '')));
    }

    private static function escapeIcsText(string $text): string
    {
        return str_replace(['\\', ';', ',', "\r", "\n"], ['\\\\', '\\;', '\\,', '', '\\n'], $text);
    }

    /** @param  list<string>  $veventBodies  Lines inside each VEVENT (BEGIN...END without outer CALENDAR). */
    private static function wrapCalendar(array $veventBodies): string
    {
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//GameQ//Court Booking//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
        ];
        foreach ($veventBodies as $body) {
            foreach (explode("\r\n", $body) as $bl) {
                $lines[] = $bl;
            }
        }
        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines)."\r\n";
    }

    private static function veventBlocks(
        string $uid,
        Carbon $dtStampUtc,
        Carbon $dtStartUtc,
        Carbon $dtEndUtc,
        string $summary,
        string $description,
        string $location,
    ): string {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'court-booking.local';
        $fullUid = $uid.'@'.$host;

        $fold = fn (string $name, string $value): array => self::foldIcsLine($name.':'.$value);

        $out = ['BEGIN:VEVENT'];
        foreach ($fold('UID', $fullUid) as $l) {
            $out[] = $l;
        }
        foreach ($fold('DTSTAMP', $dtStampUtc->clone()->utc()->format('Ymd\THis\Z')) as $l) {
            $out[] = $l;
        }
        foreach ($fold('DTSTART', $dtStartUtc->clone()->utc()->format('Ymd\THis\Z')) as $l) {
            $out[] = $l;
        }
        foreach ($fold('DTEND', $dtEndUtc->clone()->utc()->format('Ymd\THis\Z')) as $l) {
            $out[] = $l;
        }
        foreach ($fold('SUMMARY', self::escapeIcsText($summary)) as $l) {
            $out[] = $l;
        }
        if ($description !== '') {
            foreach ($fold('DESCRIPTION', self::escapeIcsText($description)) as $l) {
                $out[] = $l;
            }
        }
        if ($location !== '') {
            foreach ($fold('LOCATION', self::escapeIcsText($location)) as $l) {
                $out[] = $l;
            }
        }
        $out[] = 'END:VEVENT';

        return implode("\r\n", $out);
    }

    /**
     * @return list<string>
     */
    private static function foldIcsLine(string $line): array
    {
        $max = 75;
        if (strlen($line) <= $max) {
            return [$line];
        }
        $chunks = [];
        $first = substr($line, 0, $max);
        $chunks[] = $first;
        $rest = substr($line, $max);
        while ($rest !== '') {
            $piece = substr($rest, 0, $max - 1);
            $chunks[] = ' '.$piece;
            $rest = substr($rest, $max - 1);
        }

        return $chunks;
    }

    private static function stableUid(string $kind, string $id): string
    {
        return $kind.'-'.$id;
    }
}
