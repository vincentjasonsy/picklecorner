<?php

namespace App\Livewire\Venue;

use App\Models\CourtClient;
use App\Models\CourtClientGalleryImage;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class VenueClientGallery extends Component
{
    use WithFileUploads;

    public const MAX_IMAGES = 6;

    public string $courtClientId = '';

    /** @var array<int, TemporaryUploadedFile> */
    public array $uploads = [];

    public function mount(string $courtClientId): void
    {
        $this->courtClientId = $courtClientId;
        $this->authorizeAccess();
    }

    protected function authorizeAccess(): void
    {
        $client = CourtClient::query()->find($this->courtClientId);
        abort_unless($client !== null, 404);
        $user = auth()->user();
        abort_unless(
            $user !== null
            && ($user->isSuperAdmin() || $user->administeredCourtClient?->id === $client->id),
            403,
        );
    }

    protected function client(): CourtClient
    {
        return CourtClient::query()->findOrFail($this->courtClientId);
    }

    public function saveUploads(): void
    {
        $this->authorizeAccess();

        $client = $this->client();
        $existing = $client->galleryImages()->count();
        if ($existing >= self::MAX_IMAGES) {
            $this->addError('uploads', 'You can have at most '.self::MAX_IMAGES.' photos. Remove one to add more.');

            return;
        }

        $maxNew = self::MAX_IMAGES - $existing;

        $this->validate([
            'uploads' => ['required', 'array', 'min:1', 'max:'.$maxNew],
            'uploads.*' => ['image', 'max:5120'],
        ]);
        $next = (int) ($client->galleryImages()->max('sort_order') ?? -1);

        foreach ($this->uploads as $file) {
            $next++;
            $ext = $file->guessExtension() ?: 'jpg';
            $path = $file->storeAs(
                'court-clients/'.$client->id.'/gallery',
                Str::uuid()->toString().'.'.$ext,
                'public',
            );
            CourtClientGalleryImage::query()->create([
                'court_client_id' => $client->id,
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

        $client = $this->client();
        $row = CourtClientGalleryImage::query()
            ->where('court_client_id', $client->id)
            ->where('id', $imageId)
            ->first();
        abort_unless($row !== null, 404);

        Storage::disk('public')->delete($row->path);
        $row->delete();

        $this->normalizeSortOrder($client->id);
    }

    public function approveImage(string $imageId): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $client = $this->client();
        $row = CourtClientGalleryImage::query()
            ->where('court_client_id', $client->id)
            ->where('id', $imageId)
            ->first();
        abort_unless($row !== null, 404);

        $row->forceFill(['approved_at' => now()])->save();
    }

    public function rejectImage(string $imageId): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $client = $this->client();
        $row = CourtClientGalleryImage::query()
            ->where('court_client_id', $client->id)
            ->where('id', $imageId)
            ->first();
        abort_unless($row !== null, 404);

        Storage::disk('public')->delete($row->path);
        $row->delete();
        $this->normalizeSortOrder($client->id);
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

        $client = $this->client();
        $images = CourtClientGalleryImage::query()
            ->where('court_client_id', $client->id)
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

        $this->normalizeSortOrder($client->id);
    }

    protected function normalizeSortOrder(string $courtClientId): void
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

    public function render(): View
    {
        $this->authorizeAccess();

        $images = CourtClientGalleryImage::query()
            ->where('court_client_id', $this->courtClientId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('livewire.venue.venue-client-gallery', [
            'images' => $images,
            'legacyCover' => $this->client()->cover_image_path,
            'galleryFull' => $images->count() >= self::MAX_IMAGES,
            'maxImages' => self::MAX_IMAGES,
        ]);
    }
}
