<?php

namespace App\Livewire\Coach;

use App\Models\Booking;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::member')]
#[Title('Coached booking')]
class CoachBookingShow extends Component
{
    public Booking $booking;

    public function mount(Booking $booking): void
    {
        abort_unless($booking->coach_user_id === auth()->id(), 403);

        $this->booking = $booking->load(['courtClient', 'court', 'user']);
    }

    public function calendarUrl(): string
    {
        $tz = config('app.timezone', 'UTC');
        $ym = $this->booking->starts_at !== null
            ? $this->booking->starts_at->timezone($tz)->format('Y-m')
            : now($tz)->format('Y-m');

        return route('account.coach.bookings.calendar', ['ym' => $ym]);
    }

    public function render(): View
    {
        return view('livewire.coach.coach-booking-show');
    }
}
