<?php

namespace App\Livewire\Admin;

use App\Models\CourtClientGalleryImage;
use App\Models\CourtGalleryImage;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::admin')]
#[Title('Gallery approvals')]
class GalleryImageApprovals extends Component
{
    public function approveVenueImage(string $id): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $row = CourtClientGalleryImage::query()->where('id', $id)->first();
        abort_unless($row !== null, 404);

        $row->forceFill(['approved_at' => now()])->save();
    }

    public function rejectVenueImage(string $id): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $row = CourtClientGalleryImage::query()->where('id', $id)->first();
        abort_unless($row !== null, 404);

        $clientId = $row->court_client_id;
        Storage::disk('public')->delete($row->path);
        $row->delete();
        $this->normalizeVenueGalleryOrder($clientId);
    }

    public function approveCourtImage(string $id): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $row = CourtGalleryImage::query()->where('id', $id)->first();
        abort_unless($row !== null, 404);

        $row->forceFill(['approved_at' => now()])->save();
    }

    public function rejectCourtImage(string $id): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $row = CourtGalleryImage::query()->where('id', $id)->first();
        abort_unless($row !== null, 404);

        $courtId = $row->court_id;
        Storage::disk('public')->delete($row->path);
        $row->delete();
        $this->normalizeCourtGalleryOrder($courtId);
    }

    protected function normalizeVenueGalleryOrder(string $courtClientId): void
    {
        $rows = CourtClientGalleryImage::query()
            ->where('court_client_id', $courtClientId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        foreach ($rows as $i => $row) {
            if ((int) $row->sort_order !== $i) {
                $row->sort_order = $i;
                $row->save();
            }
        }
    }

    protected function normalizeCourtGalleryOrder(string $courtId): void
    {
        $rows = CourtGalleryImage::query()
            ->where('court_id', $courtId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        foreach ($rows as $i => $row) {
            if ((int) $row->sort_order !== $i) {
                $row->sort_order = $i;
                $row->save();
            }
        }
    }

    public function render(): View
    {
        $venuePending = CourtClientGalleryImage::query()
            ->pendingApproval()
            ->with('courtClient')
            ->orderByDesc('created_at')
            ->get();

        $courtPending = CourtGalleryImage::query()
            ->pendingApproval()
            ->with(['court.courtClient'])
            ->orderByDesc('created_at')
            ->get();

        return view('livewire.admin.gallery-image-approvals', [
            'venuePending' => $venuePending,
            'courtPending' => $courtPending,
        ]);
    }
}
