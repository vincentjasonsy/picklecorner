<?php

namespace App\Livewire;

use App\Models\Court;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::guest')]
class PublicCourtShow extends Component
{
    public Court $court;

    public bool $courtIsFavorite = false;

    /** Venue is upcoming — page is view-only; booking opens when the venue goes active. */
    public bool $venueComingSoon = false;

    public function mount(Court $court): void
    {
        $court->load(['courtClient', 'approvedGalleryImages']);
        if (! $court->is_available || ! $court->courtClient || ! $court->courtClient->isListedOnBookNow()) {
            abort(404);
        }

        $this->venueComingSoon = $court->courtClient->isOpeningSoonVenue();

        $ids = session('book_now_recent_courts', []);
        $ids = array_values(array_unique(array_merge([(string) $court->id], $ids)));
        $ids = array_slice($ids, 0, 20);
        session(['book_now_recent_courts' => $ids]);

        $this->court = $court;
        $this->syncFavoriteState();
    }

    public function toggleFavorite(): void
    {
        $user = auth()->user();
        if ($user === null) {
            session(['url.intended' => route('book-now.court', $this->court)]);
            $this->redirect(route('login'), navigate: true);

            return;
        }

        if ($this->courtIsFavorite) {
            $user->favoriteCourts()->detach($this->court->id);
            $this->courtIsFavorite = false;

            return;
        }

        $user->favoriteCourts()->attach($this->court->id);
        $this->courtIsFavorite = true;
    }

    protected function syncFavoriteState(): void
    {
        $user = auth()->user();
        $this->courtIsFavorite = $user !== null
            && $user->favoriteCourts()->whereKey($this->court->id)->exists();
    }

    public function render(): View
    {
        $t = $this->court->name;
        if ($this->court->courtClient) {
            $t .= ' · '.$this->court->courtClient->name;
        }

        return view('livewire.public-court-show')->title($t);
    }
}
