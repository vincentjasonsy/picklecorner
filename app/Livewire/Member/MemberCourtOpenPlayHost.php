<?php

namespace App\Livewire\Member;

use App\Models\Booking;
use App\Models\OpenPlayParticipant;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::member')]
class MemberCourtOpenPlayHost extends Component
{
    public Booking $booking;

    public function mount(Booking $booking): void
    {
        abort_unless(
            (string) $booking->user_id === (string) auth()->id(),
            403,
        );
        abort_unless($booking->is_open_play, 404);

        $this->booking = $booking->load(['courtClient:id,name,city', 'court:id,name']);
    }

    public function acceptParticipant(string $participantId): void
    {
        $this->authorizeParticipant($participantId, fn (OpenPlayParticipant $p): bool => $p->status === OpenPlayParticipant::STATUS_PENDING);

        DB::transaction(function () use ($participantId): void {
            $this->booking->refresh();
            $p = OpenPlayParticipant::query()
                ->whereKey($participantId)
                ->where('booking_id', $this->booking->id)
                ->lockForUpdate()
                ->firstOrFail();
            if ($p->status !== OpenPlayParticipant::STATUS_PENDING) {
                return;
            }
            if ($this->booking->openPlaySlotsRemaining() < 1) {
                return;
            }
            $p->status = OpenPlayParticipant::STATUS_ACCEPTED;
            $p->save();
        });

        $this->booking->refresh();
        $st = OpenPlayParticipant::query()->whereKey($participantId)->value('status');
        session()->flash('status', $st === OpenPlayParticipant::STATUS_ACCEPTED
            ? 'Player accepted.'
            : 'Could not accept — no spots left or the request changed.');
    }

    public function rejectParticipant(string $participantId): void
    {
        $this->authorizeParticipant($participantId, fn (OpenPlayParticipant $p): bool => $p->status === OpenPlayParticipant::STATUS_PENDING);

        OpenPlayParticipant::query()
            ->whereKey($participantId)
            ->where('booking_id', $this->booking->id)
            ->update(['status' => OpenPlayParticipant::STATUS_REJECTED]);

        session()->flash('status', 'Request declined.');
    }

    public function removeParticipant(string $participantId): void
    {
        $this->authorizeParticipant($participantId, fn (OpenPlayParticipant $p): bool => in_array($p->status, [
            OpenPlayParticipant::STATUS_PENDING,
            OpenPlayParticipant::STATUS_ACCEPTED,
        ], true));

        OpenPlayParticipant::query()
            ->whereKey($participantId)
            ->where('booking_id', $this->booking->id)
            ->delete();

        session()->flash('status', 'Removed from this open play.');
    }

    public function toggleParticipantPaid(string $participantId): void
    {
        $p = $this->authorizeParticipant($participantId, fn (OpenPlayParticipant $p): bool => $p->status === OpenPlayParticipant::STATUS_ACCEPTED);

        $wasPaid = $p->paid_at !== null;
        $p->paid_at = $wasPaid ? null : now();
        $p->save();

        session()->flash('status', $wasPaid ? 'Marked as unpaid.' : 'Marked as paid.');
    }

    /**
     * @param  callable(OpenPlayParticipant): bool  $statusOk
     */
    private function authorizeParticipant(string $participantId, callable $statusOk): OpenPlayParticipant
    {
        $p = OpenPlayParticipant::query()
            ->whereKey($participantId)
            ->where('booking_id', $this->booking->id)
            ->firstOrFail();

        abort_unless($statusOk($p), 403);

        return $p;
    }

    public function render(): View
    {
        $participants = OpenPlayParticipant::query()
            ->where('booking_id', $this->booking->id)
            ->with('user:id,name,email')
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'accepted' THEN 1 WHEN 'rejected' THEN 2 ELSE 3 END")
            ->orderBy('created_at')
            ->get();

        $joinUrl = route('account.court-open-plays.join', $this->booking);

        return view('livewire.member.member-court-open-play-host', [
            'participants' => $participants,
            'joinUrl' => $joinUrl,
        ])->title('Manage open play');
    }
}
