<?php

namespace App\Livewire\Coach;

use App\Models\CoachHourAvailability;
use App\Models\Court;
use App\Models\CourtClient;
use App\Models\VenueWeeklyHour;
use App\Support\VenueScheduleHours;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::member')]
#[Title('Availability')]
class CoachAvailability extends Component
{
    public string $courtClientId = '';

    public string $availabilityDate = '';

    /** @var list<array{id: string, day_of_week: int, is_closed: bool, opens_at: string, closes_at: string}> */
    public array $scheduleRows = [];

    public function mount(): void
    {
        $tz = config('app.timezone', 'UTC');
        $this->availabilityDate = Carbon::now($tz)->format('Y-m-d');

        $venues = $this->coachedVenues();
        $first = $venues->first();
        if ($first !== null) {
            $this->courtClientId = $first->id;
            $this->loadScheduleForVenue($first->id);
        }
    }

    public function updatedCourtClientId(): void
    {
        $allowed = $this->coachedVenueIds();
        if ($this->courtClientId !== '' && ! in_array($this->courtClientId, $allowed, true)) {
            $this->courtClientId = $allowed[0] ?? '';
        }
        if ($this->courtClientId !== '') {
            $this->loadScheduleForVenue($this->courtClientId);
        } else {
            $this->scheduleRows = [];
        }
    }

    public function updatedAvailabilityDate(): void
    {
        try {
            $this->availabilityDate = Carbon::parse($this->availabilityDate, config('app.timezone', 'UTC'))->format('Y-m-d');
        } catch (\Throwable) {
            $this->availabilityDate = Carbon::now(config('app.timezone', 'UTC'))->format('Y-m-d');
        }
    }

    protected function loadScheduleForVenue(string $courtClientId): void
    {
        $client = CourtClient::query()->with('weeklyHours')->find($courtClientId);
        if ($client === null) {
            $this->scheduleRows = [];

            return;
        }

        $this->scheduleRows = $client->weeklyHours->map(fn (VenueWeeklyHour $r) => [
            'id' => $r->id,
            'day_of_week' => $r->day_of_week,
            'is_closed' => (bool) $r->is_closed,
            'opens_at' => $r->opens_at ?? '09:00',
            'closes_at' => $r->closes_at ?? '21:00',
        ])->values()->all();
    }

    /**
     * @return list<int>
     */
    #[Computed]
    public function slotHoursForDate(): array
    {
        if ($this->courtClientId === '' || $this->scheduleRows === []) {
            return [];
        }

        try {
            $dow = (int) Carbon::parse($this->availabilityDate.' 12:00:00', config('app.timezone', 'UTC'))->format('w');
        } catch (\Throwable) {
            return [];
        }

        return VenueScheduleHours::slotStartHoursForDay($this->scheduleRows, $dow);
    }

    /**
     * Hour is “on” when every court at this venue you coach has that hour marked.
     *
     * @return array<int, true>
     */
    #[Computed]
    public function availableHourLookup(): array
    {
        $courtIds = $this->courtIdsInSelectedVenue();
        if ($courtIds === []) {
            return [];
        }

        $uid = auth()->id();
        $map = [];

        foreach ($this->slotHoursForDate as $h) {
            $all = true;
            foreach ($courtIds as $cid) {
                $exists = CoachHourAvailability::query()
                    ->where('coach_user_id', $uid)
                    ->where('court_id', $cid)
                    ->whereDate('date', $this->availabilityDate)
                    ->where('hour', $h)
                    ->exists();
                if (! $exists) {
                    $all = false;
                    break;
                }
            }
            if ($all) {
                $map[(int) $h] = true;
            }
        }

        return $map;
    }

    #[Computed]
    public function coachedVenues()
    {
        $ids = $this->coachedVenueIds();
        if ($ids === []) {
            return collect();
        }

        return CourtClient::query()
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->get(['id', 'name', 'city']);
    }

    /**
     * @return list<string>
     */
    protected function coachedVenueIds(): array
    {
        $courtIds = $this->coachedCourtIds();
        if ($courtIds === []) {
            return [];
        }

        return Court::query()
            ->whereIn('id', $courtIds)
            ->distinct()
            ->pluck('court_client_id')
            ->all();
    }

    /**
     * Bookable courts at the selected venue that you coach (subset of coach_courts).
     *
     * @return list<string>
     */
    protected function courtIdsInSelectedVenue(): array
    {
        if ($this->courtClientId === '') {
            return [];
        }

        return Court::query()
            ->where('court_client_id', $this->courtClientId)
            ->where('is_available', true)
            ->whereIn('id', $this->coachedCourtIds())
            ->pluck('id')
            ->all();
    }

    /**
     * @return list<string>
     */
    protected function coachedCourtIds(): array
    {
        return auth()->user()->coachedCourts()->pluck('court_id')->all();
    }

    public function toggleHour(int $hour): void
    {
        if ($this->courtClientId === '') {
            return;
        }

        try {
            $dow = (int) Carbon::parse($this->availabilityDate.' 12:00:00', config('app.timezone', 'UTC'))->format('w');
        } catch (\Throwable) {
            return;
        }

        $allowed = VenueScheduleHours::slotStartHoursForDay($this->scheduleRows, $dow);
        if (! in_array($hour, $allowed, true)) {
            return;
        }

        $courtIds = $this->courtIdsInSelectedVenue();
        if ($courtIds === []) {
            return;
        }

        $uid = auth()->id();

        $allSet = true;
        foreach ($courtIds as $cid) {
            if (! CoachHourAvailability::query()
                ->where('coach_user_id', $uid)
                ->where('court_id', $cid)
                ->whereDate('date', $this->availabilityDate)
                ->where('hour', $hour)
                ->exists()) {
                $allSet = false;
                break;
            }
        }

        if ($allSet) {
            CoachHourAvailability::query()
                ->where('coach_user_id', $uid)
                ->whereIn('court_id', $courtIds)
                ->whereDate('date', $this->availabilityDate)
                ->where('hour', $hour)
                ->delete();
        } else {
            foreach ($courtIds as $cid) {
                CoachHourAvailability::query()->firstOrCreate(
                    [
                        'coach_user_id' => $uid,
                        'court_id' => $cid,
                        'date' => $this->availabilityDate,
                        'hour' => $hour,
                    ],
                    [],
                );
            }
        }
    }

    public function render(): View
    {
        return view('livewire.coach.coach-availability');
    }
}
