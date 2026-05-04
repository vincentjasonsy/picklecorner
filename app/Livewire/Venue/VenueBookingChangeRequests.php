<?php

namespace App\Livewire\Venue;

use App\Livewire\Concerns\StaffBookingChangeRequestQueue;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::venue-portal')]
#[Title('Refund & reschedule requests')]
class VenueBookingChangeRequests extends Component
{
    use StaffBookingChangeRequestQueue;

    public function mount(): void
    {
        $this->mountBookingChangeQueue(auth()->user()->administeredCourtClient?->id);
        abort_unless($this->courtClientId, 403);
    }

    public function render()
    {
        return $this->renderBookingChangeQueue();
    }
}
