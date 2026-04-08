<?php

namespace App\Livewire\Venue;

use App\Models\Court;
use App\Models\CourtGalleryImage;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class CourtGalleryEditor extends Component
{
    use WithFileUploads;

    public const MAX_IMAGES = 6;

    public string $courtId = '';

    public array $uploads = [];

    public function mount(string $courtId): void
    {
        $this->courtId = $courtId;
        $this->authorizeAccess();
    }

    protected function authorizeAccess(): void
    {
        $court = Court::query()->find($this->courtId);
        abort_unless($court !== null, 404);
        $user = auth()->user();
        abort_unless(
            $user !== null
            && ($user->isSuperAdmin() || $user->administeredCourtClient?->id === $court->court_client_id),
            403,
        );
    }

    protected function court(): Court
    {
        return Court::query()->findOrFail($this->courtId);
    }

    public function saveUploads(): void
    {
        $this->authorizeAccess();

        $court = $this->court();
        $existing = $court->galleryImages()->count();
        if ($existing >= self::MAX_IMAGES) {
            $this->addError('uploads', 'You can have at most '.self::MAX_IMAGES.' photos per court. Remove one to add more.');

            return;
        }

        $maxNew = self::MAX_IMAGES - $existing;

        $this->validate([
            'uploads' => ['required', 'array', 'min:1', 'max:'.$maxNew],
            'uploads.*' => ['image', 'max:5120'],
        ]);
        $next = (int) ($court->galleryImages()->max('sort_order') ?? -1);

        foreach ($this->uploads as $file) {
            $next++;
            $ext = $file->guessExtension() ?: 'jpg';
            $path = $file->storeAs(
                'courts/'.$court->id.'/gallery',
                Str::uuid()->toString().'.'.$ext,
                'public',
            );
            CourtGalleryImage::query()->create([
                'court_id' => $court->id,
                'path' => $path,
                'sort_order' => $next,
                'alt_text' => null,
                'approved_at' => auth()->user()?->isSuperAdmin() ? now() : null,
            ]);
        }

        $this->uploads = [];
    }

    public function removeImage(string $imageId): void
    {
        $this->authorizeAccess();

        $court = $this->court();
        $row = CourtGalleryImage::query()
            ->where('court_id', $court->id)
            ->where('id', $imageId)
            ->first();
        abort_unless($row !== null, 404);

        Storage::disk('public')->delete($row->path);
        $row->delete();

        $this->normalizeSortOrder($court->id);
    }

    public function approveImage(string $imageId): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $court = $this->court();
        $row = CourtGalleryImage::query()
            ->where('court_id', $court->id)
            ->where('id', $imageId)
            ->first();
        abort_unless($row !== null, 404);

        $row->forceFill(['approved_at' => now()])->save();
    }

    public function rejectImage(string $imageId): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $court = $this->court();
        $row = CourtGalleryImage::query()
            ->where('court_id', $court->id)
            ->where('id', $imageId)
            ->first();
        abort_unless($row !== null, 404);

        Storage::disk('public')->delete($row->path);
        $row->delete();
        $this->normalizeSortOrder($court->id);
    }

    public function moveUp(string $imageId): void
    {
        $this->swapAdjacent($imageId, -1);
    }

    public function moveDown(string $imageId): void
    {
        $this->swapAdjacent($imageId, 1);
    }

    protected function swapAdjacent(string $imageId, int $direction): void
    {
        $this->authorizeAccess();

        $court = $this->court();
        $images = CourtGalleryImage::query()
            ->where('court_id', $court->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->values();

        $idx = $images->search(fn ($r) => $r->id === $imageId);
        if ($idx === false) {
            return;
        }

        $j = $idx + $direction;
        if ($j < 0 || $j >= $images->count()) {
            return;
        }

        $a = $images[$idx];
        $b = $images[$j];
        $tmp = $a->sort_order;
        $a->sort_order = $b->sort_order;
        $b->sort_order = $tmp;
        $a->save();
        $b->save();

        $this->normalizeSortOrder($court->id);
    }

    protected function normalizeSortOrder(string $courtId): void
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
        $this->authorizeAccess();

        $court = $this->court()->loadMissing('courtClient');
        $images = CourtGalleryImage::query()
            ->where('court_id', $this->courtId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('livewire.venue.court-gallery-editor', [
            'court' => $court,
            'images' => $images,
            'galleryFull' => $images->count() >= self::MAX_IMAGES,
            'maxImages' => self::MAX_IMAGES,
        ]);
    }
}
