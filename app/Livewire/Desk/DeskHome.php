<?php

namespace App\Livewire\Desk;

use App\Models\Booking;
use App\Models\BookingChangeRequest;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts::desk-portal')]
#[Title('Front desk')]
class DeskHome extends Component
{
    #[Url(as: 'day', except: '')]
    public string $dailyViewDate = '';

    public function mount(): void
    {
        if ($this->dailyViewDate === '') {
            $this->dailyViewDate = Carbon::now(config('app.timezone', 'UTC'))->format('Y-m-d');
        }
        $this->sanitizeDailyViewDate();
    }

    #[Computed]
    public function courtClient()
    {
        return auth()->user()->deskCourtClient?->loadCount('courts');
    }

    #[Computed]
    public function pendingMySubmissions(): int
    {
        $c = $this->courtClient;
        if (! $c) {
            return 0;
        }

        return Booking::query()
            ->where('court_client_id', $c->id)
            ->where('desk_submitted_by', auth()->id())
            ->where('status', Booking::STATUS_PENDING_APPROVAL)
            ->count();
    }

    #[Computed]
    public function pendingMemberChangeRequests(): int
    {
        $c = $this->courtClient;
        if (! $c) {
            return 0;
        }

        return BookingChangeRequest::query()
            ->where('court_client_id', $c->id)
            ->where('status', BookingChangeRequest::STATUS_PENDING)
            ->count();
    }

    /**
     * All venue bookings that start on the selected calendar day (venue timezone).
     *
     * @return Collection<int, Booking>
     */
    #[Computed]
    public function dailyBookings(): Collection
    {
        $c = $this->courtClient;
        if (! $c) {
            return collect();
        }

        $tz = config('app.timezone', 'UTC');
        try {
            $day = Carbon::createFromFormat('Y-m-d', $this->dailyViewDate, $tz)->startOfDay();
        } catch (\Throwable) {
            $day = Carbon::now($tz)->startOfDay();
        }
        $next = $day->copy()->addDay();

        return Booking::query()
            ->with(['court:id,name', 'user:id,name,email'])
            ->where('court_client_id', $c->id)
            ->where('starts_at', '>=', $day)
            ->where('starts_at', '<', $next)
            ->orderBy('starts_at')
            ->get();
    }

    public function updatedDailyViewDate(): void
    {
        $this->sanitizeDailyViewDate();
    }

    public function shiftDaily(int $deltaDays): void
    {
        $tz = config('app.timezone', 'UTC');
        try {
            $day = Carbon::createFromFormat('Y-m-d', $this->dailyViewDate, $tz)->addDays($deltaDays);
        } catch (\Throwable) {
            $day = Carbon::now($tz);
        }
        $this->dailyViewDate = $day->format('Y-m-d');
        $this->sanitizeDailyViewDate();
    }

    public function goToToday(): void
    {
        $this->dailyViewDate = Carbon::now(config('app.timezone', 'UTC'))->format('Y-m-d');
        $this->sanitizeDailyViewDate();
    }

    protected function sanitizeDailyViewDate(): void
    {
        $tz = config('app.timezone', 'UTC');
        try {
            Carbon::createFromFormat('Y-m-d', $this->dailyViewDate, $tz);
        } catch (\Throwable) {
            $this->dailyViewDate = Carbon::now($tz)->format('Y-m-d');
        }
    }

    public function dailyViewLabel(): string
    {
        $tz = config('app.timezone', 'UTC');
        try {
            return Carbon::createFromFormat('Y-m-d', $this->dailyViewDate, $tz)
                ->isoFormat('dddd, MMM D, YYYY');
        } catch (\Throwable) {
            return '';
        }
    }

    public function render()
    {
        return view('livewire.desk.desk-home');
    }
}
