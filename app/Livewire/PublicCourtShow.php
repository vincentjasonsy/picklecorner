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

    public function mount(Court $court): void
    {
        $court->load(['courtClient', 'approvedGalleryImages']);
        if (! $court->is_available || ! $court->courtClient || ! $court->courtClient->is_active) {
            abort(404);
        }

        $ids = session('book_now_recent_courts', []);
        $ids = array_values(array_unique(array_merge([(string) $court->id], $ids)));
        $ids = array_slice($ids, 0, 20);
        session(['book_now_recent_courts' => $ids]);

        $this->court = $court;
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
