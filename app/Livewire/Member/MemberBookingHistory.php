<?php

namespace App\Livewire\Member;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::member')]
#[Title('My games')]
class MemberBookingHistory extends Component
{
    use WithPagination;

    public function render(): View
    {
        $bookings = auth()->user()->bookings()
            ->with(['courtClient:id,name,city', 'court:id,name'])
            ->orderByDesc('starts_at')
            ->paginate(12);

        return view('livewire.member.member-booking-history', [
            'bookings' => $bookings,
        ]);
    }
}
