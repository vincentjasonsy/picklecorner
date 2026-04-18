<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Support\BookingCalendar;
use Symfony\Component\HttpFoundation\Response;

final class MemberBookingCalendarController extends Controller
{
    public function __invoke(Booking $booking): Response
    {
        abort_unless(auth()->check() && $booking->user_id === auth()->id(), 403);

        abort_if($booking->starts_at === null || $booking->ends_at === null, 404);

        $booking->load(['courtClient', 'court', 'coach']);

        $body = BookingCalendar::icsFromBooking($booking);
        $slug = substr((string) $booking->id, 0, 8);

        return response($body, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="court-booking-'.$slug.'.ics"',
        ]);
    }
}
