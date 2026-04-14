<?php

namespace App\Livewire\Coach;

use App\Models\CoachHourAvailability;
use App\Models\CoachProfile;
use App\Models\Court;
use App\Models\CourtClient;
use App\Models\CourtClientClosedDay;
use App\Models\VenueWeeklyHour;
use App\Support\BookingCalendarGrid;
use App\Support\Money;
use App\Support\VenueScheduleHours;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::member')]
#[Title('Schedule & rate')]
class CoachAvailability extends Component
{
    public string $courtClientId = '';

    public string $availabilityDate = '';

    /** @var string Y-m — month shown in the picker calendar (follows {@see}). */
    public string $calendarMonthYm = '';

    /** @var list<array{id: string, day_of_week: int, is_closed: bool, opens_at: string, closes_at: string}> */
    public array $scheduleRows = [];

    public int $hourlyRatePesos = 0;

    public string $currency = 'PHP';

    public function mount(): void
    {
        $tz = config('app.timezone', 'UTC');
        $this->availabilityDate = Carbon::now($tz)->format('Y-m-d');
        $this->calendarMonthYm = Carbon::parse($this->availabilityDate, $tz)->format('Y-m');

        $venues = $this->coachedVenues;
        $first = $venues->first();
        if ($first !== null) {
            $this->courtClientId = $first->id;
            $this->loadScheduleForVenue($first->id);
        }

        $this->syncRateFromProfile();
    }

    protected function syncRateFromProfile(): void
    {
        $p = CoachProfile::query()->firstOrCreate(
            ['user_id' => auth()->id()],
            ['hourly_rate_cents' => 0, 'currency' => 'PHP', 'bio' => null],
        );

        $this->hourlyRatePesos = (int) floor(((int) $p->hourly_rate_cents) / 100);
        $this->currency = $p->currency ?: 'PHP';
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
        $tz = config('app.timezone', 'UTC');
        try {
            $this->availabilityDate = Carbon::parse($this->availabilityDate, $tz)->format('Y-m-d');
        } catch (\Throwable) {
            $this->availabilityDate = Carbon::now($tz)->format('Y-m-d');
        }
        $this->calendarMonthYm = Carbon::parse($this->availabilityDate, $tz)->format('Y-m');
    }

    public function shiftAvailabilityDate(int $days): void
    {
        $tz = config('app.timezone', 'UTC');
        try {
            $d = Carbon::parse($this->availabilityDate, $tz)->addDays($days);
        } catch (\Throwable) {
            $d = Carbon::now($tz)->addDays($days);
        }
        $this->availabilityDate = $d->format('Y-m-d');
        $this->calendarMonthYm = $d->format('Y-m');
    }

    public function goToToday(): void
    {
        $tz = config('app.timezone', 'UTC');
        $this->availabilityDate = Carbon::now($tz)->format('Y-m-d');
        $this->calendarMonthYm = Carbon::parse($this->availabilityDate, $tz)->format('Y-m');
    }

    public function shiftCalendarMonth(int $deltaMonths): void
    {
        $tz = config('app.timezone', 'UTC');
        try {
            $anchor = Carbon::createFromFormat('Y-m', $this->calendarMonthYm, $tz)
                ->startOfMonth()
                ->addMonths($deltaMonths);
        } catch (\Throwable) {
            $anchor = Carbon::now($tz)->startOfMonth()->addMonths($deltaMonths);
        }
        $this->calendarMonthYm = $anchor->format('Y-m');
    }

    public function pickCalendarDay(string $ymd): void
    {
        $tz = config('app.timezone', 'UTC');
        try {
            $d = Carbon::parse($ymd, $tz)->format('Y-m-d');
        } catch (\Throwable) {
            return;
        }
        $this->availabilityDate = $d;
        $this->calendarMonthYm = Carbon::parse($d, $tz)->format('Y-m');
    }

    public function saveCoachRate(): void
    {
        $validated = $this->validate([
            'hourlyRatePesos' => ['required', 'integer', 'min:0', 'max:500000'],
            'currency' => ['required', 'string', 'size:3'],
        ]);

        $cents = $validated['hourlyRatePesos'] * 100;

        CoachProfile::query()->updateOrCreate(
            ['user_id' => auth()->id()],
            [
                'hourly_rate_cents' => $cents,
                'currency' => strtoupper($validated['currency']),
            ],
        );

        $this->syncRateFromProfile();

        session()->flash('status', 'Coaching rate saved.');
    }

