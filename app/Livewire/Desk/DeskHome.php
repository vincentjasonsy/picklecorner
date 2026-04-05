<?php

namespace App\Livewire\Desk;

use App\Models\Booking;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::desk-portal')]
#[Title('Front desk')]
class DeskHome extends Component
{
    #[Computed]
    public function courtClient()
    {
        return auth()->user()->deskCourtClient?->loadCount('courts');
    }

    #[Computed]
    public function pendingMySubmissions(): int
    {
        $c = $this->courtClient;
        if (! $c) {
            return 0;
        }

        return Booking::query()
            ->where('court_client_id', $c->id)
            ->where('desk_submitted_by', auth()->id())
            ->where('status', Booking::STATUS_PENDING_APPROVAL)
            ->count();
    }

    public function render()
    {
        return view('livewire.desk.desk-home');
    }
}
