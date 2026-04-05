<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\BookingHistoryDateRange;
use App\Models\Booking;
use App\Models\CourtClient;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts::admin')]
#[Title('Booking history')]
class BookingHistory extends Component
{
    use BookingHistoryDateRange;

    #[Url]
    public string $venue = '';

    public function mount(): void
    {
        $this->initializeHistoryRangeDefaults();

        if ($this->venue !== '' && ! CourtClient::query()->whereKey($this->venue)->exists()) {
            $this->venue = '';
        }
    }

    public function updatingVenue(): void
    {
        if ($this->venue !== '' && ! CourtClient::query()->whereKey($this->venue)->exists()) {
            $this->venue = '';
        }
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
        $rangeStart = Carbon::parse($this->from, $tz)->startOfDay();
        $rangeEnd = Carbon::parse($this->to, $tz)->endOfDay();

        $query = Booking::query()
            ->with(['courtClient:id,name', 'court:id,name,court_client_id', 'user:id,name,email'])
            ->whereBetween('starts_at', [$rangeStart, $rangeEnd])
            ->orderBy('starts_at');

        if ($this->venue !== '') {
            $query->where('court_client_id', $this->venue);
        }

        /** @var Collection<int, Booking> $bookings */
        $bookings = $query->get();
        $data = $this->bucketBookingsByDayInRange($bookings, $tz);

        $venues = CourtClient::query()->orderBy('name')->get(['id', 'name']);

        return view('livewire.admin.booking-history', [
            'rangeStart' => $data['start'],
            'rangeEnd' => $data['end'],
            'days' => $data['days'],
            'rangeBookingCount' => $bookings->count(),
            'venues' => $venues,
            'maxHistoryRangeDays' => $this->maxHistoryRangeDays,
        ]);
    }
}
