<?php

namespace App\Livewire\Desk;

use App\Models\Booking;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::desk-portal')]
#[Title('Booking')]
class DeskBookingShow extends Component
{
    public Booking $booking;

    public function mount(Booking $booking): void
    {
        $user = auth()->user();
        $venue = $user?->deskCourtClient;
        abort_unless(
            $venue !== null
            && $booking->court_client_id === $venue->id
            && $booking->desk_submitted_by === $user->id,
            403,
        );

        $this->booking = $booking->load(['courtClient', 'court', 'user', 'coach']);
    }

    public function calendarUrl(): string
    {
        $tz = config('app.timezone', 'UTC');
        $ym = $this->booking->starts_at !== null
            ? $this->booking->starts_at->timezone($tz)->format('Y-m')
            : now($tz)->format('Y-m');

        return route('desk.bookings.calendar', ['ym' => $ym]);
    }

    public function render(): View
    {
        return view('livewire.desk.desk-booking-show');
    }
}
