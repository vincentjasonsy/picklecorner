<?php

namespace App\Http\Controllers;

use App\Models\PaymongoBookingIntent;
use App\Support\PaymongoCheckoutFlash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PayMongoBookingCancelController extends Controller
{
    public function __invoke(Request $request, string $intent): RedirectResponse
    {
        $row = PaymongoBookingIntent::query()->with('courtClient')->find($intent);

        if ($row === null) {
            return redirect()->route('book-now');
        }

        $slug = $row->courtClient?->slug;
        if ($slug === null || $slug === '') {
            return redirect()->route('book-now');
        }

        $user = $request->user();
        if ($user === null || $row->user_id !== $user->id) {
            return redirect()->route('book-now.venue.book', ['courtClient' => $slug]);
        }

        return redirect()
            ->route('book-now.venue.book', ['courtClient' => $slug])
            ->with('paymongo_checkout', PaymongoCheckoutFlash::forIntent($row, 'cancelled'));
    }
}
