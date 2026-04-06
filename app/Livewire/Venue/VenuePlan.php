<?php

namespace App\Livewire\Venue;

use App\Models\CourtClient;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::venue-portal')]
#[Title('Plan & billing')]
class VenuePlan extends Component
{
    public function render(): View
    {
        /** @var CourtClient|null $client */
        $client = auth()->user()->administeredCourtClient;
        abort_unless($client !== null, 404);

        return view('livewire.venue.venue-plan', [
            'courtClient' => $client->fresh(),
        ]);
    }
}
