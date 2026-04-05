<?php

namespace App\Livewire\Venue;

use App\Livewire\Concerns\BookingHistoryDateRange;
use App\Models\Booking;
use App\Models\CourtClient;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::venue-portal')]
#[Title('Booking history')]
class VenueBookingHistory extends Component
{
    use BookingHistoryDateRange;

    public function mount(): void
    {
        abort_unless(auth()->user()?->administeredCourtClient !== null, 403);
        $this->initializeHistoryRangeDefaults();
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
        /** @var CourtClient $client */
        $client = auth()->user()->administeredCourtClient;

        $tz = config('app.timezone', 'UTC');
        $rangeStart = Carbon::parse($this->from, $tz)->startOfDay();
        $rangeEnd = Carbon::parse($this->to, $tz)->endOfDay();

        $query = Booking::query()
            ->with(['court:id,name,court_client_id', 'user:id,name,email'])
            ->where('court_client_id', $client->id)
            ->whereBetween('starts_at', [$rangeStart, $rangeEnd])
            ->orderBy('starts_at');

        /** @var Collection<int, Booking> $bookings */
        $bookings = $query->get();
        $data = $this->bucketBookingsByDayInRange($bookings, $tz);

        return view('livewire.venue.venue-booking-history', [
            'courtClient' => $client,
            'rangeStart' => $data['start'],
            'rangeEnd' => $data['end'],
            'days' => $data['days'],
            'rangeBookingCount' => $bookings->count(),
            'maxHistoryRangeDays' => $this->maxHistoryRangeDays,
        ]);
    }
}