    protected function normalizedAvailabilityDate(): ?string
    {
        try {
            return Carbon::parse($this->availabilityDate, config('app.timezone', 'UTC'))->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    public function availabilityDateLabel(): string
    {
        try {
            return Carbon::parse($this->availabilityDate, config('app.timezone', 'UTC'))->isoFormat('dddd, MMM D, YYYY');
        } catch (\Throwable) {
            return '';
        }
    }

    public function selectedDayOfWeek(): int
    {
        try {
            return (int) Carbon::parse($this->availabilityDate.' 12:00:00', config('app.timezone', 'UTC'))->format('w');
        } catch (\Throwable) {
            return (int) Carbon::now(config('app.timezone', 'UTC'))->format('w');
        }
    }

    public function isSelectedDateVenueClosure(): bool
    {
        $d = $this->normalizedAvailabilityDate();
        if ($d === null || $this->courtClientId === '') {
            return false;
        }

        $client = CourtClient::query()->find($this->courtClientId);

        return $client !== null && $client->isClosedOnDate($d);
    }

    /**
     * @return list<list<array{date: Carbon, in_month: bool, bookings: Collection}>>
     */
    protected function buildCalendarMonthWeeks(): array
    {
        $tz = config('app.timezone', 'UTC');
        try {
            $monthStart = Carbon::createFromFormat('Y-m', $this->calendarMonthYm, $tz)->startOfMonth();
        } catch (\Throwable) {
            $monthStart = Carbon::now($tz)->startOfMonth();
        }

        return BookingCalendarGrid::build($monthStart, $tz, collect())['weeks'];
    }

    protected function buildCalendarMonthLabel(): string
    {
        $tz = config('app.timezone', 'UTC');
        try {
            $monthStart = Carbon::createFromFormat('Y-m', $this->calendarMonthYm, $tz)->startOfMonth();
        } catch (\Throwable) {
            $monthStart = Carbon::now($tz)->startOfMonth();
        }

        return $monthStart->copy()->timezone($tz)->isoFormat('MMMM YYYY');
    }

    /**
     * @return array<string, true>
     */
    protected function buildVenueClosureLookupForCalendarMonth(): array
    {
        if ($this->courtClientId === '') {
            return [];
        }

        $tz = config('app.timezone', 'UTC');
        try {
            $monthStart = Carbon::createFromFormat('Y-m', $this->calendarMonthYm, $tz)->startOfMonth();
        } catch (\Throwable) {
            $monthStart = Carbon::now($tz)->startOfMonth();
        }

        [$gridStart, $gridEnd] = BookingCalendarGrid::visibleGridBounds($monthStart, $tz);
        $rows = CourtClientClosedDay::query()
            ->where('court_client_id', $this->courtClientId)
            ->whereDate('closed_on', '>=', $gridStart->toDateString())
            ->whereDate('closed_on', '<=', $gridEnd->toDateString())
            ->pluck('closed_on');

        $map = [];
        foreach ($rows as $value) {
            $key = $value instanceof Carbon ? $value->format('Y-m-d') : Carbon::parse((string) $value, $tz)->format('Y-m-d');
            $map[$key] = true;
        }

        return $map;
    }

    /**
     * Dates (Y-m-d) where you have at least one hour marked at this venue.
     *
     * @return array<string, true>
     */
    protected function buildCoachMarkedDaysLookupForCalendarMonth(): array
    {
        if ($this->courtClientId === '') {
            return [];
        }

        $courtIds = $this->courtIdsInSelectedVenue();
        if ($courtIds === []) {
            return [];
        }

        $tz = config('app.timezone', 'UTC');
        try {
            $monthStart = Carbon::createFromFormat('Y-m', $this->calendarMonthYm, $tz)->startOfMonth();
        } catch (\Throwable) {
            $monthStart = Carbon::now($tz)->startOfMonth();
        }

        [$gridStart, $gridEnd] = BookingCalendarGrid::visibleGridBounds($monthStart, $tz);
        $uid = auth()->id();

        $dates = CoachHourAvailability::query()
            ->where('coach_user_id', $uid)
            ->whereIn('court_id', $courtIds)
            ->whereDate('date', '>=', $gridStart->toDateString())
            ->whereDate('date', '<=', $gridEnd->toDateString())
            ->distinct()
            ->pluck('date');

        $map = [];
        foreach ($dates as $value) {
            $key = $value instanceof Carbon ? $value->format('Y-m-d') : Carbon::parse((string) $value, $tz)->format('Y-m-d');
            $map[$key] = true;
        }

        return $map;
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

        $date = $this->normalizedAvailabilityDate();
        if ($date !== null) {
            $client = CourtClient::query()->find($this->courtClientId);
            if ($client !== null && $client->isClosedOnDate($date)) {
                return [];
            }
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

        if ($this->isSelectedDateVenueClosure()) {
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

        unset($this->availableHourLookup);
    }

    public function clearHoursForSelectedDate(): void
    {
        if ($this->courtClientId === '' || $this->isSelectedDateVenueClosure()) {
            return;
        }

        $courtIds = $this->courtIdsInSelectedVenue();
        if ($courtIds === []) {
            return;
        }

        $date = $this->normalizedAvailabilityDate();
        if ($date === null) {
            return;
        }

        CoachHourAvailability::query()
            ->where('coach_user_id', auth()->id())
            ->whereIn('court_id', $courtIds)
            ->whereDate('date', $date)
            ->delete();

        unset($this->availableHourLookup);
    }

    public function render(): View
    {
        $tz = config('app.timezone', 'UTC');
        $ratePreviewCents = max(0, $this->hourlyRatePesos) * 100;

        return view('livewire.coach.coach-availability', [
            'calendarTz' => $tz,
            'calendarMonthWeeks' => $this->buildCalendarMonthWeeks(),
            'calendarMonthLabel' => $this->buildCalendarMonthLabel(),
            'venueClosureLookup' => $this->buildVenueClosureLookupForCalendarMonth(),
            'coachMarkedDaysLookup' => $this->buildCoachMarkedDaysLookupForCalendarMonth(),
            'ratePreviewFormatted' => Money::formatMinor($ratePreviewCents, strtoupper($this->currency) ?: 'PHP'),
            'dayLabels' => VenueWeeklyHour::DAY_LABELS,
        ]);
    }
}
