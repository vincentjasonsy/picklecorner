<?php

namespace App\Livewire\Desk;

use App\Livewire\Admin\CourtClientManualBooking;
use App\Models\CourtClient;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('layouts::desk-portal')]
#[Title('New booking request')]
class DeskManualBooking extends CourtClientManualBooking
{
    public function mount(?CourtClient $courtClient = null): void
    {
        $client = auth()->user()->deskCourtClient;
        abort_unless($client, 404);
        parent::mount($client);
    }

    public function manualBookingPortal(): string
    {
        return 'desk';
    }

    public function manualBookingBackUrl(): string
    {
        return route('desk.home');
    }

    protected function isDeskSubmission(): bool
    {
        return true;
    }
}
