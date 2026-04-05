<?php

namespace App\Livewire\Venue;

use App\Models\Booking;
use App\Services\ActivityLogger;
use Illuminate\Contracts\View\View;
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

    #[Computed]
    public function pendingBookings()
    {
        $c = $this->courtClient;
        if (! $c) {
            return collect();
        }

        return Booking::query()
            ->where('court_client_id', $c->id)
            ->where('status', Booking::STATUS_PENDING_APPROVAL)
            ->with(['user', 'court', 'deskSubmitter'])
            ->orderBy('starts_at')
            ->get();
    }

    public function approve(string $bookingId): void
    {
        $booking = $this->findPendingBooking($bookingId);
        if (! $booking) {
            return;
        }

        $booking->status = Booking::STATUS_CONFIRMED;
        $booking->save();

        ActivityLogger::log(
            'booking.desk_approved',
            ['booking_id' => $booking->id],
            $booking,
            'Desk booking request approved',
            null,
            $booking->court_client_id,
        );

        unset($this->pendingBookings);

        session()->flash('status', 'Booking approved.');
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

        $note = trim($this->denyReason);
        $booking->status = Booking::STATUS_DENIED;
        $prevNotes = $booking->notes;
        $booking->notes = $prevNotes
            ? $prevNotes."\n\nDenied: ".$note
            : 'Denied: '.$note;
        $booking->save();

        ActivityLogger::log(
            'booking.desk_denied',
            ['booking_id' => $booking->id, 'reason' => $note],
            $booking,
            'Desk booking request denied',
            null,
            $booking->court_client_id,
        );

        $this->denyBookingId = null;
        $this->denyReason = '';
        unset($this->pendingBookings);

        session()->flash('status', 'Booking request denied.');
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
