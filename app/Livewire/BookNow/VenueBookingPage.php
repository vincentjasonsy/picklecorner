<?php

namespace App\Livewire\BookNow;

use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtClient;
use App\Models\CourtDateSlotBlock;
use App\Models\GiftCard;
use App\Models\User;
use App\Models\UserType;
use App\Models\VenueWeeklyHour;
use App\Services\CoachAvailabilityService;
use App\Services\CourtSlotPricing;
use App\Services\GiftCardService;
use App\Services\PublicVenueBookingSubmission;
use App\Support\Money;
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

    public string $paymentMethod = Booking::PAYMENT_GCASH;

    public string $paymentReference = '';

    public string $giftCardCode = '';

    /** Optional coach for this request (single court only; court + coach booked together). */
    public string $coachUserId = '';

    /** Billable coach hours (you choose; max = selected slot hours). Only used when {@see $coachUserId} is set. */
    public int $coachPaidHours = 0;

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
        if (is_string($draft['payment_method'] ?? null)) {
            $this->paymentMethod = $draft['payment_method'];
        }
        if (is_string($draft['payment_reference'] ?? null)) {
            $this->paymentReference = $draft['payment_reference'];
        }
        if (is_string($draft['gift_card_code'] ?? null)) {
            $this->giftCardCode = $draft['gift_card_code'];
        }
        if (is_string($draft['coach_user_id'] ?? null)) {
            $this->coachUserId = $draft['coach_user_id'];
        }
        if (isset($draft['coach_paid_hours'])) {
            $this->coachPaidHours = max(0, (int) $draft['coach_paid_hours']);
        }
        $this->step = ($draft['step'] ?? 'review') === 'times' ? 'times' : 'review';
    }

    protected function persistDraftForAuthReturn(): void
    {
        session()->put(self::DRAFT_SESSION_KEY, [
            'court_client_id' => $this->courtClient->id,
            'booking_calendar_date' => $this->bookingCalendarDate,
            'selected_slots' => $this->selectedSlots,
            'payment_method' => $this->paymentMethod,
            'payment_reference' => $this->paymentReference,
            'gift_card_code' => $this->giftCardCode,
            'coach_user_id' => $this->coachUserId,
            'coach_paid_hours' => $this->coachPaidHours,
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
        $this->coachUserId = '';
        $this->coachPaidHours = 0;
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
        $this->coachUserId = '';
        $this->coachPaidHours = 0;
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
        if ($this->coachUserId !== '' && count(CoachAvailabilityService::groupSlotsByCourt($this->selectedSlots)) > 1) {
            $this->coachUserId = '';
            $this->coachPaidHours = 0;
        }
        $this->clampCoachPaidHours();
    }

    public function updatedCoachUserId(?string $value): void
    {
        if ($value === null || $value === '') {
            $this->coachPaidHours = 0;

            return;
        }

        $max = $this->totalSelectedSlotHours();
        if ($max < 1) {
            $this->coachPaidHours = 0;

            return;
        }

        if ($this->coachPaidHours < 1) {
            $this->coachPaidHours = $max;
        }
        $this->coachPaidHours = min($max, max(1, $this->coachPaidHours));
    }

    public function updatedCoachPaidHours(mixed $value): void
    {
        $this->clampCoachPaidHours((int) $value);
    }

    protected function clampCoachPaidHours(?int $preferred = null): void
    {
        if ($this->coachUserId === '') {
            $this->coachPaidHours = 0;

            return;
        }

        $max = $this->totalSelectedSlotHours();
        if ($max < 1) {
            $this->coachPaidHours = 0;

            return;
        }

        $v = $preferred ?? $this->coachPaidHours;
        if ($v < 1) {
            $v = $max;
        }
        $this->coachPaidHours = min($max, max(1, $v));
    }

    /**
     * Number of one-hour slots selected (all courts). Used as max coach-paid hours.
     */
    public function totalSelectedSlotHours(): int
    {
        $n = 0;
        foreach ($this->selectedSlotsGroupedByCourt() as $hours) {
            $n += count($hours);
        }

        return $n;
    }

    public function clearSlotSelection(): void
    {
        $this->selectedSlots = [];
        $this->coachUserId = '';
        $this->coachPaidHours = 0;
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

        $this->clampCoachPaidHours();
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
     * @return list<array{starts: Carbon, ends: Carbon}>
     */
    protected function selectedTimeWindows(): array
    {
        $byCourt = $this->selectedSlotsGroupedByCourt();
        $date = $this->normalizedBookingCalendarDate();
        if ($date === null) {
            return [];
        }
        $tz = config('app.timezone', 'UTC');
        $windows = [];
        foreach ($byCourt as $hours) {
            foreach ($this->contiguousHourRuns($hours) as $run) {
                if ($run === []) {
                    continue;
                }
                $firstHour = $run[0];
                $lastHour = $run[count($run) - 1];
                $starts = Carbon::parse($date.' '.sprintf('%02d:00:00', $firstHour), $tz);
                $ends = Carbon::parse($date.' '.sprintf('%02d:00:00', $lastHour), $tz)->addHour();
                $windows[] = ['starts' => $starts, 'ends' => $ends];
            }
        }

        return $windows;
    }

    #[Computed]
    public function availableCoachesForReview(): Collection
    {
        $date = $this->normalizedBookingCalendarDate();
        if ($date === null || $this->selectedSlots === []) {
            return collect();
        }
        $windows = $this->selectedTimeWindows();
        if ($windows === []) {
            return collect();
        }

        return CoachAvailabilityService::availableCoaches(
            $this->courtClient,
            $date,
            $this->selectedSlots,
            $windows,
        );
    }

    /**
     * @return list<array{court: Court, starts: Carbon, ends: Carbon, gross_cents: int, court_gross_cents: int, hours: list<int>, coach_fee_cents: int}>
     */
    public function buildSpecsForSubmit(): array
    {
        $byCourt = $this->selectedSlotsGroupedByCourt();
        $allowed = $this->slotHoursForSelectedDate();
        $date = $this->normalizedBookingCalendarDate();
        if ($date === null) {
            return [];
        }

        $coachUser = null;
        if ($this->coachUserId !== '') {
            if (count($byCourt) !== 1) {
                return [];
            }
            $coachUser = User::query()->with('coachProfile')->find($this->coachUserId);
            if (! $coachUser?->isCoach()) {
                return [];
            }
            if (! $this->availableCoachesForReview->contains('id', $this->coachUserId)) {
                return [];
            }
        }

        $tz = config('app.timezone', 'UTC');
        $specs = [];

        $rate = (int) ($coachUser?->coachProfile?->hourly_rate_cents ?? 0);
        $maxBillable = $this->totalSelectedSlotHours();
        $paidHours = $coachUser !== null
            ? min($maxBillable, max(0, $this->coachPaidHours))
            : 0;
        $coachTotalFee = $coachUser !== null ? $rate * $paidHours : 0;
        $coachFeeRemaining = $coachTotalFee;

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

                if ($coachUser !== null) {
                    if (CoachAvailabilityService::coachHasOverlappingBooking((string) $coachUser->id, $starts, $ends)) {
                        return [];
                    }
                }

                $courtGross = 0;
                foreach ($run as $h) {
                    $slotStart = Carbon::parse($date.' '.sprintf('%02d:00:00', $h), $tz);
                    $hourly = CourtSlotPricing::estimatedHourlyCentsAtStart($court, $slotStart)
                        ?? $court->courtClient?->hourly_rate_cents
                        ?? 0;
                    $courtGross += $hourly;
                }
                $courtGross = (int) round($courtGross);

                $coachFeeThisSpec = 0;
                if ($coachFeeRemaining > 0) {
                    $coachFeeThisSpec = $coachFeeRemaining;
                    $coachFeeRemaining = 0;
                }

                $specs[] = [
                    'court' => $court,
                    'starts' => $starts,
                    'ends' => $ends,
                    'gross_cents' => $courtGross + $coachFeeThisSpec,
                    'court_gross_cents' => $courtGross,
                    'hours' => $run,
                    'coach_fee_cents' => $coachFeeThisSpec,
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

    #[Computed]
    public function reviewCoachFeeCents(): int
    {
        if ($this->coachUserId === '') {
            return 0;
        }

        $specs = $this->buildSpecsForSubmit();
        if ($specs === []) {
            return 0;
        }

        return (int) array_sum(array_column($specs, 'coach_fee_cents'));
    }

    #[Computed]
    public function reviewCourtSubtotalCents(): int
    {
        $specs = $this->buildSpecsForSubmit();
        if ($specs === []) {
            return 0;
        }

        return (int) array_sum(array_column($specs, 'court_gross_cents'));
    }

    public function coachSelectionBlockedReason(): ?string
    {
        $by = CoachAvailabilityService::groupSlotsByCourt($this->selectedSlots);
        if (count($by) > 1) {
            return 'To book a coach, select time slots on one court only for this date. Court and coach are reserved together.';
        }

        return null;
    }

    /**
     * Best-effort preview (no row lock); submit re-validates.
     */
    #[Computed]
    public function reviewGiftEstimateCents(): int
    {
        $raw = trim($this->giftCardCode);
        if ($raw === '' || $this->step !== 'review') {
            return 0;
        }
        $specs = $this->buildSpecsForSubmit();
        if ($specs === []) {
            return 0;
        }
        $totalGross = (int) array_sum(array_column($specs, 'gross_cents'));
        $normalized = GiftCardService::normalizeCode($raw);
        $card = GiftCard::query()
            ->where('code', $normalized)
            ->where(function ($q): void {
                $q->where('court_client_id', $this->courtClient->id)
                    ->orWhereNull('court_client_id');
            })
            ->first();
        if ($card === null || ! $card->redeemableNow()) {
            return 0;
        }

        return GiftCardService::computeAppliedCents($card, $totalGross);
    }

    #[Computed]
    public function reviewBalanceAfterGiftCents(): int
    {
        return max(0, $this->reviewEstimateCents - $this->reviewGiftEstimateCents);
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

        $maxCoachH = $this->totalSelectedSlotHours();

        $rules = [
            'paymentMethod' => ['nullable', 'string', Rule::in(Booking::paymentMethodOptions())],
            'paymentReference' => ['nullable', 'string', 'max:128'],
            'paymentProof' => ['nullable', 'image', 'max:5120'],
            'giftCardCode' => ['nullable', 'string', 'max:48'],
            'coachUserId' => ['nullable', 'string', 'uuid', 'exists:users,id'],
            'coachPaidHours' => ['nullable', 'integer', 'min:0', 'max:'.$maxCoachH],
        ];

        if ($this->coachUserId !== '' && $maxCoachH >= 1) {
            $rules['coachPaidHours'] = ['required', 'integer', 'min:1', 'max:'.$maxCoachH];
        }

        $this->validate($rules, [], [
            'paymentReference' => 'payment reference',
            'giftCardCode' => 'gift card code',
            'coachPaidHours' => 'coach paid hours',
        ]);

        $specs = $this->buildSpecsForSubmit();
        if ($specs === []) {
            $this->addError('selectedSlots', 'Your selection is no longer available. Adjust the grid and try again.');

            return;
        }

        if ($this->coachUserId !== '') {
            $ids = $this->availableCoachesForReview->pluck('id')->map(fn ($id): string => (string) $id)->all();
            if (! in_array($this->coachUserId, $ids, true)) {
                $this->addError('coachUserId', 'That coach is not available for these courts and times. Choose another coach or change your selection.');

                return;
            }
            $this->clampCoachPaidHours();
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
                null,
                $this->paymentMethod,
                $this->paymentReference,
                $this->paymentProof,
                $this->giftCardCode,
                $this->coachUserId !== '' ? $this->coachUserId : null,
            );
        } catch (\InvalidArgumentException $e) {
            if ($e->getMessage() === 'No time slots to book.') {
                $this->addError('selectedSlots', 'Your selection is no longer available. Adjust the grid and try again.');
            } elseif (trim($this->giftCardCode) !== '') {
                $this->addError('giftCardCode', $e->getMessage());
            } else {
                $this->addError('submit', $e->getMessage());
            }

            return;
        }

        session()->forget(self::DRAFT_SESSION_KEY);
        session()->forget(self::AFTER_LOGIN_SESSION_KEY);

        $bookings = $result['bookings'];
        $deskPolicy = $result['desk_policy'];

        $this->selectedSlots = [];
        $this->paymentProof = null;
        $this->paymentReference = '';
        $this->paymentMethod = Booking::PAYMENT_GCASH;
        $this->giftCardCode = '';
        $this->coachUserId = '';
        $this->coachPaidHours = 0;
        $this->step = 'times';

        $giftTotal = (int) array_sum(array_filter(array_map(
            fn (Booking $b) => $b->gift_card_redeemed_cents,
            $bookings,
        )));

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
        if ($giftTotal > 0) {
            $flash .= ' Gift card applied: '.Money::formatMinor($giftTotal).' total.';
        }

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
