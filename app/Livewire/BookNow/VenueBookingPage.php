<?php

namespace App\Livewire\BookNow;

use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtClient;
use App\Models\CourtDateSlotBlock;
use App\Models\GiftCard;
use App\Models\PaymongoBookingIntent;
use App\Models\UserType;
use App\Models\VenueWeeklyHour;
use App\Services\BookingFeeService;
use App\Services\CoachAvailabilityService;
use App\Services\CourtSlotPricing;
use App\Services\GiftCardService;
use App\Services\PaymongoVenueBookingPayment;
use App\Services\PublicVenueBookingSubmission;
use App\Services\VenueBookingSlotProbe;
use App\Services\VenueBookingSpecsBuilder;
use App\Support\BookingCalendar;
use App\Support\Money;
use App\Support\VenueScheduleHours;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
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

    public string $paymentMethod = Booking::PAYMENT_PAYMONGO;

    public string $paymentReference = '';

    public string $giftCardCode = '';

    /** Optional coach for this request (must be available on every selected court and hour). */
    public string $coachUserId = '';

    /** Billable coach hours (you choose; max = selected slot hours). Only used when {@see $coachUserId} is set. */
    public int $coachPaidHours = 0;

    /** Host wants extra players to join this single-court booking (one continuous block only). */
    public bool $isOpenPlay = false;

    /** Max additional players who can join (not including you). */
    public int $openPlayMaxSlots = 4;

    /** Shown to people who want to join (format, level, etc.). */
    public string $openPlayPublicNotes = '';

    /** How joiners pay you (e.g. GCash number). Shown to accepted players. */
    public string $openPlayHostPaymentDetails = '';

    /** Optional line for refunds / coordination (Viber, email, etc.). Shown to joiners. */
    public string $openPlayExternalContact = '';

    /** Refund expectations (e.g. partial refunds, host discretion). Shown to joiners. */
    public string $openPlayRefundPolicy = '';

    /** @var mixed */
    public $paymentProof = null;

    /** Acknowledgement that the convenience fee is non-refundable when a convenience fee applies. */
    public bool $ackConvenienceFeeNonRefundable = false;

    public function mount(CourtClient $courtClient): void
    {
        abort_unless($courtClient->isListedOnBookNow(), 404);

        $this->courtClient = $courtClient->load(['courts', 'approvedGalleryImages', 'weeklyHours']);
        $this->ensureDefaultWeeklyHours();
        $this->courtClient->refresh();
        $this->courtClient->load(['courts.approvedGalleryImages', 'approvedGalleryImages', 'weeklyHours']);
        $this->syncScheduleRowsFromDatabase();
        $this->bookingCalendarDate = Carbon::now(config('app.timezone', 'UTC'))->format('Y-m-d');

        if ($this->venueIsOpeningSoon()) {
            $this->step = 'times';
            $this->selectedSlots = [];
        }

        if (session()->pull(self::AFTER_LOGIN_SESSION_KEY, false)) {
            $draft = session()->get(self::DRAFT_SESSION_KEY);
            if (is_array($draft) && ($draft['court_client_id'] ?? '') === $this->courtClient->id) {
                $this->hydrateFromDraft($draft);
                session()->forget(self::DRAFT_SESSION_KEY);
            }
        }

        $this->applyBookAgainQueryParameters();
        $this->hydrateFromPaymongoCheckoutFlash();
        $this->clearInvalidPrefilledSlotsIfNeeded();
        $this->maybeAdvanceBookAgainToReviewFromQuery();
        $this->maybeRestoreReviewStepAfterPaymongoReturn();

        $this->clearCoachWhenCheckoutHidden();
        $this->syncOpenPlayEligibility();
    }

    /**
     * Restore review-step state from {@see PaymongoBookingIntent::payload_json} after checkout cancel / unpaid return.
     */
    protected function hydrateFromPaymongoCheckoutFlash(): void
    {
        $flash = session('paymongo_checkout');
        if (! is_array($flash) || empty($flash['intent_id']) || ! is_string($flash['intent_id'])) {
            return;
        }

        $intent = PaymongoBookingIntent::query()->find($flash['intent_id']);
        if ($intent === null || $intent->court_client_id !== $this->courtClient->id) {
            return;
        }

        $user = auth()->user();
        if ($user === null || $intent->user_id !== $user->id) {
            return;
        }

        if ($intent->status === PaymongoBookingIntent::STATUS_COMPLETED) {
            return;
        }

        $payload = $intent->payload_json;
        if (! is_array($payload)) {
            return;
        }

        $this->applyPayloadFromPaymongoIntent($payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function applyPayloadFromPaymongoIntent(array $payload): void
    {
        $rawDate = $payload['booking_calendar_date'] ?? null;
        if (is_string($rawDate) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawDate)) {
            try {
                $this->bookingCalendarDate = Carbon::parse($rawDate, config('app.timezone', 'UTC'))->format('Y-m-d');
            } catch (\Throwable) {
                // keep mount default
            }
        }

        $slots = $payload['selected_slots'] ?? [];
        $this->selectedSlots = is_array($slots)
            ? array_values(array_unique(array_filter(array_map('strval', $slots))))
            : [];

        $this->paymentMethod = Booking::PAYMENT_PAYMONGO;
        $this->paymentReference = '';

        if (is_string($payload['gift_card_code'] ?? null)) {
            $this->giftCardCode = $payload['gift_card_code'];
        }

        if ($this->venueCheckoutShowCoach() && is_string($payload['coach_user_id'] ?? null)) {
            $this->coachUserId = $payload['coach_user_id'];
        }
        if (isset($payload['coach_paid_hours'])) {
            $this->coachPaidHours = max(0, (int) $payload['coach_paid_hours']);
        }

        $this->isOpenPlay = (bool) ($payload['is_open_play'] ?? false);
        $openPlay = $payload['open_play'] ?? null;
        if ($this->isOpenPlay && is_array($openPlay)) {
            if (isset($openPlay['max_slots'])) {
                $this->openPlayMaxSlots = max(1, min(48, (int) $openPlay['max_slots']));
            }
            if (array_key_exists('public_notes', $openPlay) && is_string($openPlay['public_notes'])) {
                $this->openPlayPublicNotes = $openPlay['public_notes'];
            }
            if (array_key_exists('host_payment_details', $openPlay) && is_string($openPlay['host_payment_details'])) {
                $this->openPlayHostPaymentDetails = $openPlay['host_payment_details'];
            }
            if (array_key_exists('external_contact', $openPlay) && is_string($openPlay['external_contact'])) {
                $this->openPlayExternalContact = $openPlay['external_contact'];
            }
            if (array_key_exists('refund_policy', $openPlay) && is_string($openPlay['refund_policy'])) {
                $this->openPlayRefundPolicy = $openPlay['refund_policy'];
            }
        }

        $this->step = 'times';
        $this->ackConvenienceFeeNonRefundable = false;

        $this->clearCoachWhenCheckoutHidden();

        $hydratedDate = $this->normalizedBookingCalendarDate();
        if ($hydratedDate !== null && $this->courtClient->isClosedOnDate($hydratedDate)) {
            $this->selectedSlots = [];
            $this->step = 'times';
        }

        $this->syncOpenPlayEligibility();
    }

    /** After payload restore + slot validation, return to review so the grid and totals match the PayMongo banner. */
    protected function maybeRestoreReviewStepAfterPaymongoReturn(): void
    {
        $flash = session('paymongo_checkout');
        if (! is_array($flash) || empty($flash['intent_id'])) {
            return;
        }

        if ($this->selectedSlots === []) {
            return;
        }

        if ($this->step !== 'times') {
            return;
        }

        $this->goToReview();
    }

    protected function applyBookAgainQueryParameters(): void
    {
        $tz = config('app.timezone', 'UTC');

        $bookDate = request()->query('book_date');
        if (is_string($bookDate) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $bookDate)) {
            try {
                $this->bookingCalendarDate = Carbon::parse($bookDate, $tz)->format('Y-m-d');
            } catch (\Throwable) {
                // ignore invalid GET
            }
        }

        $slotsRaw = request()->query('book_slots');
        if (! is_string($slotsRaw) || trim($slotsRaw) === '') {
            return;
        }

        $parts = array_values(array_filter(array_map('trim', explode(',', $slotsRaw))));
        $clean = [];

        foreach ($parts as $p) {
            if (! preg_match('/^([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})-(\d{1,2})$/i', $p, $m)) {
                continue;
            }

            $clean[] = strtolower($m[1]).'-'.((int) $m[2]);
        }

        if ($clean !== []) {
            $this->selectedSlots = array_values(array_unique($clean));
            $this->step = 'times';
            $this->coachUserId = '';
            $this->coachPaidHours = 0;
            $this->syncOpenPlayEligibility();
        }
    }

    protected function clearInvalidPrefilledSlotsIfNeeded(): void
    {
        if ($this->selectedSlots === []) {
            return;
        }

        $courtId = null;

        foreach ($this->selectedSlots as $slot) {
            if (preg_match('/^(.+)-(\d+)$/', $slot, $m)) {
                $courtId = $m[1];

                break;
            }
        }

        if ($courtId === null) {
            return;
        }

        $hours = [];

        foreach ($this->selectedSlots as $slot) {
            $quoted = preg_quote((string) $courtId, '/');

            if (preg_match('/^'.$quoted.'-(\d+)$/', $slot, $hm)) {
                $hours[] = (int) $hm[1];
            }
        }

        sort($hours);

        $date = $this->normalizedBookingCalendarDate();

        if ($date === null || ! VenueBookingSlotProbe::canSelectSlots($this->courtClient, $date, $courtId, $hours)) {
            $this->selectedSlots = [];
            session()->flash('status', 'Those exact times aren’t available anymore — tap open slots on the grid.');
        }
    }

    /** When “Book again” passes `book_step=review`, jump to checkout after valid prefill. */
    protected function maybeAdvanceBookAgainToReviewFromQuery(): void
    {
        if (request()->query('book_step') !== 'review') {
            return;
        }

        if ($this->selectedSlots === []) {
            return;
        }

        $this->goToReview();
    }

    protected function venueCheckoutShowCoach(): bool
    {
        return (bool) config('booking.venue_checkout_show_coach', false);
    }

    /** Ignore coach when the checkout UI is disabled via config. */
    protected function effectiveCoachUserId(): string
    {
        if (! $this->venueCheckoutShowCoach()) {
            return '';
        }

        return $this->coachUserId;
    }

    protected function clearCoachWhenCheckoutHidden(): void
    {
        if (! $this->venueCheckoutShowCoach()) {
            $this->coachUserId = '';
            $this->coachPaidHours = 0;
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
        $this->paymentMethod = Booking::PAYMENT_PAYMONGO;
        $this->paymentReference = '';
        if (is_string($draft['gift_card_code'] ?? null)) {
            $this->giftCardCode = $draft['gift_card_code'];
        }
        if (is_string($draft['coach_user_id'] ?? null)) {
            $this->coachUserId = $draft['coach_user_id'];
        }
        if (isset($draft['coach_paid_hours'])) {
            $this->coachPaidHours = max(0, (int) $draft['coach_paid_hours']);
        }
        if (isset($draft['open_play_max_slots'])) {
            $this->openPlayMaxSlots = max(1, min(48, (int) $draft['open_play_max_slots']));
        }
        if (is_string($draft['open_play_public_notes'] ?? null)) {
            $this->openPlayPublicNotes = $draft['open_play_public_notes'];
        }
        if (is_string($draft['open_play_host_payment_details'] ?? null)) {
            $this->openPlayHostPaymentDetails = $draft['open_play_host_payment_details'];
        }
        if (is_string($draft['open_play_external_contact'] ?? null)) {
            $this->openPlayExternalContact = $draft['open_play_external_contact'];
        }
        if (is_string($draft['open_play_refund_policy'] ?? null)) {
            $this->openPlayRefundPolicy = $draft['open_play_refund_policy'];
        }
        $this->step = ($draft['step'] ?? 'review') === 'times' ? 'times' : 'review';

        $this->clearCoachWhenCheckoutHidden();

        $hydratedDate = $this->normalizedBookingCalendarDate();
        if ($hydratedDate !== null && $this->courtClient->isClosedOnDate($hydratedDate)) {
            $this->selectedSlots = [];
            $this->step = 'times';
        }

        $this->syncOpenPlayEligibility();
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
            'is_open_play' => $this->isOpenPlay,
            'open_play_max_slots' => $this->openPlayMaxSlots,
            'open_play_public_notes' => $this->openPlayPublicNotes,
            'open_play_host_payment_details' => $this->openPlayHostPaymentDetails,
            'open_play_external_contact' => $this->openPlayExternalContact,
            'open_play_refund_policy' => $this->openPlayRefundPolicy,
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

        return in_array($slug, [UserType::SLUG_USER, UserType::SLUG_COACH, UserType::SLUG_OPEN_PLAY_HOST], true);
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
        $this->syncOpenPlayEligibility();
    }

    public function shiftBookingDate(int $days): void
    {
        if ($this->venueIsOpeningSoon()) {
            return;
        }

        try {
            $d = Carbon::parse($this->bookingCalendarDate, config('app.timezone', 'UTC'))->addDays($days);
        } catch (\Throwable) {
            $d = Carbon::now(config('app.timezone', 'UTC'))->addDays($days);
        }
        $this->bookingCalendarDate = $d->format('Y-m-d');
        $this->selectedSlots = [];
        $this->coachUserId = '';
        $this->coachPaidHours = 0;
        $this->syncOpenPlayEligibility();
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
        $date = $this->normalizedBookingCalendarDate();
        if ($date !== null && $this->courtClient->isClosedOnDate($date)) {
            return [];
        }

        return VenueScheduleHours::slotStartHoursForDay($this->scheduleRows, $this->bookingDayOfWeek());
    }

    public function isBookingDateVenueClosure(): bool
    {
        $date = $this->normalizedBookingCalendarDate();

        return $date !== null && $this->courtClient->isClosedOnDate($date);
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
                ->with(['timeSlotSettings', 'timeSlotBlocks', 'approvedGalleryImages'])
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

    protected function isPublicBookingGridSlotBlocked(Court $court, int $hour): bool
    {
        $dow = $this->bookingDayOfWeek();
        if ($court->isWeeklySlotBlocked($dow, $hour)) {
            return true;
        }

        $key = $court->id.'-'.$hour;
        $dateBlocks = $this->dateBlockLookup;

        return isset($dateBlocks[$key]);
    }

    protected function selectedSlotsIncludeBlockedCells(): bool
    {
        $dow = $this->bookingDayOfWeek();
        $dateBlocks = $this->dateBlockLookup;
        $courts = $this->courtsOrderedForGrid()->keyBy('id');

        foreach ($this->selectedSlots as $key) {
            if (! preg_match('/^(.+)-(\d+)$/', $key, $m)) {
                continue;
            }
            $cid = $m[1];
            $h = (int) $m[2];
            $court = $courts->get($cid);
            if ($court === null) {
                continue;
            }
            if ($court->isWeeklySlotBlocked($dow, $h)) {
                return true;
            }
            if (isset($dateBlocks[$cid.'-'.$h])) {
                return true;
            }
        }

        return false;
    }

    public function venueIsOpeningSoon(): bool
    {
        return $this->courtClient->isOpeningSoonVenue();
    }

    public function toggleSlot(string $courtId, int $hour): void
    {
        if ($this->venueIsOpeningSoon()) {
            return;
        }

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

        if ($this->isPublicBookingGridSlotBlocked($court, $hour)) {
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
        $this->clampCoachPaidHours();
        $this->syncOpenPlayEligibility();
    }

    /**
     * Remove one review-table row: all hourly slots in the contiguous block for that court.
     *
     * @param  list<int>  $hours
     */
    public function removeReviewSpecSlots(string $courtId, array $hours): void
    {
        if ($this->venueIsOpeningSoon()) {
            return;
        }

        if ($this->step !== 'review') {
            return;
        }

        $court = Court::query()
            ->where('id', $courtId)
            ->where('court_client_id', $this->courtClient->id)
            ->first();
        if ($court === null) {
            return;
        }

        $hours = array_values(array_unique(array_map(static fn (mixed $h): int => (int) $h, $hours)));
        if ($hours === []) {
            return;
        }

        $selected = $this->selectedSlots;
        foreach ($hours as $h) {
            $key = $courtId.'-'.$h;
            $idx = array_search($key, $selected, true);
            if ($idx !== false) {
                unset($selected[$idx]);
            }
        }
        $this->selectedSlots = array_values($selected);
        $this->clampCoachPaidHours();
        $this->syncOpenPlayEligibility();

        if ($this->selectedSlots === []) {
            $this->backToTimes();
        }
    }

    protected function syncOpenPlayEligibility(): void
    {
        $user = auth()->user();
        if ($user === null || ! $user->isOpenPlayHost()) {
            $this->isOpenPlay = false;

            return;
        }

        if (count($this->buildSpecsForSubmit()) !== 1) {
            $this->turnOffOpenPlayHostAndResetFields();

            return;
        }

        $this->isOpenPlay = true;
    }

    /**
     * Host had open play on, but selection no longer qualifies (e.g. multiple courts). Clear fields and flag.
     */
    protected function turnOffOpenPlayHostAndResetFields(): void
    {
        if ($this->isOpenPlay) {
            $this->openPlayPublicNotes = '';
            $this->openPlayHostPaymentDetails = '';
            $this->openPlayExternalContact = '';
            $this->openPlayRefundPolicy = '';
            $this->openPlayMaxSlots = 4;
        }
        $this->isOpenPlay = false;
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
        return VenueBookingSpecsBuilder::totalSelectedSlotHours($this->selectedSlots);
    }

    public function clearSlotSelection(): void
    {
        $this->selectedSlots = [];
        $this->coachUserId = '';
        $this->coachPaidHours = 0;
        $this->syncOpenPlayEligibility();
    }

    /**
     * @return array<string, list<int>>
     */
    protected function selectedSlotsGroupedByCourt(): array
    {
        return VenueBookingSpecsBuilder::selectedSlotsGroupedByCourt($this->selectedSlots);
    }

    /**
     * @param  list<int>  $sortedUnique
     * @return list<list<int>>
     */
    protected function contiguousHourRuns(array $sortedUnique): array
    {
        return VenueBookingSpecsBuilder::contiguousHourRuns($sortedUnique);
    }

    public function slotHourLabel(int $hour): string
    {
        return Carbon::createFromTime($hour, 0, 0)->format('g:i A');
    }

    /**
     * Per-hour court rate for this calendar cell (matches checkout line pricing).
     */
    public function slotHourPriceLabel(Court $court, int $hour): string
    {
        $resolved = CourtSlotPricing::resolveForSlot($court, $this->bookingDayOfWeek(), $hour);
        $cents = $resolved['cents'];

        if ($cents === null || $cents <= 0) {
            return '';
        }

        return Money::formatMinor((int) $cents, $this->courtClient->currency ?? 'PHP');
    }

    /** Used by review-step floating CTA (must read Livewire state on $this, not Blade locals). */
    public function reviewStepHasSpecs(): bool
    {
        return count($this->buildSpecsForSubmit()) > 0;
    }

    /** Disable Continue / Submit until slots are valid and any convenience fee is acknowledged. */
    public function reviewSubmitActionDisabled(): bool
    {
        if ($this->venueIsOpeningSoon()) {
            return true;
        }

        if (! $this->reviewStepHasSpecs()) {
            return true;
        }

        return $this->reviewBookingFeeCents > 0 && ! $this->ackConvenienceFeeNonRefundable;
    }

    protected function bookingOverlapsCourt(string $courtId, Carbon $starts, Carbon $ends): bool
    {
        return VenueBookingSpecsBuilder::bookingOverlapsCourt($courtId, $starts, $ends);
    }

    public function goToReview(): void
    {
        if ($this->venueIsOpeningSoon()) {
            return;
        }

        $this->resetErrorBag('selectedSlots');
        $date = $this->normalizedBookingCalendarDate();
        if ($date !== null && $this->courtClient->isClosedOnDate($date)) {
            $this->selectedSlots = [];
            $this->addError(
                'selectedSlots',
                'This date is closed for booking at this venue. Choose another day.',
            );

            return;
        }
        $byCourt = $this->selectedSlotsGroupedByCourt();
        if ($byCourt === []) {
            $this->addError('selectedSlots', 'Select at least one open time slot on the grid.');

            return;
        }

        if ($this->selectedSlotsIncludeBlockedCells()) {
            $this->addError(
                'selectedSlots',
                'Your selection includes blocked times. Remove them or pick another date.',
            );

            return;
        }

        $this->clampCoachPaidHours();
        $this->syncOpenPlayEligibility();
        $this->ackConvenienceFeeNonRefundable = false;
        $this->step = 'review';
        $this->js('window.scrollTo(0, 0);');
    }

    public function backToTimes(): void
    {
        $this->ackConvenienceFeeNonRefundable = false;
        $this->step = 'times';
    }

    /**
     * Download provisional times as .ics on the review step (Apple Calendar, Outlook, etc.).
     */
    public function downloadReviewCalendar()
    {
        abort_if($this->venueIsOpeningSoon(), 404);
        abort_unless($this->step === 'review', 404);

        $specs = $this->buildSpecsForSubmit();
        if ($specs === []) {
            abort(404);
        }

        $ics = BookingCalendar::icsFromVenueSpecs($this->courtClient, $specs);
        $filename = 'court-booking-preview-'.$this->bookingCalendarDate.'.ics';

        return response()->streamDownload(
            static function () use ($ics): void {
                echo $ics;
            },
            $filename,
            ['Content-Type' => 'text/calendar; charset=utf-8'],
        );
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
        $date = $this->normalizedBookingCalendarDate();
        if ($date === null) {
            return [];
        }

        return VenueBookingSpecsBuilder::selectedTimeWindows($this->selectedSlots, $date);
    }

    #[Computed]
    public function availableCoachesForReview(): Collection
    {
        if (! $this->venueCheckoutShowCoach()) {
            return collect();
        }

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
        return VenueBookingSpecsBuilder::buildSpecsForSubmit(
            $this->courtClient,
            $this->scheduleRows,
            $this->bookingCalendarDate,
            $this->selectedSlots,
            $this->effectiveCoachUserId(),
            $this->coachPaidHours,
            $this->venueCheckoutShowCoach(),
        );
    }

    #[Computed]
    public function reviewEstimateCents(): int
    {
        return (int) array_sum(array_column($this->buildSpecsForSubmit(), 'gross_cents'));
    }

    #[Computed]
    public function reviewCoachFeeCents(): int
    {
        if ($this->effectiveCoachUserId() === '') {
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

    #[Computed]
    public function reviewBookingFeeCents(): int
    {
        $specs = $this->buildSpecsForSubmit();
        if ($specs === []) {
            return 0;
        }

        return BookingFeeService::calculateCentsForSpecs($specs);
    }

    #[Computed]
    public function reviewCheckoutTotalCents(): int
    {
        return $this->reviewEstimateCents + $this->reviewBookingFeeCents;
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

        return GiftCardService::computeAppliedCents($card, $this->reviewCheckoutTotalCents);
    }

    #[Computed]
    public function reviewBalanceAfterGiftCents(): int
    {
        return max(0, $this->reviewCheckoutTotalCents - $this->reviewGiftEstimateCents);
    }

    #[Computed]
    public function canConfigureOpenPlay(): bool
    {
        return count($this->buildSpecsForSubmit()) === 1;
    }

    public function submitRequest()
    {
        if ($this->venueIsOpeningSoon()) {
            $this->addError('submit', 'This venue is not accepting bookings yet.');

            return;
        }

        if (! auth()->check()) {
            $this->addError('submit', 'Please sign in to complete your request.');

            return;
        }

        if (! $this->canSubmitBookings()) {
            $this->addError('submit', 'Only player, coach, and open play host accounts can submit booking requests here. Staff should use the venue or desk app.');

            return;
        }

        $this->step = 'review';

        $this->clearCoachWhenCheckoutHidden();

        $maxCoachH = $this->totalSelectedSlotHours();

        $rules = [
            'paymentReference' => ['nullable', 'string', 'max:128'],
            'paymentProof' => ['nullable', 'image', 'max:5120'],
            'giftCardCode' => ['nullable', 'string', 'max:48'],
            'coachUserId' => ['nullable', 'string', 'uuid', 'exists:users,id'],
            'coachPaidHours' => ['nullable', 'integer', 'min:0', 'max:'.$maxCoachH],
        ];

        if ($this->effectiveCoachUserId() !== '' && $maxCoachH >= 1) {
            $rules['coachPaidHours'] = ['required', 'integer', 'min:1', 'max:'.$maxCoachH];
        }

        if ($this->isOpenPlay) {
            $rules['openPlayMaxSlots'] = ['required', 'integer', 'min:1', 'max:48'];
            $rules['openPlayPublicNotes'] = ['nullable', 'string', 'max:5000'];
            $rules['openPlayHostPaymentDetails'] = ['required', 'string', 'min:3', 'max:5000'];
            $rules['openPlayExternalContact'] = ['nullable', 'string', 'max:5000'];
            $rules['openPlayRefundPolicy'] = ['nullable', 'string', 'max:5000'];
        }

        if ($this->reviewBookingFeeCents > 0) {
            $rules['ackConvenienceFeeNonRefundable'] = ['accepted'];
        }

        $this->validate($rules, [], [
            'paymentReference' => 'payment reference',
            'giftCardCode' => 'gift card code',
            'coachPaidHours' => 'coach paid hours',
            'openPlayMaxSlots' => 'player slots',
            'openPlayPublicNotes' => 'open play notes',
            'openPlayHostPaymentDetails' => 'payment details for players',
            'openPlayExternalContact' => 'refund / contact line',
            'openPlayRefundPolicy' => 'refund policy',
            'ackConvenienceFeeNonRefundable' => 'convenience fee terms',
        ]);

        $specs = $this->buildSpecsForSubmit();
        if ($specs === []) {
            $this->addError('selectedSlots', 'Your selection is no longer available. Adjust the grid and try again.');

            return;
        }

        if ($this->effectiveCoachUserId() !== '') {
            $ids = $this->availableCoachesForReview->pluck('id')->map(fn ($id): string => (string) $id)->all();
            if (! in_array($this->effectiveCoachUserId(), $ids, true)) {
                $this->addError('coachUserId', 'That coach is not available for these courts and times. Choose another coach or change your selection.');

                return;
            }
            $this->clampCoachPaidHours();
        }

        if ($this->isOpenPlay && count($specs) !== 1) {
            $this->addError('isOpenPlay', 'Open play only applies when you book a single court in one continuous block. Adjust your selection and try again.');

            return;
        }

        $openPlayPayload = null;
        if ($this->isOpenPlay && count($specs) === 1) {
            $openPlayPayload = [
                'max_slots' => $this->openPlayMaxSlots,
                'public_notes' => $this->openPlayPublicNotes !== '' ? $this->openPlayPublicNotes : null,
                'host_payment_details' => $this->openPlayHostPaymentDetails,
                'external_contact' => $this->openPlayExternalContact !== '' ? $this->openPlayExternalContact : null,
                'refund_policy' => $this->openPlayRefundPolicy !== '' ? $this->openPlayRefundPolicy : null,
            ];
        }

        $booker = auth()->user();
        if ($booker === null) {
            return;
        }

        $balanceAfterGift = $this->reviewBalanceAfterGiftCents;
        $paymongoOk = config('paymongo.enabled') && (string) config('paymongo.secret_key') !== '';

        if ($balanceAfterGift > 0 && ! $paymongoOk) {
            $this->addError(
                'submit',
                'Online payment is not available yet. Please contact the venue or try again later.',
            );

            return;
        }

        if (
            $this->paymentMethod === Booking::PAYMENT_PAYMONGO
            && $paymongoOk
            && $balanceAfterGift > 0
        ) {
            try {
                $checkoutUrl = PaymongoVenueBookingPayment::createCheckoutRedirect(
                    $this->courtClient,
                    $booker,
                    $this->scheduleRows,
                    $this->bookingCalendarDate,
                    $this->selectedSlots,
                    trim($this->giftCardCode),
                    $this->effectiveCoachUserId() !== '' ? $this->effectiveCoachUserId() : null,
                    $this->coachPaidHours,
                    $this->venueCheckoutShowCoach(),
                    $this->isOpenPlay,
                    $openPlayPayload,
                    $balanceAfterGift,
                );
            } catch (\Throwable $e) {
                report($e);
                $this->addError(
                    'submit',
                    'Unable to start PayMongo checkout. Check your connection and try again.',
                );

                return;
            }

            return redirect()->away($checkoutUrl);
        }

        $paymentMethodForSubmit = $this->paymentMethod;
        if ($balanceAfterGift <= 0 && $paymentMethodForSubmit === Booking::PAYMENT_PAYMONGO) {
            $paymentMethodForSubmit = Booking::PAYMENT_GCASH;
        }

        try {
            $result = PublicVenueBookingSubmission::submit(
                $this->courtClient,
                $booker,
                $specs,
                null,
                $paymentMethodForSubmit,
                $this->paymentReference,
                $this->paymentProof,
                $this->giftCardCode,
                $this->effectiveCoachUserId() !== '' ? $this->effectiveCoachUserId() : null,
                $openPlayPayload,
            );
        } catch (\InvalidArgumentException $e) {
            if ($e->getMessage() === 'No time slots to book.') {
                $this->addError('selectedSlots', 'Your selection is no longer available. Adjust the grid and try again.');
            } elseif (str_starts_with($e->getMessage(), 'That court time')) {
                $this->addError('selectedSlots', $e->getMessage());
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
        $this->paymentMethod = Booking::PAYMENT_PAYMONGO;
        $this->giftCardCode = '';
        $this->coachUserId = '';
        $this->coachPaidHours = 0;
        $this->isOpenPlay = false;
        $this->openPlayMaxSlots = 4;
        $this->openPlayPublicNotes = '';
        $this->openPlayHostPaymentDetails = '';
        $this->openPlayExternalContact = '';
        $this->openPlayRefundPolicy = '';
        $this->step = 'times';

        $giftTotal = (int) array_sum(array_filter(array_map(
            fn (Booking $b) => $b->gift_card_redeemed_cents,
            $bookings,
        )));

        $flash = match ($deskPolicy) {
            CourtClient::DESK_BOOKING_POLICY_AUTO_APPROVE => count($bookings) === 1
                ? 'Booking confirmed automatically (venue setting).'
                : 'Your booking request was confirmed automatically (venue setting) — '.count($bookings).' court time blocks.',
            CourtClient::DESK_BOOKING_POLICY_AUTO_DENY => count($bookings) === 1
                ? 'Booking was not accepted (venue auto-deny setting).'
                : 'Your booking request was not accepted (venue auto-deny setting).',
            default => 'Request sent. The venue will review your booking.',
        };
        if ($giftTotal > 0) {
            $flash .= ' Gift card applied: '.Money::formatMinor($giftTotal).' total.';
        }
        if ($openPlayPayload !== null) {
            $flash .= ' Open play enabled — share the link from Account → Court open play after the venue confirms.';
        }

        $firstBooking = $bookings[0] ?? null;
        if ($firstBooking !== null) {
            $flash .= ' Reference: '.$firstBooking->transactionReference().'.';
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
