<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\PaymongoBookingIntent;
use App\Services\PaymongoVenueBookingPayment;
use App\Support\PaymongoCheckoutFlash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PayMongoBookingReturnController extends Controller
{
    private const PAYMENT_RECEIVED_FLASH = 'Payment received. Your booking request is in.';

    public function __invoke(Request $request, string $intent): RedirectResponse
    {
        $row = PaymongoBookingIntent::query()->find($intent);
        $slug = $row?->courtClient?->slug;

        if ($slug === null || $slug === '') {
            return redirect()->route('book-now');
        }

        $user = $request->user();
        if ($user === null || $row === null || $row->user_id !== $user->id) {
            return redirect()
                ->route('book-now.venue.book', ['courtClient' => $slug]);
        }

        if ($row->status === PaymongoBookingIntent::STATUS_PENDING) {
            PaymongoVenueBookingPayment::tryCompleteIntentFromPaidCheckoutSession($row);
            $row->refresh();
        }

        if ($row->status === PaymongoBookingIntent::STATUS_COMPLETED) {
            $requestId = $row->booking_request_id;
            if (is_string($requestId) && $requestId !== '') {
                $booking = Booking::query()
                    ->where('booking_request_id', $requestId)
                    ->where('user_id', $row->user_id)
                    ->orderBy('starts_at')
                    ->first();

                if ($booking !== null) {
                    return redirect()
                        ->route('account.bookings.show', $booking)
                        ->with('status', self::PAYMENT_RECEIVED_FLASH);
                }
            }

            return redirect()
                ->route('account.bookings')
                ->with('status', self::PAYMENT_RECEIVED_FLASH);
        }

        if ($row->status === PaymongoBookingIntent::STATUS_FAILED) {
            return redirect()
                ->route('book-now.venue.book', ['courtClient' => $slug])
                ->with('paymongo_checkout', PaymongoCheckoutFlash::forIntent($row, 'failed'));
        }

        return redirect()
            ->route('book-now.venue.book', ['courtClient' => $slug])
            ->with('paymongo_checkout', PaymongoCheckoutFlash::forIntent($row, 'unpaid'));
    }
}
