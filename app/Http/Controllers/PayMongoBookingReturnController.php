<?php

namespace App\Http\Controllers;

use App\Models\PaymongoBookingIntent;
use App\Services\PaymongoVenueBookingPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PayMongoBookingReturnController extends Controller
{
    public function __invoke(Request $request, string $intent): RedirectResponse
    {
        $row = PaymongoBookingIntent::query()->find($intent);
        $slug = $row?->courtClient?->slug;

        if ($slug === null || $slug === '') {
            return redirect()->route('book-now');
        }

        if ($row->status === PaymongoBookingIntent::STATUS_PENDING) {
            PaymongoVenueBookingPayment::tryCompleteIntentFromPaidCheckoutSession($row);
            $row->refresh();
        }

        $message = match ($row->status) {
            PaymongoBookingIntent::STATUS_COMPLETED => 'Payment received. Your booking request is in.',
            default => 'Thanks — we are confirming your payment. Refresh your booking history in a moment if you do not see it yet.',
        };

        return redirect()
            ->route('book-now.venue.book', ['courtClient' => $slug])
            ->with('status', $message);
    }
}
