<?php

namespace App\Livewire\Desk;

use App\Livewire\Concerns\WithBookingCalendarMonth;
use App\Models\Booking;
use App\Models\CourtClient;
use App\Support\BookingCalendarGrid;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::desk-portal')]
#[Title('Booking calendar')]
class DeskBookingsCalendar extends Component
{
    use WithBookingCalendarMonth;

    public function mount(): void
    {
        abort_unless(auth()->user()?->deskCourtClient !== null, 403);
        $this->initializeCalendarMonth();
    }

    public function statusBadgeClasses(string $status): string
    {
        return match ($status) {
            Booking::STATUS_PENDING_APPROVAL => 'bg-amber-100 text-amber-950 dark:bg-amber-950/40 dark:text-amber-100',
            Booking::STATUS_CONFIRMED => 'bg-teal-100 text-teal-950 dark:bg-teal-950/40 dark:text-teal-100',
            Booking::STATUS_DENIED => 'bg-rose-100 text-rose-950 dark:bg-rose-950/40 dark:text-rose-100',
            Booking::STATUS_CANCELLED => 'bg-stone-200 text-stone-800 dark:bg-stone-700 dark:text-stone-200',
            Booking::STATUS_COMPLETED => 'bg-stone-200 text-stone-800 dark:bg-stone-600 dark:text-stone-100',
            default => 'bg-stone-200 text-stone-700 dark:bg-stone-700 dark:text-stone-200',
        };
    }

    public function render(): View
    {
        /** @var CourtClient $venue */
        $venue = auth()->user()->deskCourtClient;

        $tz = config('app.timezone', 'UTC');
        $monthStart = $this->calendarMonthStart();
        [$gridStart, $gridEnd] = BookingCalendarGrid::visibleGridBounds($monthStart, $tz);

        /** @var Collection<int, Booking> $bookings */
        $bookings = Booking::query()
            ->with(['court:id,name', 'user:id,name'])
            ->where('court_client_id', $venue->id)
            ->where('desk_submitted_by', auth()->id())
            ->whereBetween('starts_at', [$gridStart, $gridEnd])
            ->orderBy('starts_at')
            ->get();

        $grid = BookingCalendarGrid::build($monthStart, $tz, $bookings);

        return view('livewire.desk.desk-bookings-calendar', [
            'venue' => $venue,
            'weeks' => $grid['weeks'],
            'monthLabel' => $monthStart->copy()->timezone($tz)->isoFormat('MMMM YYYY'),
            'tz' => $tz,
            'calendarContext' => 'desk',
        ]);
    }
}
