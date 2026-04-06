<?php

namespace App\Livewire\Member;

use App\Models\Booking;
use App\Models\OpenPlayParticipant;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::member')]
class MemberCourtOpenPlayHost extends Component
{
    public Booking $booking;

    /** Refund / coordination line shown to players (e.g. Viber, email). */
    public string $externalContactDraft = '';

    /** Refund expectations shown to players (partial refunds, etc.). */
    public string $refundPolicyDraft = '';

    public ?string $closureParticipantId = null;

    /** reject | remove */
    public string $closureAction = 'reject';

    public string $closureReason = '';

    public string $closureMessage = '';

    public function mount(Booking $booking): void
    {
        abort_unless(
            (string) $booking->user_id === (string) auth()->id(),
            403,
        );
        abort_unless($booking->is_open_play, 404);

        $this->booking = $booking->load(['courtClient:id,name,city', 'court:id,name']);
        $this->externalContactDraft = (string) ($booking->open_play_external_contact ?? '');
        $this->refundPolicyDraft = (string) ($booking->open_play_refund_policy ?? '');
    }

    public function saveExternalContact(): void
    {
        $this->validate(['externalContactDraft' => ['nullable', 'string', 'max:5000']]);
        $trim = trim($this->externalContactDraft);
        $this->booking->open_play_external_contact = $trim !== '' ? $trim : null;
        $this->booking->save();
        $this->booking->refresh();
        session()->flash('status', 'Refund / contact info saved.');
    }

    public function saveRefundPolicy(): void
    {
        $this->validate(['refundPolicyDraft' => ['nullable', 'string', 'max:5000']]);
        $trim = trim($this->refundPolicyDraft);
        $this->booking->open_play_refund_policy = $trim !== '' ? $trim : null;
        $this->booking->save();
        $this->booking->refresh();
        session()->flash('status', 'Refund policy saved.');
    }

    public function openClosureModal(string $participantId, string $action): void
    {
        abort_unless(in_array($action, ['reject', 'remove'], true), 404);
        $this->closureParticipantId = $participantId;
        $this->closureAction = $action;
        $this->closureReason = '';
        $this->closureMessage = '';
        $this->resetErrorBag(['closureReason', 'closureMessage']);
    }

    public function closeClosureModal(): void
    {
        $this->closureParticipantId = null;
        $this->closureReason = '';
        $this->closureMessage = '';
    }

    public function submitClosure(): void
    {
        if ($this->closureParticipantId === null) {
            return;
        }

        $this->validate([
            'closureReason' => ['required', Rule::in(OpenPlayParticipant::hostClosureReasonValues())],
            'closureMessage' => ['nullable', 'string', 'max:2000'],
        ], [], [
            'closureReason' => 'reason',
            'closureMessage' => 'note',
        ]);

        if ($this->closureReason === OpenPlayParticipant::CLOSURE_OTHER && trim($this->closureMessage) === '') {
            $this->addError('closureMessage', 'Add a short note when you choose Other.');

            return;
        }

        $action = $this->closureAction;
        $p = $this->authorizeParticipant($this->closureParticipantId, function (OpenPlayParticipant $p) use ($action): bool {
            if ($action === 'reject') {
                return in_array($p->status, [
                    OpenPlayParticipant::STATUS_PENDING,
                    OpenPlayParticipant::STATUS_WAITING_LIST,
                ], true);
            }

            return in_array($p->status, [
                OpenPlayParticipant::STATUS_PENDING,
                OpenPlayParticipant::STATUS_ACCEPTED,
            ], true);
        });

        $msg = trim($this->closureMessage);
        $p->host_closure_reason = $this->closureReason;
        $p->host_closure_message = $msg !== '' ? $msg : null;
        $p->host_closed_at = now();
        $p->status = $action === 'reject'
            ? OpenPlayParticipant::STATUS_REJECTED
            : OpenPlayParticipant::STATUS_REMOVED_BY_HOST;
        $p->save();

        $this->closeClosureModal();
        $this->booking->refresh();
        session()->flash('status', $action === 'reject' ? 'Request declined.' : 'Player removed.');
    }

    public function acceptParticipant(string $participantId): void
    {
        $this->authorizeParticipant($participantId, fn (OpenPlayParticipant $p): bool => in_array($p->status, [
            OpenPlayParticipant::STATUS_PENDING,
            OpenPlayParticipant::STATUS_WAITING_LIST,
        ], true));

        DB::transaction(function () use ($participantId): void {
            $this->booking->refresh();
            $p = OpenPlayParticipant::query()
                ->whereKey($participantId)
                ->where('booking_id', $this->booking->id)
                ->lockForUpdate()
                ->firstOrFail();
            if (! in_array($p->status, [
                OpenPlayParticipant::STATUS_PENDING,
                OpenPlayParticipant::STATUS_WAITING_LIST,
            ], true)) {
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
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'waiting_list' THEN 1 WHEN 'accepted' THEN 2 WHEN 'rejected' THEN 3 WHEN 'removed_by_host' THEN 4 ELSE 5 END")
            ->orderBy('created_at')
            ->get();

        $joinUrl = route('account.court-open-plays.join', $this->booking);

        return view('livewire.member.member-court-open-play-host', [
            'participants' => $participants,
            'joinUrl' => $joinUrl,
        ])->title('Manage open play');
    }
}
