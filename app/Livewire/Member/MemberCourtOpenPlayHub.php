<?php

namespace App\Livewire\Member;

use App\Models\Booking;
use App\Models\OpenPlayParticipant;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::member')]
#[Title('Court open play')]
class MemberCourtOpenPlayHub extends Component
{
    public function render(): View
    {
        $hosted = Booking::query()
            ->where('user_id', auth()->id())
            ->where('is_open_play', true)
            ->where('starts_at', '>=', now()->subHour())
            ->whereNotIn('status', [Booking::STATUS_CANCELLED, Booking::STATUS_DENIED])
            ->with(['courtClient:id,name,city', 'court:id,name'])
            ->withCount([
                'openPlayParticipants as pending_participants_count' => fn ($q) => $q->where('status', OpenPlayParticipant::STATUS_PENDING),
                'openPlayParticipants as accepted_participants_count' => fn ($q) => $q->where('status', OpenPlayParticipant::STATUS_ACCEPTED),
            ])
            ->orderBy('starts_at')
            ->get();

        $joined = OpenPlayParticipant::query()
            ->where('user_id', auth()->id())
            ->whereIn('status', [
                OpenPlayParticipant::STATUS_PENDING,
                OpenPlayParticipant::STATUS_ACCEPTED,
                OpenPlayParticipant::STATUS_WAITING_LIST,
            ])
            ->whereHas('booking', function ($q): void {
                $q->where('is_open_play', true)
                    ->where('starts_at', '>=', now()->subHour())
                    ->whereNotIn('status', [Booking::STATUS_CANCELLED, Booking::STATUS_DENIED]);
            })
            ->with([
                'booking' => fn ($q) => $q->with([
                    'courtClient:id,name,city',
                    'court:id,name',
                    'user:id,name',
                ])->withCount([
                    'openPlayParticipants as accepted_joiners_count' => fn ($q2) => $q2->where('status', OpenPlayParticipant::STATUS_ACCEPTED),
                ]),
            ])
            ->get()
            ->sortBy(fn (OpenPlayParticipant $p) => $p->booking?->starts_at ?? now())
            ->values();

        return view('livewire.member.member-court-open-play-hub', [
            'hostedSessions' => $hosted,
            'joinedRows' => $joined,
        ]);
    }
}
