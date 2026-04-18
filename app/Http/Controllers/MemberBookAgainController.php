<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Support\BookAgainPlanner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class MemberBookAgainController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $booking = Booking::query()
            ->where('user_id', $request->user()?->id)
            ->whereIn('status', [Booking::STATUS_CONFIRMED, Booking::STATUS_COMPLETED])
            ->whereNotNull('court_id')
            ->latest('starts_at')
            ->first();

        if ($booking === null) {
            return redirect()->route('account.dashboard')->with(
                'status',
                'Book a court first — then you can repeat your last session in one tap.'
            );
        }

        $payload = BookAgainPlanner::redirectPayload($booking);

        $redirect = redirect()->to($payload['url']);

        if ($payload['flash'] !== null && $payload['flash'] !== '') {
            $redirect->with('status', $payload['flash']);
        }

        return $redirect;
    }
}
