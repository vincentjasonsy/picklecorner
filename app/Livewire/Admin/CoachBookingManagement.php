<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\BookingHistoryDateRange;
use App\Models\Booking;
use App\Models\CourtClient;
use App\Models\User;
use App\Models\UserType;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts::admin')]
#[Title('Coach bookings')]
class CoachBookingManagement extends Component
{
    use BookingHistoryDateRange;

    #[Url]
    public string $venue = '';

    #[Url]
    public string $coach = '';

    public function mount(): void
    {
        $this->initializeHistoryRangeDefaults();

        if ($this->venue !== '' && ! CourtClient::query()->whereKey($this->venue)->exists()) {
            $this->venue = '';
        }

        $this->sanitizeCoachFilter();
    }

    public function updatingVenue(): void
    {
        if ($this->venue !== '' && ! CourtClient::query()->whereKey($this->venue)->exists()) {
            $this->venue = '';
        }
    }

    public function updatedCoach(): void
    {
        $this->sanitizeCoachFilter();
    }

    protected function sanitizeCoachFilter(): void
    {
        if ($this->coach === '') {
            return;
        }

        $valid = User::query()
            ->whereKey($this->coach)
            ->whereHas('userType', fn ($q) => $q->where('slug', UserType::SLUG_COACH))
            ->exists();

        if (! $valid) {
            $this->coach = '';
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
            ->with([
                'courtClient:id,name',
                'court:id,name,court_client_id',
                'user:id,name,email',
                'coach:id,name,email',
            ])
            ->whereNotNull('coach_user_id')
            ->whereBetween('starts_at', [$rangeStart, $rangeEnd])
            ->orderBy('starts_at');

        if ($this->venue !== '') {
            $query->where('court_client_id', $this->venue);
        }

        if ($this->coach !== '') {
            $query->where('coach_user_id', $this->coach);
        }

        /** @var Collection<int, Booking> $bookings */
        $bookings = $query->get();
        $data = $this->bucketBookingsByDayInRange($bookings, $tz);

        $venues = CourtClient::query()->orderBy('name')->get(['id', 'name']);

        $coaches = User::query()
            ->whereHas('userType', fn ($q) => $q->where('slug', UserType::SLUG_COACH))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('livewire.admin.coach-booking-management', [
            'rangeStart' => $data['start'],
            'rangeEnd' => $data['end'],
            'days' => $data['days'],
            'rangeBookingCount' => $bookings->count(),
            'venues' => $venues,
            'coaches' => $coaches,
            'maxHistoryRangeDays' => $this->maxHistoryRangeDays,
        ]);
    }
}
