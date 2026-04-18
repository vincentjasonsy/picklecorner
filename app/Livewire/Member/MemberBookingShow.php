<?php

namespace App\Livewire\Member;

use App\Models\Booking;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
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

    /**
     * All rows in the same member submission (shared booking_request_id), ordered by start time.
     * Recomputed on each request to avoid persisting a list of models in Livewire state.
     */
    #[Computed]
    public function requestBookings(): Collection
    {
        $b = $this->booking;
        $rid = $b->booking_request_id;
        if ($rid !== null && $rid !== '') {
            return Booking::query()
                ->where('user_id', auth()->id())
                ->where('court_client_id', $b->court_client_id)
                ->where('booking_request_id', $rid)
                ->with(['court'])
                ->orderBy('starts_at')
                ->get();
        }

        return collect([$b]);
    }

    public function render(): View
    {
        return view('livewire.member.member-booking-show');
    }
}
