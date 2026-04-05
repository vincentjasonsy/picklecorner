<?php

namespace App\Livewire\Admin;

use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtClient;
use App\Models\CourtDateSlotBlock;
use App\Models\GiftCard;
use App\Models\User;
use App\Models\UserType;
use App\Models\VenueWeeklyHour;
use App\Services\ActivityLogger;
use App\Services\CourtSlotPricing;
use App\Services\GiftCardService;
use App\Support\Money;
use App\Support\VenueScheduleHours;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts::admin')]
#[Title('Manual booking')]
class CourtClientManualBooking extends Component
{
    use WithFileUploads;

    public CourtClient $courtClient;

    /** @var list<array{id: string, day_of_week: int, is_closed: bool, opens_at: string, closes_at: string}> */
    public array $scheduleRows = [];

    public string $bookingCalendarDate = '';

    /**
     * Selected cells as "courtUuid-slotHour" keys (same grid as availability).
     *
     * @var list<string>
     */
    public array $selectedManualSlots = [];

    public ?string $manualBookingUserId = null;

    public string $manualBookingUserSearch = '';

    public string $manualBookingNotes = '';

    public string $manualBookingGiftCardCode = '';

    public string $manualBookingPaymentMethod = Booking::PAYMENT_GCASH;

    public string $manualBookingPaymentReference = '';

    /** @var mixed */
    public $manualBookingPaymentProof = null;

    /** Set when desk taps a booked grid cell to inspect the reservation. */
    public ?string $deskViewBookingId = null;

    public function mount(?CourtClient $courtClient = null): void
    {
        abort_unless($courtClient !== null, 404);

        $this->authorizeManualBookingForCourtClient($courtClient);

        $this->courtClient = $courtClient->load(['courts', 'weeklyHours']);
        $this->ensureDefaultWeeklyHours();
        $this->courtClient->refresh();
        $this->courtClient->load(['courts', 'weeklyHours']);
        $this->syncScheduleRowsFromDatabase();
        $this->bookingCalendarDate = Carbon::now(config('app.timezone', 'UTC'))->format('Y-m-d');
    }

    protected function authorizeManualBookingForCourtClient(CourtClient $courtClient): void
    {
        $user = auth()->user();
        if ($user === null) {
            abort(403);
        }
        if ($user->isSuperAdmin()) {
            return;
        }
        if ($user->isCourtAdmin() && $user->administeredCourtClient?->is($courtClient)) {
            return;
        }
        if ($user->isCourtClientDesk() && $user->deskCourtClient?->is($courtClient)) {
            return;
        }
        abort(403);
    }

    /** @return 'admin'|'venue'|'desk' */
    public function manualBookingPortal(): string
    {
        return 'admin';
    }

    public function manualBookingBackUrl(): string
    {
        return route('admin.court-clients.edit', $this->courtClient);
    }

    protected function isDeskSubmission(): bool
    {
        return false;
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
        $this->selectedManualSlots = [];
        $this->deskViewBookingId = null;
    }

