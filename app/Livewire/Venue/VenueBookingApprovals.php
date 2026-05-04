<?php

namespace App\Livewire\Venue;

use App\Models\Booking;
use App\Models\CourtClient;
use App\Models\User;
use App\Models\UserVenueCreditLedgerEntry;
use App\Services\ActivityLogger;
use App\Services\BookingChangeRequestService;
use App\Services\UserVenueCreditService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::venue-portal')]
#[Title('Manual booking requests')]
class VenueBookingApprovals extends Component
{
    public string $denyReason = '';

    public ?string $denyBookingId = null;

    #[Computed]
    public function courtClient()
    {
        $c = auth()->user()->administeredCourtClient;

        return $c ? $c->fresh() : null;
    }

    /**
     * Pending bookings grouped by a single submission (same booking_request_id).
     * Legacy rows without booking_request_id are one group each.
     *
     * @return Collection<int, Collection<int, Booking>>
     */
    #[Computed]
    public function pendingBookingGroups(): Collection
    {
        $c = $this->courtClient;
        if (! $c) {
            return collect();
        }

        $all = Booking::query()
            ->where('court_client_id', $c->id)
            ->where('status', Booking::STATUS_PENDING_APPROVAL)
            ->with(['user', 'court', 'deskSubmitter'])
            ->orderBy('starts_at')
            ->get();

        return $all
            ->groupBy(fn (Booking $b) => $b->booking_request_id !== null && $b->booking_request_id !== ''
                ? 'req:'.$b->booking_request_id
                : 'single:'.$b->id)
            ->map(fn (Collection $bookings) => $bookings->sortBy('starts_at')->values())
            ->values();
    }

    public function approve(string $bookingId): void
    {
        $booking = $this->findPendingBooking($bookingId);
        if (! $booking) {
            return;
        }

        $group = $this->pendingBookingsInSameRequest($booking);
        $ids = $group->pluck('id')->all();

        DB::transaction(function () use ($ids): void {
            Booking::query()->whereIn('id', $ids)->update(['status' => Booking::STATUS_CONFIRMED]);
        });

        $first = $group->first();
        ActivityLogger::log(
            'booking.desk_approved',
            [
                'booking_ids' => $ids,
                'booking_request_id' => $first?->booking_request_id,
            ],
            $first,
            count($ids) === 1
                ? 'Desk booking request approved'
                : 'Desk booking request approved ('.count($ids).' courts)',
            null,
            $first?->court_client_id,
        );

        unset($this->pendingBookingGroups);

        $ref = $first?->transactionReference() ?? '';
        $refPart = $ref !== '' ? ' Reference: '.$ref.'.' : '';

        session()->flash(
            'status',
            (count($ids) === 1
                ? 'Booking request approved.'
                : 'Booking request approved ('.count($ids).' courts).').$refPart,
        );
    }

    public function openDeny(string $bookingId): void
    {
        $this->denyBookingId = $bookingId;
        $this->denyReason = '';
    }

    public function cancelDeny(): void
    {
        $this->denyBookingId = null;
        $this->denyReason = '';
    }

    public function confirmDeny(): void
    {
        $this->validate([
            'denyReason' => ['required', 'string', 'max:500'],
        ]);

        $booking = $this->findPendingBooking($this->denyBookingId ?? '');
        if (! $booking) {
            return;
        }

        $group = $this->pendingBookingsInSameRequest($booking);
        $note = trim($this->denyReason);
        $denyLine = 'Denied: '.$note;

        $creditIssuedCents = 0;

        DB::transaction(function () use ($group, $denyLine, &$creditIssuedCents): void {
            foreach ($group as $b) {
                $prevNotes = $b->notes;
                $b->status = Booking::STATUS_DENIED;
                $b->notes = $prevNotes
                    ? $prevNotes."\n\n".$denyLine
                    : $denyLine;
                $b->save();
            }

            $first = $group->first();
            if ($first === null || $first->user_id === null) {
                return;
            }

            $venue = CourtClient::query()->find($first->court_client_id);
            if ($venue === null) {
                return;
            }

            $totalCredit = BookingChangeRequestService::totalDeskDenialCreditCents($group);
            if ($totalCredit <= 0) {
                return;
            }

            $member = User::query()->find($first->user_id);
            if ($member === null) {
                return;
            }

            UserVenueCreditService::addCredit(
                $member,
                $venue,
                $totalCredit,
                UserVenueCreditLedgerEntry::ENTRY_TYPE_DESK_DENIAL,
                $first,
                'Booking request denied; credit returned to your account.',
            );
            $creditIssuedCents = $totalCredit;
        });

        $first = $group->first();
        $ids = $group->pluck('id')->all();

        ActivityLogger::log(
            'booking.desk_denied',
            [
                'booking_ids' => $ids,
                'booking_request_id' => $first?->booking_request_id,
                'reason' => $note,
                'credit_issued_cents' => $creditIssuedCents > 0 ? $creditIssuedCents : null,
            ],
            $first,
            count($ids) === 1
                ? 'Desk booking request denied'
                : 'Desk booking request denied ('.count($ids).' courts)',
            null,
            $first?->court_client_id,
        );

        $this->denyBookingId = null;
        $this->denyReason = '';
        unset($this->pendingBookingGroups);

        $ref = $first?->transactionReference() ?? '';
        $refPart = $ref !== '' ? ' Reference: '.$ref.'.' : '';

        $baseMsg = count($ids) === 1
            ? 'Booking request denied.'
            : 'Booking request denied ('.count($ids).' courts).';
        if ($creditIssuedCents > 0 && $first?->user_id !== null) {
            $baseMsg .= ' Account credit was issued to the booker.';
        }

        session()->flash(
            'status',
            $baseMsg.$refPart,
        );
    }

    protected function findPendingBooking(string $id): ?Booking
    {
        $c = $this->courtClient;
        if (! $c || $id === '') {
            return null;
        }

        return Booking::query()
            ->where('id', $id)
            ->where('court_client_id', $c->id)
            ->where('status', Booking::STATUS_PENDING_APPROVAL)
            ->first();
    }

    /**
     * @return Collection<int, Booking>
     */
    protected function pendingBookingsInSameRequest(Booking $booking): Collection
    {
        if ($booking->booking_request_id === null || $booking->booking_request_id === '') {
            return collect([$booking]);
        }

        return Booking::query()
            ->where('court_client_id', $booking->court_client_id)
            ->where('status', Booking::STATUS_PENDING_APPROVAL)
            ->where('booking_request_id', $booking->booking_request_id)
            ->orderBy('starts_at')
            ->get();
    }

    public function slotLabel(Booking $b): string
    {
        $tz = config('app.timezone', 'UTC');

        return $b->starts_at?->timezone($tz)->isoFormat('MMM D, YYYY h:mm a')
            .' – '
            .$b->ends_at?->timezone($tz)->isoFormat('h:mm a');
    }

    public function render(): View
    {
        return view('livewire.venue.venue-booking-approvals');
    }
}
