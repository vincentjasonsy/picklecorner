<?php

namespace App\Livewire\Venue;

use App\Livewire\Admin\BookingShow;
use App\Models\Booking;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('layouts::venue-portal')]
#[Title('Booking')]
class VenueBookingShow extends BookingShow
{
    public function mount(Booking $booking): void
    {
        $mine = auth()->user()->administeredCourtClient;
        abort_unless(
            $mine !== null && $booking->court_client_id === $mine->id,
            403,
        );

        parent::mount($booking);
    }

    public function historyUrl(): string
    {
        $tz = config('app.timezone', 'UTC');
        if ($this->booking->starts_at === null) {
            return route('venue.bookings.history', [
                'from' => Carbon::now($tz)->startOfWeek(CarbonInterface::MONDAY)->toDateString(),
                'to' => Carbon::now($tz)->startOfWeek(CarbonInterface::MONDAY)->addDays(6)->toDateString(),
            ]);
        }

        $monday = $this->booking->starts_at->timezone($tz)->startOfWeek(CarbonInterface::MONDAY)->toDateString();
        $sunday = $this->booking->starts_at->timezone($tz)->startOfWeek(CarbonInterface::MONDAY)->addDays(6)->toDateString();

        return route('venue.bookings.history', ['from' => $monday, 'to' => $sunday]);
    }

    public function render(): View
    {
        return view('livewire.venue.venue-booking-show', [
            'booking' => $this->booking,
        ]);
    }
}
