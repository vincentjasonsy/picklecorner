<?php

namespace App\Livewire\Venue;

use App\Livewire\Admin\CourtClientManualBooking;
use App\Models\CourtClient;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('layouts::venue-portal')]
#[Title('Manual booking')]
class VenueManualBooking extends CourtClientManualBooking
{
    public function mount(?CourtClient $courtClient = null): void
    {
        $client = auth()->user()->administeredCourtClient;
        abort_unless($client, 404);
        parent::mount($client);
    }

    public function manualBookingPortal(): string
    {
        return 'venue';
    }

    public function manualBookingBackUrl(): string
    {
        return route('venue.settings');
    }
}
