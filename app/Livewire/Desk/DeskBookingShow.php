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

    /** When set, “Back” returns to the front-desk home daily view for this Y-m-d. */
    public ?string $backToDailyDay = null;

    public function mount(Booking $booking): void
    {
        $user = auth()->user();
        $venue = $user?->deskCourtClient;
        abort_unless(
            $venue !== null
            && $booking->court_client_id === $venue->id,
            403,
        );

        $this->booking = $booking->load(['courtClient', 'court', 'user', 'coach']);

        if (request()->query('from') === 'daily') {
            $day = request()->query('day');
            $this->backToDailyDay = is_string($day) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $day) ? $day : null;
        }
    }

    public function calendarUrl(): string
    {
        $tz = config('app.timezone', 'UTC');
        $ym = $this->booking->starts_at !== null
            ? $this->booking->starts_at->timezone($tz)->format('Y-m')
            : now($tz)->format('Y-m');

        return route('desk.bookings.calendar', ['ym' => $ym]);
    }

    public function deskBackUrl(): string
    {
        if ($this->backToDailyDay !== null) {
            return route('desk.home', ['day' => $this->backToDailyDay]);
        }

        return $this->calendarUrl();
    }

    public function deskBackLabel(): string
    {
        return $this->backToDailyDay !== null ? '← Back to daily schedule' : '← Back to calendar';
    }

    public function render(): View
    {
        return view('livewire.desk.desk-booking-show');
    }
}
