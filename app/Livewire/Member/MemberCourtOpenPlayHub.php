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

        return view('livewire.member.member-court-open-play-hub', [
            'hostedSessions' => $hosted,
        ]);
    }
}
