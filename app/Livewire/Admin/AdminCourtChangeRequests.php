<?php

namespace App\Livewire\Admin;

use App\Models\Court;
use App\Models\CourtChangeRequest;
use App\Models\CourtClient;
use App\Services\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::admin')]
#[Title('Court change requests')]
class AdminCourtChangeRequests extends Component
{
    public string $rejectNote = '';

    public ?string $rejectingId = null;

    #[Computed]
    public function pendingRequests()
    {
        return CourtChangeRequest::query()
            ->where('status', CourtChangeRequest::STATUS_PENDING)
            ->with(['courtClient', 'requester', 'court'])
            ->orderBy('created_at')
            ->get();
    }

    public function approve(string $id): void
    {
        $req = CourtChangeRequest::query()
            ->where('id', $id)
            ->where('status', CourtChangeRequest::STATUS_PENDING)
            ->firstOrFail();

        try {
            if ($req->action === CourtChangeRequest::ACTION_ADD_COURT) {
                $this->applyApprovedAddCourt($req);
            } elseif ($req->action === CourtChangeRequest::ACTION_REMOVE_COURT) {
                $this->applyApprovedRemoveCourt($req);
            } else {
                session()->flash('warning', 'Unknown request action.');

                return;
            }
        } catch (\Throwable $e) {
            report($e);
            session()->flash('warning', $e->getMessage());

            return;
        }

        $req->status = CourtChangeRequest::STATUS_APPROVED;
        $req->reviewed_by_user_id = auth()->id();
        $req->reviewed_at = now();
        $req->review_note = null;
        $req->save();

        ActivityLogger::log(
            'court_change.approved',
            ['request_id' => $req->id, 'action' => $req->action],
            $req->courtClient,
            'Court change request approved',
        );

        unset($this->pendingRequests);

        session()->flash('status', 'Request approved and applied.');
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
            'rejectNote' => ['required', 'string', 'max:500'],
        ]);

        $req = CourtChangeRequest::query()
            ->where('id', $this->rejectingId)
            ->where('status', CourtChangeRequest::STATUS_PENDING)
            ->first();

        if (! $req) {
            $this->cancelReject();

            return;
        }

        $req->status = CourtChangeRequest::STATUS_REJECTED;
        $req->reviewed_by_user_id = auth()->id();
        $req->reviewed_at = now();
        $req->review_note = trim($this->rejectNote);
        $req->save();

        ActivityLogger::log(
            'court_change.rejected',
            ['request_id' => $req->id],
            $req->courtClient,
            'Court change request rejected',
        );

        $this->cancelReject();
        unset($this->pendingRequests);

        session()->flash('status', 'Request rejected.');
    }

    protected function applyApprovedAddCourt(CourtChangeRequest $req): void
    {
        $env = $req->environment;
        if (! in_array($env, [Court::ENV_INDOOR, Court::ENV_OUTDOOR], true)) {
            throw new \InvalidArgumentException('Invalid environment on request.');
        }

        $client = CourtClient::query()->with(['courts'])->findOrFail($req->court_client_id);

        $courts = $client->courts->sortBy([
            fn (Court $c) => $c->environment === Court::ENV_INDOOR ? 1 : 0,
            fn (Court $c) => $c->sort_order,
        ])->values();

        $rows = $courts->map(fn (Court $c) => [
            'id' => $c->id,
            'environment' => $c->environment,
        ])->all();
        $rows[] = ['id' => null, 'environment' => $env];

        $outdoor = [];
        $indoor = [];
        foreach ($rows as $row) {
            if (($row['environment'] ?? '') === Court::ENV_INDOOR) {
                $indoor[] = $row;
            } else {
                $outdoor[] = $row;
            }
        }
        $rows = array_merge($outdoor, $indoor);

        $outdoorN = 0;
        $indoorN = 0;
        $names = [];
        foreach ($rows as $row) {
            if (($row['environment'] ?? '') === Court::ENV_INDOOR) {
                $indoorN++;
                $names[] = Court::defaultName(Court::ENV_INDOOR, $indoorN);
            } else {
                $outdoorN++;
                $names[] = Court::defaultName(Court::ENV_OUTDOOR, $outdoorN);
            }
        }

        DB::transaction(function () use ($rows, $names, $client): void {
            foreach ($rows as $i => $row) {
                $base = [
                    'court_client_id' => $client->id,
                    'name' => $names[$i],
                    'environment' => $row['environment'],
                    'sort_order' => $i,
                ];
                if (! empty($row['id'])) {
                    Court::query()
                        ->where('id', $row['id'])
                        ->where('court_client_id', $client->id)
                        ->update($base);
                } else {
                    Court::query()->create(array_merge($base, [
                        'hourly_rate_cents' => null,
                        'peak_hourly_rate_cents' => null,
                        'is_available' => true,
                    ]));
                }
            }
        });
    }

    protected function applyApprovedRemoveCourt(CourtChangeRequest $req): void
    {
        $courtId = $req->court_id;
        if ($courtId === null || $courtId === '') {
            throw new \InvalidArgumentException('Missing court on removal request.');
        }

        $court = Court::query()
            ->where('id', $courtId)
            ->where('court_client_id', $req->court_client_id)
            ->first();

        if (! $court) {
            throw new \InvalidArgumentException('Court not found for this venue.');
        }

        if ($court->bookings()->exists()) {
            throw new \InvalidArgumentException('Cannot remove a court that has bookings.');
        }

        DB::transaction(function () use ($court, $req): void {
            $court->delete();

            $client = CourtClient::query()->with(['courts'])->findOrFail($req->court_client_id);
            $courts = $client->courts->sortBy([
                fn (Court $c) => $c->environment === Court::ENV_INDOOR ? 1 : 0,
                fn (Court $c) => $c->sort_order,
            ])->values();

            $rows = $courts->map(fn (Court $c) => [
                'id' => $c->id,
                'environment' => $c->environment,
            ])->all();

            $outdoorN = 0;
            $indoorN = 0;
            $names = [];
            foreach ($rows as $row) {
                if (($row['environment'] ?? '') === Court::ENV_INDOOR) {
                    $indoorN++;
                    $names[] = Court::defaultName(Court::ENV_INDOOR, $indoorN);
                } else {
                    $outdoorN++;
                    $names[] = Court::defaultName(Court::ENV_OUTDOOR, $outdoorN);
                }
            }

            foreach ($rows as $i => $row) {
                Court::query()
                    ->where('id', $row['id'])
                    ->where('court_client_id', $client->id)
                    ->update([
                        'name' => $names[$i],
                        'environment' => $row['environment'],
                        'sort_order' => $i,
                    ]);
            }
        });
    }

    public function render(): View
    {
        return view('livewire.admin.admin-court-change-requests');
    }
}
