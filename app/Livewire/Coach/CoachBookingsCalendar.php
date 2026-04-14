<?php

namespace App\Livewire\Coach;

use App\Livewire\Concerns\WithBookingCalendarMonth;
use App\Models\Booking;
use App\Support\BookingCalendarGrid;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::member')]
#[Title('Coaching calendar')]
class CoachBookingsCalendar extends Component
{
    use WithBookingCalendarMonth;

    public function mount(): void
    {
        $this->initializeCalendarMonth();
    }

    public function statusBadgeClasses(string $status): string
    {
        return match ($status) {
            Booking::STATUS_CONFIRMED, Booking::STATUS_COMPLETED => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200',
            Booking::STATUS_PENDING_APPROVAL => 'bg-amber-100 text-amber-900 dark:bg-amber-950/50 dark:text-amber-200',
            Booking::STATUS_CANCELLED => 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200',
            Booking::STATUS_DENIED => 'bg-red-100 text-red-800 dark:bg-red-950/50 dark:text-red-200',
            default => 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200',
        };
    }

    public function render(): View
    {
        $tz = config('app.timezone', 'UTC');
        $monthStart = $this->calendarMonthStart();
        [$gridStart, $gridEnd] = BookingCalendarGrid::visibleGridBounds($monthStart, $tz);

        /** @var Collection<int, Booking> $bookings */
        $bookings = Booking::query()
            ->with(['courtClient:id,name', 'court:id,name', 'user:id,name'])
            ->where('coach_user_id', auth()->id())
            ->whereBetween('starts_at', [$gridStart, $gridEnd])
            ->orderBy('starts_at')
            ->get();

        $grid = BookingCalendarGrid::build($monthStart, $tz, $bookings);

        return view('livewire.coach.coach-bookings-calendar', [
            'weeks' => $grid['weeks'],
            'monthLabel' => $monthStart->copy()->timezone($tz)->isoFormat('MMMM YYYY'),
            'tz' => $tz,
            'calendarContext' => 'coach',
        ]);
    }
}
