<?php

namespace App\Livewire\Venue;

use App\Livewire\Admin\CourtClientEdit;
use App\Models\CourtClient;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('layouts::venue-portal')]
#[Title('Venue settings')]
class VenueCourtClientManage extends CourtClientEdit
{
    public bool $isVenuePortal = true;

    public function mount(?CourtClient $courtClient = null): void
    {
        $client = auth()->user()->administeredCourtClient;
        abort_unless($client, 404);
        parent::mount($client);
    }
}
