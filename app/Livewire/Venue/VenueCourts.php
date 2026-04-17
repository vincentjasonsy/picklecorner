<?php

namespace App\Livewire\Venue;

use App\Models\Court;
use App\Models\CourtChangeRequest;
use App\Services\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::venue-portal')]
#[Title('Courts')]
class VenueCourts extends Component
{
    public string $removeCourtId = '';

    #[Computed]
    public function courtClient()
    {
        return auth()->user()->administeredCourtClient?->load(['courts.galleryImages']);
    }

    #[Computed]
    public function pendingRequests()
    {
        $c = $this->courtClient;
        if (! $c) {
            return collect();
        }

        return CourtChangeRequest::query()
            ->where('court_client_id', $c->id)
            ->where('status', CourtChangeRequest::STATUS_PENDING)
            ->with(['court'])
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Same column order as booking / desk grids (outdoor ↑, indoor ↓ by court number in the name).
     *
     * @return Collection<int, Court>
     */
    #[Computed]
    public function courtsOrderedForGrid(): Collection
    {
        $c = $this->courtClient;
        if (! $c) {
            return collect();
        }

        return Court::orderedForGridColumns($c->courts);
    }

    public function requestAddOutdoor(): void
    {
        $this->createAddRequest(Court::ENV_OUTDOOR);
    }

    public function requestAddIndoor(): void
    {
        $this->createAddRequest(Court::ENV_INDOOR);
    }

    protected function createAddRequest(string $environment): void
    {
        $c = $this->courtClient;
        if (! $c) {
            return;
        }

        CourtChangeRequest::query()->create([
            'court_client_id' => $c->id,
            'requested_by_user_id' => auth()->id(),
            'action' => CourtChangeRequest::ACTION_ADD_COURT,
            'environment' => $environment,
            'court_id' => null,
            'status' => CourtChangeRequest::STATUS_PENDING,
        ]);

        ActivityLogger::log(
            'court_change.requested',
            ['action' => 'add_court', 'environment' => $environment],
            $c,
            'Court add request submitted',
        );

        unset($this->pendingRequests);

        session()->flash('status', 'Add-court request sent to platform admin for approval.');
    }

    public function requestRemoveCourt(): void
    {
        $c = $this->courtClient;
        if (! $c) {
            return;
        }

        $this->validate([
            'removeCourtId' => [
                'required',
                'uuid',
                Rule::exists('courts', 'id')->where('court_client_id', $c->id),
            ],
        ], [], ['removeCourtId' => 'court']);

        CourtChangeRequest::query()->create([
            'court_client_id' => $c->id,
            'requested_by_user_id' => auth()->id(),
            'action' => CourtChangeRequest::ACTION_REMOVE_COURT,
            'environment' => null,
            'court_id' => $this->removeCourtId,
            'status' => CourtChangeRequest::STATUS_PENDING,
        ]);

        ActivityLogger::log(
            'court_change.requested',
            ['action' => 'remove_court', 'court_id' => $this->removeCourtId],
            $c,
            'Court removal request submitted',
        );

        $this->removeCourtId = '';
        unset($this->pendingRequests);

        session()->flash('status', 'Removal request sent to platform admin for approval.');
    }

    /**
     * Withdraw a pending add-court (or remove-court) request before platform admin reviews it.
     */
    public function withdrawPendingRequest(string $id): void
    {
        $c = $this->courtClient;
        if (! $c) {
            return;
        }

        $req = CourtChangeRequest::query()
            ->where('id', $id)
            ->where('court_client_id', $c->id)
            ->where('status', CourtChangeRequest::STATUS_PENDING)
            ->first();

        if (! $req) {
            return;
        }

        ActivityLogger::log(
            'court_change.withdrawn',
            [
                'request_id' => $req->id,
                'action' => $req->action,
                'environment' => $req->environment,
                'court_id' => $req->court_id,
            ],
            $c,
            'Court change request withdrawn by venue',
        );

        $req->delete();

        unset($this->pendingRequests);

        session()->flash('status', 'Pending request withdrawn.');
    }

    public function render(): View
    {
        return view('livewire.venue.venue-courts');
    }
}
