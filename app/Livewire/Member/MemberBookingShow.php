<?php

namespace App\Livewire\Member;

use App\Models\Booking;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::member')]
#[Title('Booking details')]
class MemberBookingShow extends Component
{
    public Booking $booking;

    public function mount(Booking $booking): void
    {
        abort_unless($booking->user_id === auth()->id(), 403);

        $this->booking = $booking->load([
            'courtClient',
            'court',
            'coach:id,name,email',
            'giftCard:id,code',
        ]);
    }

    public function render(): View
    {
        return view('livewire.member.member-booking-show');
    }
}
