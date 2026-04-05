<?php

namespace App\Livewire\Admin;

use App\Models\Booking;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::admin')]
#[Title('Booking')]
class BookingShow extends Component
{
    public Booking $booking;

    public function mount(Booking $booking): void
    {
        $this->booking = $booking->load([
            'courtClient',
            'court',
            'user',
            'deskSubmitter',
            'giftCard',
        ]);
    }

    public function historyUrl(): string
    {
        $tz = config('app.timezone', 'UTC');
        if ($this->booking->starts_at === null) {
            $mon = Carbon::now($tz)->startOfWeek(CarbonInterface::MONDAY);

            return route('admin.bookings.index', [
                'from' => $mon->toDateString(),
                'to' => $mon->copy()->addDays(6)->toDateString(),
            ]);
        }

        $mon = $this->booking->starts_at->timezone($tz)->startOfWeek(CarbonInterface::MONDAY);

        return route('admin.bookings.index', [
            'from' => $mon->toDateString(),
            'to' => $mon->copy()->addDays(6)->toDateString(),
        ]);
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
        return view('livewire.admin.booking-show');
    }
}
