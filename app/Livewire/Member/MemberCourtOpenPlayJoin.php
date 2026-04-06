<?php

namespace App\Livewire\Member;

use App\Models\Booking;
use App\Models\OpenPlayParticipant;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::member')]
class MemberCourtOpenPlayJoin extends Component
{
    public Booking $booking;

    public string $joinerNote = '';

    /** Optional GCash transaction ID so the host can match your payment. */
    public string $gcashReference = '';

    public function mount(Booking $booking): void
    {
        abort_unless($booking->is_open_play, 404);

        $this->booking = $booking->load([
            'courtClient:id,name,city',
            'court:id,name',
            'user:id,name',
        ]);

        if (auth()->check() && (string) auth()->id() !== (string) $booking->user_id) {
            $existing = OpenPlayParticipant::query()
                ->where('booking_id', $booking->id)
                ->where('user_id', auth()->id())
                ->first();
            if ($existing !== null && $existing->gcash_reference !== null && $existing->gcash_reference !== '') {
                $this->gcashReference = $existing->gcash_reference;
            }
        }
    }

    protected function gcashReferenceRules(): array
    {
        return [
            'gcashReference' => ['nullable', 'string', 'max:128'],
        ];
    }

    public function updateGcashReference(): void
    {
        $this->validate($this->gcashReferenceRules(), [], [
            'gcashReference' => 'GCash reference',
        ]);

        $user = auth()->user();
        abort_if((string) $user->id === (string) $this->booking->user_id, 403);

        $p = OpenPlayParticipant::query()
            ->where('booking_id', $this->booking->id)
            ->where('user_id', $user->id)
            ->first();

        if ($p === null || ! in_array($p->status, [OpenPlayParticipant::STATUS_PENDING, OpenPlayParticipant::STATUS_ACCEPTED], true)) {
            return;
        }

        $trim = trim($this->gcashReference);
        $p->gcash_reference = $trim !== '' ? $trim : null;
        $p->save();

        session()->flash('status', 'GCash reference saved.');
    }

    public function requestJoin(): void
    {
        $this->validate(array_merge([
            'joinerNote' => ['nullable', 'string', 'max:2000'],
        ], $this->gcashReferenceRules()), [], [
            'joinerNote' => 'note',
            'gcashReference' => 'GCash reference',
        ]);

        $user = auth()->user();
        abort_if((string) $user->id === (string) $this->booking->user_id, 403);

        $this->booking->refresh();

        $existing = OpenPlayParticipant::query()
            ->where('booking_id', $this->booking->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing !== null) {
            if ($existing->status === OpenPlayParticipant::STATUS_ACCEPTED) {
                $this->addError('join', 'You’re already in for this game.');

                return;
            }
            if ($existing->status === OpenPlayParticipant::STATUS_PENDING) {
                $this->addError('join', 'You already have a pending request.');

                return;
            }
            if (in_array($existing->status, [OpenPlayParticipant::STATUS_REJECTED, OpenPlayParticipant::STATUS_CANCELLED], true)) {
                if (! $this->booking->allowsOpenPlayJoinRequests()) {
                    $this->addError('join', 'No spots left or the host isn’t accepting requests yet.');

                    return;
                }
                $existing->status = OpenPlayParticipant::STATUS_PENDING;
                $existing->joiner_note = $this->joinerNote !== '' ? $this->joinerNote : null;
                $trimRef = trim($this->gcashReference);
                $existing->gcash_reference = $trimRef !== '' ? $trimRef : null;
                $existing->paid_at = null;
                $existing->save();
                session()->flash('status', 'Request sent again.');

                return;
            }
        }

        if (! $this->booking->allowsOpenPlayJoinRequests()) {
            $this->addError('join', 'No spots left or the venue hasn’t confirmed this booking yet.');

            return;
        }

        $trimRef = trim($this->gcashReference);

        OpenPlayParticipant::query()->create([
            'booking_id' => $this->booking->id,
            'user_id' => $user->id,
            'status' => OpenPlayParticipant::STATUS_PENDING,
            'joiner_note' => $this->joinerNote !== '' ? $this->joinerNote : null,
            'gcash_reference' => $trimRef !== '' ? $trimRef : null,
        ]);

        session()->flash('status', 'Request sent to the host.');
    }

    public function leaveOpenPlay(): void
    {
        $user = auth()->user();
        if ((string) $user->id === (string) $this->booking->user_id) {
            return;
        }

        $p = OpenPlayParticipant::query()
            ->where('booking_id', $this->booking->id)
            ->where('user_id', $user->id)
            ->first();

        if ($p === null) {
            return;
        }

        if (! in_array($p->status, [OpenPlayParticipant::STATUS_PENDING, OpenPlayParticipant::STATUS_ACCEPTED], true)) {
            return;
        }

        $p->status = OpenPlayParticipant::STATUS_CANCELLED;
        $p->save();

        $this->booking->refresh();
        session()->flash('status', 'You’ve left this open play.');
    }

    public function render(): View
    {
        $isHost = (string) auth()->id() === (string) $this->booking->user_id;

        $myParticipant = null;
        if (! $isHost) {
            $myParticipant = OpenPlayParticipant::query()
                ->where('booking_id', $this->booking->id)
                ->where('user_id', auth()->id())
                ->first();
        }

        return view('livewire.member.member-court-open-play-join', [
            'isHost' => $isHost,
            'myParticipant' => $myParticipant,
        ])->title('Join open play');
    }
}
