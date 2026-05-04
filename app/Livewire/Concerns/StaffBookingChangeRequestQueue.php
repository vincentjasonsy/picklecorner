<?php

namespace App\Livewire\Concerns;

use App\Models\BookingChangeRequest;
use App\Models\CourtClient;
use App\Services\BookingChangeRequestService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;

trait StaffBookingChangeRequestQueue
{
    public ?string $courtClientId = null;

    public string $rejectNote = '';

    public ?string $rejectingId = null;

    public ?string $acceptingId = null;

    public ?string $acceptingType = null;

    public string $acceptRefundCredit = '';

    public string $acceptReviewNote = '';

    public function mountBookingChangeQueue(?string $courtClientId): void
    {
        $this->courtClientId = $courtClientId;
    }

    #[Computed]
    public function pendingChangeRequests()
    {
        if ($this->courtClientId === null || $this->courtClientId === '') {
            return collect();
        }

        return BookingChangeRequest::query()
            ->where('court_client_id', $this->courtClientId)
            ->where('status', BookingChangeRequest::STATUS_PENDING)
            ->with(['booking.court', 'requester'])
            ->orderBy('created_at')
            ->get();
    }

    public function openAccept(string $id): void
    {
        $req = BookingChangeRequest::query()
            ->where('court_client_id', $this->courtClientId)
            ->whereKey($id)
            ->firstOrFail();

        $this->acceptingId = $id;
        $this->acceptingType = $req->type;
        $this->acceptReviewNote = '';
        if ($req->type === BookingChangeRequest::TYPE_REFUND_CREDIT) {
            $offered = $req->offered_credit_cents;
            $this->acceptRefundCredit = $offered !== null ? (string) $offered : '';
        } else {
            $this->acceptRefundCredit = '';
        }
    }

    public function cancelAccept(): void
    {
        $this->acceptingId = null;
        $this->acceptingType = null;
        $this->acceptRefundCredit = '';
        $this->acceptReviewNote = '';
    }

    public function confirmAccept(): void
    {
        $req = BookingChangeRequest::query()
            ->where('court_client_id', $this->courtClientId)
            ->whereKey($this->acceptingId)
            ->firstOrFail();

        $rules = [
            'acceptReviewNote' => ['nullable', 'string', 'max:1000'],
        ];
        if ($req->type === BookingChangeRequest::TYPE_REFUND_CREDIT) {
            $rules['acceptRefundCredit'] = ['nullable', 'regex:/^[0-9]*$/'];
        }
        $this->validate($rules);

        $venue = CourtClient::query()->findOrFail($this->courtClientId);
        abort_unless(BookingChangeRequestService::staffMayReview(Auth::user(), $venue), 403);

        $override = null;
        if ($req->type === BookingChangeRequest::TYPE_REFUND_CREDIT) {
            if ($this->acceptRefundCredit !== '') {
                $override = min(2_000_000_000, max(0, (int) $this->acceptRefundCredit));
            }
        }

        try {
            BookingChangeRequestService::accept(
                $req,
                Auth::user(),
                $override,
                $this->acceptReviewNote !== '' ? $this->acceptReviewNote : null,
            );
        } catch (\Throwable $e) {
            report($e);
            session()->flash('warning', $e->getMessage());

            return;
        }

        $this->cancelAccept();
        unset($this->pendingChangeRequests);

        session()->flash('status', 'Request accepted.');
    }

    public function openReject(string $id): void
    {
        $this->rejectingId = $id;
        $this->rejectNote = '';
    }

    public function cancelReject(): void
    {
        $this->rejectingId = null;
        $this->rejectNote = '';
    }

    public function confirmReject(): void
    {
        $this->validate([
            'rejectNote' => ['nullable', 'string', 'max:1000'],
        ]);

        $req = BookingChangeRequest::query()
            ->where('court_client_id', $this->courtClientId)
            ->whereKey($this->rejectingId)
            ->first();

        if (! $req) {
            $this->cancelReject();

            return;
        }

        $venue = CourtClient::query()->findOrFail($this->courtClientId);
        abort_unless(BookingChangeRequestService::staffMayReview(Auth::user(), $venue), 403);

        BookingChangeRequestService::decline(
            $req,
            Auth::user(),
            $this->rejectNote !== '' ? $this->rejectNote : null,
        );

        $this->cancelReject();
        unset($this->pendingChangeRequests);

        session()->flash('status', 'Request declined.');
    }

    public function renderBookingChangeQueue(): View
    {
        return view('livewire.staff.booking-change-requests');
    }
}
