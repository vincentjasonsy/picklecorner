<?php

namespace App\Livewire\Venue;

use App\Models\Booking;
use App\Models\CourtChangeRequest;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::venue-portal')]
#[Title('Overview')]
class VenueHome extends Component
{
    #[Computed]
    public function courtClient()
    {
        $c = auth()->user()->administeredCourtClient;
        if (! $c) {
            return null;
        }

        return $c->fresh()->loadCount('courts');
    }

    #[Computed]
    public function pendingDeskBookings(): int
    {
        $c = $this->courtClient;
        if (! $c) {
            return 0;
        }

        return Booking::query()
            ->where('court_client_id', $c->id)
            ->where('status', Booking::STATUS_PENDING_APPROVAL)
            ->count();
    }

    #[Computed]
    public function pendingCourtRequests(): int
    {
        $c = $this->courtClient;
        if (! $c) {
            return 0;
        }

        return CourtChangeRequest::query()
            ->where('court_client_id', $c->id)
            ->where('status', CourtChangeRequest::STATUS_PENDING)
            ->count();
    }

    public function render()
    {
        return view('livewire.venue.venue-home');
    }
}
