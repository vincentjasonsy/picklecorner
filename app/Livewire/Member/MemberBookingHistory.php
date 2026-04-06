<?php

namespace App\Livewire\Member;

use App\Models\Booking;
use App\Models\OpenPlayParticipant;
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

        $openPlayJoins = OpenPlayParticipant::query()
            ->where('user_id', auth()->id())
            ->whereIn('status', [OpenPlayParticipant::STATUS_PENDING, OpenPlayParticipant::STATUS_ACCEPTED])
            ->whereHas('booking', function ($q): void {
                $q->where('starts_at', '>=', now()->subHour())
                    ->whereNotIn('status', [Booking::STATUS_CANCELLED, Booking::STATUS_DENIED]);
            })
            ->with([
                'booking' => fn ($q) => $q->with(['courtClient:id,name,city', 'court:id,name']),
            ])
            ->get()
            ->sortBy(fn (OpenPlayParticipant $p) => $p->booking?->starts_at ?? now())
            ->values();

        return view('livewire.member.member-booking-history', [
            'bookings' => $bookings,
            'openPlayJoins' => $openPlayJoins,
        ]);
    }
}
