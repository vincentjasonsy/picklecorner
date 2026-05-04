<?php

namespace App\Livewire\Member;

use App\Models\Booking;
use App\Models\BookingChangeRequest;
use App\Models\UserVenueCredit;
use App\Services\BookingChangeRequestService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::member')]
#[Title('Booking details')]
class MemberBookingShow extends Component
{
    public Booking $booking;

    public string $refundNote = '';

    public string $rescheduleNote = '';

    public string $rescheduleDate = '';

    public string $rescheduleStartTime = '';

    public function mount(Booking $booking): void
    {
        abort_unless($booking->user_id === auth()->id(), 403);

        $this->booking = $booking->load([
            'courtClient',
            'court',
            'coach:id,name,email',
            'giftCard:id,code',
            'changeRequests' => fn ($q) => $q->orderByDesc('created_at'),
        ]);

        if ($this->booking->starts_at) {
            $tz = config('app.timezone', 'UTC');
            $this->rescheduleDate = $this->booking->starts_at->copy()->timezone($tz)->format('Y-m-d');
            $this->rescheduleStartTime = $this->booking->starts_at->copy()->timezone($tz)->format('H:i');
        }
    }

    /**
     * All rows in the same member submission (shared booking_request_id), ordered by start time.
     * Recomputed on each request to avoid persisting a list of models in Livewire state.
     */
    #[Computed]
    public function requestBookings(): Collection
    {
        $b = $this->booking;
        $rid = $b->booking_request_id;
        if ($rid !== null && $rid !== '') {
            return Booking::query()
                ->where('user_id', auth()->id())
                ->where('court_client_id', $b->court_client_id)
                ->where('booking_request_id', $rid)
                ->with(['court'])
                ->orderBy('starts_at')
                ->get();
        }

        return collect([$b]);
    }

    #[Computed]
    public function venueCreditBalanceCents(): int
    {
        $currency = $this->booking->currency ?? 'PHP';

        return (int) (UserVenueCredit::query()
            ->where('user_id', auth()->id())
            ->where('currency', $currency)
            ->value('balance_cents') ?? 0);
    }

    #[Computed]
    public function pendingChangeRequest(): ?BookingChangeRequest
    {
        return $this->booking->changeRequests
            ->firstWhere('status', BookingChangeRequest::STATUS_PENDING);
    }

    #[Computed]
    public function mayRequestChange(): bool
    {
        return BookingChangeRequestService::memberMayPlaceRequest($this->booking);
    }

    #[Computed]
    public function defaultRefundCreditCents(): int
    {
        return BookingChangeRequestService::defaultRefundCreditCents($this->booking);
    }

    public function submitRefundRequest(): void
    {
        $this->validate([
            'refundNote' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            BookingChangeRequestService::submitRefund($this->booking, auth()->user(), $this->refundNote);
        } catch (\InvalidArgumentException $e) {
            session()->flash('warning', $e->getMessage());

            return;
        }

        $this->refreshBookingChangeRequests();
        $this->refundNote = '';
        unset($this->pendingChangeRequest, $this->mayRequestChange);
        session()->flash('status', 'Refund request sent. The venue will review it.');
    }

    public function submitRescheduleRequest(): void
    {
        $this->validate([
            'rescheduleDate' => ['required', 'date'],
            'rescheduleStartTime' => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'rescheduleNote' => ['nullable', 'string', 'max:2000'],
        ]);

        $tz = config('app.timezone', 'UTC');
        $newStart = Carbon::parse($this->rescheduleDate.' '.$this->rescheduleStartTime.':00', $tz);
        $duration = (int) $this->booking->starts_at->diffInMinutes($this->booking->ends_at);
        $newEnd = $newStart->copy()->addMinutes($duration);

        try {
            BookingChangeRequestService::submitReschedule(
                $this->booking,
                auth()->user(),
                $newStart,
                $newEnd,
                $this->rescheduleNote,
            );
        } catch (\InvalidArgumentException $e) {
            $this->addError('reschedule', $e->getMessage());

            return;
        }

        $this->refreshBookingChangeRequests();
        unset($this->pendingChangeRequest, $this->mayRequestChange);
        session()->flash('status', 'Reschedule request sent. The venue will review it.');
    }

    public function withdrawPendingRequest(): void
    {
        $pending = $this->pendingChangeRequest;
        if (! $pending) {
            return;
        }

        try {
            BookingChangeRequestService::withdraw($pending, auth()->user());
        } catch (\InvalidArgumentException $e) {
            session()->flash('warning', $e->getMessage());

            return;
        }

        $this->refreshBookingChangeRequests();
        unset($this->pendingChangeRequest, $this->mayRequestChange);
        session()->flash('status', 'Request withdrawn.');
    }

    protected function refreshBookingChangeRequests(): void
    {
        $this->booking->load([
            'changeRequests' => fn ($q) => $q->orderByDesc('created_at'),
        ]);
    }

    public function render(): View
    {
        return view('livewire.member.member-booking-show');
    }
}
