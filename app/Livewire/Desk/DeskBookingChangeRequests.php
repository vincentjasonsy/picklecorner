<?php

namespace App\Livewire\Desk;

use App\Livewire\Concerns\StaffBookingChangeRequestQueue;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::desk-portal')]
#[Title('Refund & reschedule requests')]
class DeskBookingChangeRequests extends Component
{
    use StaffBookingChangeRequestQueue;

    public function mount(): void
    {
        $this->mountBookingChangeQueue(auth()->user()->desk_court_client_id);
        abort_unless($this->courtClientId, 403);
    }

    public function render()
    {
        return $this->renderBookingChangeQueue();
    }
}