    public function shiftBookingDate(int $days): void
    {
        try {
            $d = Carbon::parse($this->bookingCalendarDate, config('app.timezone', 'UTC'))->addDays($days);
        } catch (\Throwable) {
            $d = Carbon::now(config('app.timezone', 'UTC'))->addDays($days);
        }
        $this->bookingCalendarDate = $d->format('Y-m-d');
        $this->selectedManualSlots = [];
        $this->deskViewBookingId = null;
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
                ->with(['timeSlotSettings', 'timeSlotBlocks'])
                ->get(),
        );
    }

    /**
     * Keys "courtId-slotHour" for date-only blocks (same as availability grid).
     *
     * @return array<string, true>
     */
    #[Computed]
    public function manualBookingDateBlockLookup(): array
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
     * Bookings that occupy a grid cell (same statuses that block new bookings).
     * Key: "courtId-hour" → guest display name + short status label + booking id.
     *
     * @return array<string, array{name: string, status: string, booking_id: string}>
     */
    #[Computed]
    public function manualBookingOccupancyBySlot(): array
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

    public function openDeskBookedSlot(string $courtId, int $hour): void
    {
        if ($this->manualBookingPortal() !== 'desk') {
            return;
        }

        $allowedHours = $this->slotHoursForSelectedDate();
        if (! in_array($hour, $allowedHours, true)) {
            return;
        }

        $court = Court::query()
            ->where('id', $courtId)
            ->where('court_client_id', $this->courtClient->id)
            ->first();
        if (! $court) {
            return;
        }

        $key = $courtId.'-'.$hour;
        $occ = $this->manualBookingOccupancyBySlot;
        if (! isset($occ[$key]['booking_id'])) {
            return;
        }

        $this->deskViewBookingId = $occ[$key]['booking_id'];
    }

    public function closeDeskViewBooking(): void
    {
        $this->deskViewBookingId = null;
    }

    #[Computed]
    public function deskViewBooking(): ?Booking
    {
        if ($this->manualBookingPortal() !== 'desk' || $this->deskViewBookingId === null) {
            return null;
        }

        return Booking::query()
            ->where('id', $this->deskViewBookingId)
            ->where('court_client_id', $this->courtClient->id)
            ->with(['user', 'court', 'deskSubmitter'])
            ->first();
    }

    public function isManualSlotSelected(string $courtId, int $hour): bool
    {
        return in_array($courtId.'-'.$hour, $this->selectedManualSlots, true);
    }

    public function toggleManualSlot(string $courtId, int $hour): void
    {
        $allowedHours = $this->slotHoursForSelectedDate();
        if (! in_array($hour, $allowedHours, true)) {
            return;
        }

        $court = Court::query()
            ->where('id', $courtId)
            ->where('court_client_id', $this->courtClient->id)
            ->first();
        if (! $court) {
            return;
        }

        $key = $courtId.'-'.$hour;
        $occupancy = $this->manualBookingOccupancyBySlot;
        if (isset($occupancy[$key])) {
            return;
        }

        $selected = $this->selectedManualSlots;
        $idx = array_search($key, $selected, true);
        if ($idx !== false) {
            unset($selected[$idx]);
            $this->selectedManualSlots = array_values($selected);

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
        $this->selectedManualSlots = array_values($selected);
    }

    public function clearSlotSelection(): void
    {
        $this->selectedManualSlots = [];
    }

    /**
     * @return array<string, list<int>> court id => sorted unique hours
     */
    protected function selectedSlotsGroupedByCourt(): array
    {
        $by = [];
        foreach ($this->selectedManualSlots as $key) {
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

    /**
     * @param  list<int>  $grosses
     * @return list<int>
     */
    protected function allocateGiftCentsAcrossBookings(int $totalApplied, array $grosses): array
    {
        $n = count($grosses);
        if ($n === 0) {
            return [];
        }
        $G = array_sum($grosses);
        if ($G <= 0 || $totalApplied <= 0) {
            return array_fill(0, $n, 0);
        }
        $out = [];
        $assigned = 0;
        for ($i = 0; $i < $n - 1; $i++) {
            $out[$i] = (int) floor($totalApplied * $grosses[$i] / $G);
            $assigned += $out[$i];
        }
        $out[$n - 1] = max(0, $totalApplied - $assigned);

        return $out;
    }

    public function slotHourLabel(int $hour): string
    {
        return Carbon::createFromTime($hour, 0, 0)->format('g:i A');
    }

    #[Computed]
    public function manualBookingUserResults(): Collection
    {
        $bookerTypeIds = UserType::query()
            ->whereIn('slug', [UserType::SLUG_USER, UserType::SLUG_COACH])
            ->pluck('id');

        $q = trim($this->manualBookingUserSearch);
        if (strlen($q) < 2) {
            return collect();
        }

        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $q).'%';

        return User::query()
            ->with('userType')
            ->whereIn('user_type_id', $bookerTypeIds)
            ->where(function ($sub) use ($like) {
                $sub->where('email', 'like', $like)
                    ->orWhere('name', 'like', $like);
            })
            ->orderBy('email')
            ->limit(20)
            ->get();
    }

    #[Computed]
    public function selectedManualBookingUser(): ?User
    {
        if ($this->manualBookingUserId === null || $this->manualBookingUserId === '') {
            return null;
        }

        return User::query()->find($this->manualBookingUserId);
    }

    public function selectManualBookingUser(string $userId): void
    {
        $bookerTypeIds = UserType::query()
            ->whereIn('slug', [UserType::SLUG_USER, UserType::SLUG_COACH])
            ->pluck('id')
            ->all();

        $exists = User::query()
            ->where('id', $userId)
            ->whereIn('user_type_id', $bookerTypeIds)
            ->exists();

        if (! $exists) {
            return;
        }

        $this->manualBookingUserId = $userId;
        $this->manualBookingUserSearch = '';
        unset($this->manualBookingUserResults, $this->selectedManualBookingUser);
    }

    public function clearManualBookingUserSelection(): void
    {
        $this->manualBookingUserId = null;
        $this->manualBookingUserSearch = '';
        unset($this->manualBookingUserResults, $this->selectedManualBookingUser);
    }

    public function saveManualBooking(): void
    {
        $bookerTypeIds = UserType::query()
            ->whereIn('slug', [UserType::SLUG_USER, UserType::SLUG_COACH])
            ->pluck('id');

        $desk = $this->isDeskSubmission();

        $rules = [
            'manualBookingUserId' => [
                'required',
                'uuid',
                Rule::exists('users', 'id')->whereIn('user_type_id', $bookerTypeIds),
            ],
            'manualBookingNotes' => ['nullable', 'string', 'max:2000'],
        ];

        if ($desk) {
            $rules['manualBookingGiftCardCode'] = ['prohibited'];
            $rules['manualBookingPaymentMethod'] = ['nullable', 'string', Rule::in(Booking::paymentMethodOptions())];
            $rules['manualBookingPaymentReference'] = ['nullable', 'string', 'max:128'];
            $rules['manualBookingPaymentProof'] = ['nullable', 'image', 'max:5120'];
        } else {
            $rules['manualBookingGiftCardCode'] = ['nullable', 'string', 'max:48'];
            $rules['manualBookingPaymentMethod'] = ['required', 'string', Rule::in(Booking::paymentMethodOptions())];
            $rules['manualBookingPaymentReference'] = ['required', 'string', 'max:128'];
            $rules['manualBookingPaymentProof'] = ['nullable', 'image', 'max:5120'];
        }

        $this->validate($rules, [], [
            'manualBookingUserId' => 'player or coach',
            'manualBookingPaymentReference' => 'payment reference',
        ]);

        $byCourt = $this->selectedSlotsGroupedByCourt();
        if ($byCourt === []) {
            $this->addError('selectedManualSlots', 'Select at least one court and time slot on the grid.');

            return;
        }

        $allowed = $this->slotHoursForSelectedDate();
        $date = $this->normalizedBookingCalendarDate();
        if ($date === null) {
            $this->addError('bookingCalendarDate', 'Invalid date.');

            return;
        }

        $tz = config('app.timezone', 'UTC');

        /** @var list<array{court: Court, starts: Carbon, ends: Carbon, gross_cents: int, hours: list<int>}> */
        $specs = [];

        foreach ($byCourt as $courtId => $hours) {
            $court = Court::query()
                ->with(['courtClient', 'timeSlotSettings'])
                ->where('id', $courtId)
                ->where('court_client_id', $this->courtClient->id)
                ->first();
            if (! $court) {
                $this->addError('selectedManualSlots', 'One or more selected courts are invalid.');

                return;
            }

            foreach ($hours as $h) {
                if (! in_array($h, $allowed, true)) {
                    $this->addError('selectedManualSlots', 'One or more slots are outside venue hours for this day.');

                    return;
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
                    $this->addError(
                        'selectedManualSlots',
                        'A selected court already has a booking that overlaps one of the chosen times.',
                    );

                    return;
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

        if ($specs === []) {
            $this->addError('selectedManualSlots', 'Select at least one time slot on the grid.');

            return;
        }

        $totalGross = (int) array_sum(array_column($specs, 'gross_cents'));
        $giftCodeRaw = $desk ? '' : trim($this->manualBookingGiftCardCode);

        $deskPolicy = CourtClient::DESK_BOOKING_POLICY_MANUAL;
        $deskStatus = Booking::STATUS_PENDING_APPROVAL;
        $bookingNotesForCreate = $this->manualBookingNotes !== '' ? $this->manualBookingNotes : null;

        if ($desk) {
            $rawPolicy = CourtClient::query()
                ->where('id', $this->courtClient->id)
                ->value('desk_booking_policy');
            $deskPolicy = in_array((string) $rawPolicy, CourtClient::deskBookingPolicyValues(), true)
                ? (string) $rawPolicy
                : CourtClient::DESK_BOOKING_POLICY_MANUAL;

            $deskStatus = match ($deskPolicy) {
                CourtClient::DESK_BOOKING_POLICY_AUTO_APPROVE => Booking::STATUS_CONFIRMED,
                CourtClient::DESK_BOOKING_POLICY_AUTO_DENY => Booking::STATUS_DENIED,
                default => Booking::STATUS_PENDING_APPROVAL,
            };

            if ($deskPolicy === CourtClient::DESK_BOOKING_POLICY_AUTO_DENY) {
                $suffix = 'Auto-denied by venue desk booking policy.';
                $bookingNotesForCreate = $bookingNotesForCreate !== null && $bookingNotesForCreate !== ''
                    ? $bookingNotesForCreate."\n\n".$suffix
                    : $suffix;
            }
        }

        try {
            $bookings = DB::transaction(function () use (
                $specs,
                $giftCodeRaw,
                $totalGross,
                $desk,
                $deskStatus,
                $bookingNotesForCreate,
            ) {
                $giftCardId = null;
                $giftAppliedTotal = null;
                $lockedGiftCard = null;
                if ($giftCodeRaw !== '') {
                    $lockedGiftCard = GiftCardService::lockCardForDebit(
                        $this->courtClient->id,
                        $giftCodeRaw,
                    );
                    $giftAppliedTotal = GiftCardService::computeAppliedCents($lockedGiftCard, $totalGross);
                    if ($giftAppliedTotal <= 0) {
                        throw new \InvalidArgumentException('Nothing to apply from this gift card.');
                    }
                    $giftCardId = $lockedGiftCard->id;
                }

                $grosses = array_column($specs, 'gross_cents');
                $giftSlices = $giftAppliedTotal !== null
                    ? $this->allocateGiftCentsAcrossBookings($giftAppliedTotal, $grosses)
                    : array_fill(0, count($specs), 0);

                if ($lockedGiftCard !== null && $giftCardId !== null) {
                    $nWithGift = count(array_filter($giftSlices, fn (int $s): bool => $s > 0));
                    if ($nWithGift > 0) {
                        GiftCardService::assertRedemptionLimits(
                            $lockedGiftCard,
                            $this->manualBookingUserId,
                            $nWithGift,
                        );
                    }
                }

                $proofPath = null;
                if ($this->manualBookingPaymentProof !== null) {
                    $proofPath = $this->manualBookingPaymentProof->store(
                        'manual-booking-proofs/'.Str::uuid()->toString(),
                        'public',
                    );
                }

                $pm = $this->manualBookingPaymentMethod !== ''
                    ? $this->manualBookingPaymentMethod
                    : null;
                $pref = trim((string) $this->manualBookingPaymentReference);

                $created = [];
                foreach ($specs as $i => $spec) {
                    /** @var Court $court */
                    $court = $spec['court'];
                    $gross = (int) $spec['gross_cents'];
                    $slice = $giftSlices[$i] ?? 0;
                    $netCents = max(0, $gross - $slice);

                    $booking = Booking::query()->create([
                        'court_client_id' => $this->courtClient->id,
                        'court_id' => $court->id,
                        'user_id' => $this->manualBookingUserId,
                        'desk_submitted_by' => $desk ? auth()->id() : null,
                        'starts_at' => $spec['starts'],
                        'ends_at' => $spec['ends'],
                        'status' => $desk ? $deskStatus : Booking::STATUS_CONFIRMED,
                        'amount_cents' => $netCents > 0 ? $netCents : null,
                        'currency' => $this->courtClient->currency ?? 'PHP',
                        'notes' => $bookingNotesForCreate,
                        'gift_card_id' => $slice > 0 ? $giftCardId : null,
                        'gift_card_redeemed_cents' => $slice > 0 ? $slice : null,
                        'payment_method' => $pm,
                        'payment_reference' => $pref !== '' ? $pref : null,
                        'payment_proof_path' => $proofPath,
                    ]);

                    if ($slice > 0 && $giftCardId !== null) {
                        GiftCardService::recordBookingRedemption($booking, $giftCardId, $slice);
                    }

                    $created[] = $booking;
                }

                if ($giftAppliedTotal !== null && $giftCardId !== null && $giftAppliedTotal > 0) {
                    $card = GiftCard::query()->find($giftCardId);
                    ActivityLogger::log(
                        'gift_card.redeemed',
                        [
                            'amount_cents' => $giftAppliedTotal,
                            'booking_ids' => array_map(fn (Booking $b) => $b->id, $created),
                        ],
                        $card,
                        $card ? "Gift card {$card->code} applied to manual bookings" : 'Gift card applied to manual bookings',
                    );
                }

                return $created;
            });
        } catch (\InvalidArgumentException $e) {
            $this->addError('manualBookingGiftCardCode', $e->getMessage());

            return;
        }

        $first = $bookings[0]->fresh();
        $ids = array_map(fn (Booking $b) => $b->id, $bookings);

        if ($desk) {
            match ($deskPolicy) {
                CourtClient::DESK_BOOKING_POLICY_AUTO_APPROVE => ActivityLogger::log(
                    'booking.desk_auto_approved',
                    ['booking_ids' => $ids, 'policy' => $deskPolicy],
                    $first,
                    count($bookings) === 1
                        ? 'Desk booking auto-approved'
                        : count($bookings).' desk bookings auto-approved',
                ),
                CourtClient::DESK_BOOKING_POLICY_AUTO_DENY => ActivityLogger::log(
                    'booking.desk_auto_denied',
                    ['booking_ids' => $ids, 'policy' => $deskPolicy],
                    $first,
                    count($bookings) === 1
                        ? 'Desk booking auto-denied'
                        : count($bookings).' desk bookings auto-denied',
                ),
                default => ActivityLogger::log(
                    'booking.desk_submitted',
                    [
                        'booking_ids' => $ids,
                    ],
                    $first,
                    count($bookings) === 1
                        ? 'Desk booking request submitted'
                        : count($bookings).' desk booking requests submitted',
                ),
            };
        } else {
            ActivityLogger::log(
                'booking.created_manual',
                [
                    'booking_ids' => $ids,
                    'payment_method' => $first->payment_method,
                    'payment_reference' => $first->payment_reference,
                    'has_payment_proof' => $first->payment_proof_path !== null,
                ],
                $first,
                count($bookings) === 1
                    ? "Manual booking created for “{$this->courtClient->name}”"
                    : count($bookings).' manual bookings created for “'.$this->courtClient->name.'”',
            );
        }

        $this->selectedManualSlots = [];
        $this->manualBookingNotes = '';
        $this->manualBookingGiftCardCode = '';
        $this->manualBookingPaymentProof = null;
        $this->manualBookingPaymentReference = '';
        $this->manualBookingPaymentMethod = Booking::PAYMENT_GCASH;

        $giftTotal = (int) array_sum(array_filter(array_map(
            fn (Booking $b) => $b->gift_card_redeemed_cents,
            $bookings,
        )));

        if ($desk) {
            $flash = match ($deskPolicy) {
                CourtClient::DESK_BOOKING_POLICY_AUTO_APPROVE => count($bookings) === 1
                    ? 'Booking confirmed automatically (venue setting).'
                    : count($bookings).' bookings confirmed automatically (venue setting).',
                CourtClient::DESK_BOOKING_POLICY_AUTO_DENY => count($bookings) === 1
                    ? 'Booking was not accepted (venue auto-deny setting).'
                    : 'Bookings were not accepted (venue auto-deny setting).',
                default => count($bookings) === 1
                    ? 'Booking request sent to your venue admin for approval.'
                    : count($bookings).' booking requests sent to your venue admin for approval.',
            };
        } else {
            $flash = count($bookings) === 1
                ? 'Manual booking created.'
                : count($bookings).' manual bookings created.';
            if ($giftTotal > 0) {
                $flash .= ' Gift card applied: '.Money::formatMinor($giftTotal).' total.';
            }
        }
        session()->flash('status', $flash);
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

    public function render(): View
    {
        return view('livewire.admin.court-client-manual-booking', [
            'dayLabels' => VenueWeeklyHour::DAY_LABELS,
        ]);
    }
}
