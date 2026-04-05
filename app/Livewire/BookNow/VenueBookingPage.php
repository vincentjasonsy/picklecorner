<?php

namespace App\Livewire\BookNow;

use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtClient;
use App\Models\CourtDateSlotBlock;
use App\Models\UserType;
use App\Models\VenueWeeklyHour;
use App\Services\CourtSlotPricing;
use App\Services\PublicVenueBookingSubmission;
use App\Support\VenueScheduleHours;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

class VenueBookingPage extends Component
{
    use WithFileUploads;

    public const DRAFT_SESSION_KEY = 'venue_booking_draft';

    public const AFTER_LOGIN_SESSION_KEY = 'venue_booking_after_login';

    public CourtClient $courtClient;

    /** @var list<array{id: string, day_of_week: int, is_closed: bool, opens_at: string, closes_at: string}> */
    public array $scheduleRows = [];

    public string $bookingCalendarDate = '';

    /** @var list<string> courtId-hour */
    public array $selectedSlots = [];

    /** times | review */
    public string $step = 'times';

    public string $bookingNotes = '';

    public string $paymentMethod = Booking::PAYMENT_GCASH;

    public string $paymentReference = '';

    /** @var mixed */
    public $paymentProof = null;

    public function mount(CourtClient $courtClient): void
    {
        abort_unless($courtClient->is_active, 404);

        $this->courtClient = $courtClient->load(['courts', 'weeklyHours']);
        $this->ensureDefaultWeeklyHours();
        $this->courtClient->refresh();
        $this->courtClient->load(['courts', 'weeklyHours']);
        $this->syncScheduleRowsFromDatabase();
        $this->bookingCalendarDate = Carbon::now(config('app.timezone', 'UTC'))->format('Y-m-d');

        if (session()->pull(self::AFTER_LOGIN_SESSION_KEY, false)) {
            $draft = session()->get(self::DRAFT_SESSION_KEY);
            if (is_array($draft) && ($draft['court_client_id'] ?? '') === $this->courtClient->id) {
                $this->hydrateFromDraft($draft);
                session()->forget(self::DRAFT_SESSION_KEY);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $draft
     */
    protected function hydrateFromDraft(array $draft): void
    {
        $this->bookingCalendarDate = is_string($draft['booking_calendar_date'] ?? null)
            ? $draft['booking_calendar_date']
            : $this->bookingCalendarDate;
        $slots = $draft['selected_slots'] ?? [];
        $this->selectedSlots = is_array($slots)
            ? array_values(array_filter(array_map('strval', $slots)))
            : [];
        $this->bookingNotes = is_string($draft['booking_notes'] ?? null) ? $draft['booking_notes'] : '';
        if (is_string($draft['payment_method'] ?? null)) {
            $this->paymentMethod = $draft['payment_method'];
        }
        if (is_string($draft['payment_reference'] ?? null)) {
            $this->paymentReference = $draft['payment_reference'];
        }
        $this->step = ($draft['step'] ?? 'review') === 'times' ? 'times' : 'review';
    }

    protected function persistDraftForAuthReturn(): void
    {
        session()->put(self::DRAFT_SESSION_KEY, [
            'court_client_id' => $this->courtClient->id,
            'booking_calendar_date' => $this->bookingCalendarDate,
            'selected_slots' => $this->selectedSlots,
            'booking_notes' => $this->bookingNotes,
            'payment_method' => $this->paymentMethod,
            'payment_reference' => $this->paymentReference,
            'step' => 'review',
        ]);
        session()->put(self::AFTER_LOGIN_SESSION_KEY, true);
        session()->put('url.intended', route('book-now.venue.book', $this->courtClient));
    }

    public function backUrl(): string
    {
        return request()->routeIs('account.book.venue')
            ? route('account.book')
            : route('book-now');
    }

    public function canSubmitBookings(): bool
    {
        $u = auth()->user();
        if ($u === null) {
            return false;
        }
        if ($u->isSuperAdmin()) {
            return true;
        }
        $slug = $u->userType?->slug;

        return in_array($slug, [UserType::SLUG_USER, UserType::SLUG_COACH], true);
    }

    protected function ensureDefaultWeeklyHours(): void
    {
        if ($this->courtClient->weeklyHours()->count() > 0) {
            return;
        }

        for ($d = 0; $d < 7; $d++) {
            VenueWeeklyHour::query()->create([
                'court_client_id' => $this->courtClient->id,
                'day_of_week' => $d,
                'is_closed' => false,
                'opens_at' => '07:00',
                'closes_at' => '22:00',
            ]);
        }
    }

    protected function syncScheduleRowsFromDatabase(): void
    {
        $this->scheduleRows = $this->courtClient->weeklyHours->map(fn (VenueWeeklyHour $r) => [
            'id' => $r->id,
            'day_of_week' => $r->day_of_week,
            'is_closed' => (bool) $r->is_closed,
            'opens_at' => $r->opens_at ?? '09:00',
            'closes_at' => $r->closes_at ?? '21:00',
        ])->values()->all();
    }

    public function updatedBookingCalendarDate(): void
    {
        try {
            $this->bookingCalendarDate = Carbon::parse($this->bookingCalendarDate, config('app.timezone', 'UTC'))->format('Y-m-d');
        } catch (\Throwable) {
            $this->bookingCalendarDate = Carbon::now(config('app.timezone', 'UTC'))->format('Y-m-d');
        }
        $this->selectedSlots = [];
    }

    public function shiftBookingDate(int $days): void
    {
        try {
            $d = Carbon::parse($this->bookingCalendarDate, config('app.timezone', 'UTC'))->addDays($days);
        } catch (\Throwable) {
            $d = Carbon::now(config('app.timezone', 'UTC'))->addDays($days);
        }
        $this->bookingCalendarDate = $d->format('Y-m-d');
        $this->selectedSlots = [];
    }

    public function bookingDayOfWeek(): int
    {
        try {
            return (int) Carbon::parse($this->bookingCalendarDate.' 12:00:00', config('app.timezone', 'UTC'))->format('w');
        } catch (\Throwable) {
            return (int) Carbon::now(config('app.timezone', 'UTC'))->format('w');
        }
    }

    /**
     * @return list<int>
     */
    public function slotHoursForSelectedDate(): array
    {
        return VenueScheduleHours::slotStartHoursForDay($this->scheduleRows, $this->bookingDayOfWeek());
    }

    public function bookingCalendarDateLabel(): string
    {
        try {
            return Carbon::parse($this->bookingCalendarDate, config('app.timezone', 'UTC'))->isoFormat('dddd, MMM D, YYYY');
        } catch (\Throwable) {
            return '';
        }
    }

    protected function normalizedBookingCalendarDate(): ?string
    {
        try {
            return Carbon::parse($this->bookingCalendarDate, config('app.timezone', 'UTC'))->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    public function courtsOrderedForGrid(): Collection
    {
        return Court::orderedForGridColumns(
            $this->courtClient->courts()
                ->where('is_available', true)
                ->with(['timeSlotSettings', 'timeSlotBlocks'])
                ->get(),
        );
    }

    /**
     * @return array<string, true>
     */
    #[Computed]
    public function dateBlockLookup(): array
    {
        $courts = $this->courtsOrderedForGrid();
        if ($courts->isEmpty()) {
            return [];
        }

        $date = $this->normalizedBookingCalendarDate();
        if ($date === null) {
            return [];
        }

        $ids = $courts->pluck('id')->all();
        $rows = CourtDateSlotBlock::query()
            ->whereIn('court_id', $ids)
            ->whereDate('blocked_date', $date)
            ->get(['court_id', 'slot_start_hour']);

        $map = [];
        foreach ($rows as $r) {
            $map[$r->court_id.'-'.$r->slot_start_hour] = true;
        }

        return $map;
    }

    /**
     * @return array<string, array{name: string, status: string, booking_id: string}>
     */
    #[Computed]
    public function occupancyBySlot(): array
    {
        $courts = $this->courtsOrderedForGrid();
        if ($courts->isEmpty()) {
            return [];
        }

        $date = $this->normalizedBookingCalendarDate();
        if ($date === null) {
            return [];
        }

        $hours = $this->slotHoursForSelectedDate();
        if ($hours === []) {
            return [];
        }

        $tz = config('app.timezone', 'UTC');
        $dayStart = Carbon::parse($date.' 00:00:00', $tz);
        $dayEnd = Carbon::parse($date.' 23:59:59', $tz);
        $courtIds = $courts->pluck('id')->all();

        $bookings = Booking::query()
            ->where('court_client_id', $this->courtClient->id)
            ->whereIn('court_id', $courtIds)
            ->whereIn('status', Booking::statusesBlockingCourtAvailability())
            ->where('starts_at', '<', $dayEnd)
            ->where('ends_at', '>', $dayStart)
            ->with(['user'])
            ->get();

        $map = [];
        foreach ($courts as $court) {
            foreach ($hours as $h) {
                $slotStart = Carbon::parse($date.' '.sprintf('%02d:00:00', $h), $tz);
                $slotEnd = $slotStart->copy()->addHour();
                foreach ($bookings as $b) {
                    if ($b->court_id !== $court->id) {
                        continue;
                    }
                    if ($b->starts_at < $slotEnd && $b->ends_at > $slotStart) {
                        $map[$court->id.'-'.$h] = [
                            'name' => $b->user?->name ?? 'Guest',
                            'status' => $this->occupancyStatusLabel($b->status),
                            'booking_id' => $b->id,
                        ];

                        break;
                    }
                }
            }
        }

        return $map;
    }

    protected function occupancyStatusLabel(string $status): string
    {
        return match ($status) {
            Booking::STATUS_PENDING_APPROVAL => 'Pending',
            Booking::STATUS_CONFIRMED => 'Confirmed',
            Booking::STATUS_COMPLETED => 'Completed',
            default => $status,
        };
    }

    public function isSlotSelected(string $courtId, int $hour): bool
    {
        return in_array($courtId.'-'.$hour, $this->selectedSlots, true);
    }

    public function toggleSlot(string $courtId, int $hour): void
    {
        $allowedHours = $this->slotHoursForSelectedDate();
        if (! in_array($hour, $allowedHours, true)) {
            return;
        }

        $court = Court::query()
            ->where('id', $courtId)
            ->where('court_client_id', $this->courtClient->id)
            ->where('is_available', true)
            ->first();
        if (! $court) {
            return;
        }

        $key = $courtId.'-'.$hour;
        $occupancy = $this->occupancyBySlot;
        if (isset($occupancy[$key])) {
            return;
        }

        $selected = $this->selectedSlots;
        $idx = array_search($key, $selected, true);
        if ($idx !== false) {
            unset($selected[$idx]);
            $this->selectedSlots = array_values($selected);

            return;
        }

        $hoursThisCourt = [];
        foreach ($selected as $k) {
            if (str_starts_with($k, $courtId.'-')) {
                $h = (int) substr($k, strlen($courtId) + 1);
                $hoursThisCourt[] = $h;
            }
        }
        if (count($hoursThisCourt) >= 16) {
            return;
        }
        if (count($selected) >= 64) {
            return;
        }

        $selected[] = $key;
        $this->selectedSlots = array_values($selected);
    }

    public function clearSlotSelection(): void
    {
        $this->selectedSlots = [];
    }

    /**
     * @return array<string, list<int>>
     */
    protected function selectedSlotsGroupedByCourt(): array
    {
        $by = [];
        foreach ($this->selectedSlots as $key) {
            if (! preg_match('/^(.*)-(\d+)$/', $key, $m)) {
                continue;
            }
            $cid = $m[1];
            $h = (int) $m[2];
            if (! isset($by[$cid])) {
                $by[$cid] = [];
            }
            $by[$cid][] = $h;
        }
        foreach ($by as $cid => $hours) {
            $hours = array_values(array_unique(array_map('intval', $hours)));
            sort($hours);
            $by[$cid] = $hours;
        }

        return $by;
    }

    /**
     * @param  list<int>  $sortedUnique
     * @return list<list<int>>
     */
    protected function contiguousHourRuns(array $sortedUnique): array
    {
        if ($sortedUnique === []) {
            return [];
        }
        $runs = [];
        $run = [$sortedUnique[0]];
        for ($i = 1, $n = count($sortedUnique); $i < $n; $i++) {
            if ($sortedUnique[$i] === $sortedUnique[$i - 1] + 1) {
                $run[] = $sortedUnique[$i];
            } else {
                $runs[] = $run;
                $run = [$sortedUnique[$i]];
            }
        }
        $runs[] = $run;

        return $runs;
    }

    public function slotHourLabel(int $hour): string
    {
        return Carbon::createFromTime($hour, 0, 0)->format('g:i A');
    }

    protected function bookingOverlapsCourt(string $courtId, Carbon $starts, Carbon $ends): bool
    {
        return Booking::query()
            ->where('court_id', $courtId)
            ->whereNotIn('status', [Booking::STATUS_CANCELLED, Booking::STATUS_DENIED])
            ->where('starts_at', '<', $ends)
            ->where('ends_at', '>', $starts)
            ->exists();
    }

    public function goToReview(): void
    {
        $this->resetErrorBag('selectedSlots');
        $byCourt = $this->selectedSlotsGroupedByCourt();
        if ($byCourt === []) {
            $this->addError('selectedSlots', 'Select at least one open time slot on the grid.');

            return;
        }

        $this->step = 'review';
    }

    public function backToTimes(): void
    {
        $this->step = 'times';
    }

    public function continueToSignIn(): void
    {
        if ($this->step === 'times') {
            $this->goToReview();
            if ($this->step !== 'review') {
                return;
            }
        } elseif ($this->selectedSlots === []) {
            $this->addError('selectedSlots', 'Select at least one open time slot on the grid.');

            return;
        }

        $this->persistDraftForAuthReturn();

        $this->redirect(route('login'), navigate: true);
    }

    public function continueToRegister(): void
    {
        if ($this->step === 'times') {
            $this->goToReview();
            if ($this->step !== 'review') {
                return;
            }
        } elseif ($this->selectedSlots === []) {
            $this->addError('selectedSlots', 'Select at least one open time slot on the grid.');

            return;
        }

        $this->persistDraftForAuthReturn();

        $this->redirect(route('register'), navigate: true);
    }

    /**
     * @return list<array{court: Court, starts: Carbon, ends: Carbon, gross_cents: int, hours: list<int>}>
     */
    public function buildSpecsForSubmit(): array
    {
        $byCourt = $this->selectedSlotsGroupedByCourt();
        $allowed = $this->slotHoursForSelectedDate();
        $date = $this->normalizedBookingCalendarDate();
        if ($date === null) {
            return [];
        }
        $tz = config('app.timezone', 'UTC');
        $specs = [];

        foreach ($byCourt as $courtId => $hours) {
            $court = Court::query()
                ->with(['courtClient', 'timeSlotSettings'])
                ->where('id', $courtId)
                ->where('court_client_id', $this->courtClient->id)
                ->first();
            if (! $court) {
                return [];
            }

            foreach ($hours as $h) {
                if (! in_array($h, $allowed, true)) {
                    return [];
                }
            }

            foreach ($this->contiguousHourRuns($hours) as $run) {
                if ($run === []) {
                    continue;
                }
                $firstHour = $run[0];
                $lastHour = $run[count($run) - 1];
                $starts = Carbon::parse($date.' '.sprintf('%02d:00:00', $firstHour), $tz);
                $ends = Carbon::parse($date.' '.sprintf('%02d:00:00', $lastHour), $tz)->addHour();

                if ($this->bookingOverlapsCourt($court->id, $starts, $ends)) {
                    return [];
                }

                $grossCents = 0;
                foreach ($run as $h) {
                    $slotStart = Carbon::parse($date.' '.sprintf('%02d:00:00', $h), $tz);
                    $hourly = CourtSlotPricing::estimatedHourlyCentsAtStart($court, $slotStart)
                        ?? $court->courtClient?->hourly_rate_cents
                        ?? 0;
                    $grossCents += $hourly;
                }
                $grossCents = (int) round($grossCents);

                $specs[] = [
                    'court' => $court,
                    'starts' => $starts,
                    'ends' => $ends,
                    'gross_cents' => $grossCents,
                    'hours' => $run,
                ];
            }
        }

        return $specs;
    }

    #[Computed]
    public function reviewEstimateCents(): int
    {
        return (int) array_sum(array_column($this->buildSpecsForSubmit(), 'gross_cents'));
    }

    public function submitRequest(): void
    {
        if (! auth()->check()) {
            $this->addError('submit', 'Please sign in to complete your request.');

            return;
        }

        if (! $this->canSubmitBookings()) {
            $this->addError('submit', 'Only player and coach accounts can submit booking requests here. Staff should use the venue or desk app.');

            return;
        }

        $this->step = 'review';

        $rules = [
            'bookingNotes' => ['nullable', 'string', 'max:2000'],
            'paymentMethod' => ['nullable', 'string', Rule::in(Booking::paymentMethodOptions())],
            'paymentReference' => ['nullable', 'string', 'max:128'],
            'paymentProof' => ['nullable', 'image', 'max:5120'],
        ];
        $this->validate($rules, [], [
            'paymentReference' => 'payment reference',
        ]);

        $specs = $this->buildSpecsForSubmit();
        if ($specs === []) {
            $this->addError('selectedSlots', 'Your selection is no longer available. Adjust the grid and try again.');

            return;
        }

        $booker = auth()->user();
        if ($booker === null) {
            return;
        }

        try {
            $result = PublicVenueBookingSubmission::submit(
                $this->courtClient,
                $booker,
                $specs,
                $this->bookingNotes !== '' ? $this->bookingNotes : null,
                $this->paymentMethod,
                $this->paymentReference,
                $this->paymentProof,
            );
        } catch (\InvalidArgumentException $e) {
            $this->addError('submit', $e->getMessage());

            return;
        }

        session()->forget(self::DRAFT_SESSION_KEY);
        session()->forget(self::AFTER_LOGIN_SESSION_KEY);

        $bookings = $result['bookings'];
        $deskPolicy = $result['desk_policy'];

        $this->selectedSlots = [];
        $this->bookingNotes = '';
        $this->paymentProof = null;
        $this->paymentReference = '';
        $this->paymentMethod = Booking::PAYMENT_GCASH;
        $this->step = 'times';

        $flash = match ($deskPolicy) {
            CourtClient::DESK_BOOKING_POLICY_AUTO_APPROVE => count($bookings) === 1
                ? 'Booking confirmed automatically (venue setting).'
                : count($bookings).' bookings confirmed automatically (venue setting).',
            CourtClient::DESK_BOOKING_POLICY_AUTO_DENY => count($bookings) === 1
                ? 'Booking was not accepted (venue auto-deny setting).'
                : 'Bookings were not accepted (venue auto-deny setting).',
            default => count($bookings) === 1
                ? 'Request sent. The venue will review your booking.'
                : count($bookings).' requests sent. The venue will review your bookings.',
        };

        session()->flash('status', $flash);

        $this->redirect($this->backUrl(), navigate: true);
    }

    public function render(): View
    {
        $layout = request()->routeIs('account.book.venue') ? 'layouts::member' : 'layouts::guest';

        return view('livewire.book-now.venue-booking-page', [
            'dayLabels' => VenueWeeklyHour::DAY_LABELS,
        ])
            ->layout($layout)
            ->title('Book · '.$this->courtClient->name);
    }
}
