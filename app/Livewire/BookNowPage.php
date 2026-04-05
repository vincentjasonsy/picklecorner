<?php

namespace App\Livewire;

use App\Models\Court;
use App\Models\CourtClient;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;

class BookNowPage extends Component
{
    /** all | indoor | outdoor */
    public string $environment = 'all';

    /** null = all cities */
    public ?string $city = null;

    public function mount(): void
    {
        if (! session()->has('book_now_nearby_city')) {
            $default = CourtClient::query()
                ->where('is_active', true)
                ->whereNotNull('city')
                ->orderBy('city')
                ->value('city');
            if ($default !== null) {
                session(['book_now_nearby_city' => $default]);
            }
        }
    }

    public function setEnvironment(string $value): void
    {
        if (! in_array($value, ['all', 'indoor', 'outdoor'], true)) {
            return;
        }
        $this->environment = $value;
    }

    public function setCity(?string $value): void
    {
        $this->city = $value === '' ? null : $value;
        if ($value !== null && $value !== '') {
            session(['book_now_nearby_city' => $value]);
        }
    }

    /** @return Collection<int, Court> */
    protected function baseCourtsQuery()
    {
        return Court::query()
            ->where('is_available', true)
            ->whereHas('courtClient', fn ($q) => $q->where('is_active', true))
            ->with('courtClient')
            ->orderBy('court_client_id')
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    /** @return Collection<int, Court> */
    public function filteredCourts(): Collection
    {
        $q = $this->baseCourtsQuery();
        if ($this->environment === 'indoor') {
            $q->where('environment', Court::ENV_INDOOR);
        } elseif ($this->environment === 'outdoor') {
            $q->where('environment', Court::ENV_OUTDOOR);
        }
        if ($this->city !== null && $this->city !== '') {
            $q->whereHas('courtClient', fn ($q) => $q->where('city', $this->city));
        }

        return $q->get();
    }

    /** @return Collection<int, string> */
    public function cityPills(): Collection
    {
        return CourtClient::query()
            ->where('is_active', true)
            ->whereNotNull('city')
            ->distinct()
            ->orderBy('city')
            ->pluck('city');
    }

    public function nearbyCity(): ?string
    {
        return session('book_now_nearby_city');
    }

    /** @return Collection<int, Court> */
    public function nearbyCourts(): Collection
    {
        $city = $this->nearbyCity();
        if ($city === null || $city === '') {
            return collect();
        }

        return $this->baseCourtsQuery()
            ->whereHas('courtClient', fn ($q) => $q->where('city', $city))
            ->limit(8)
            ->get();
    }

    /** @return Collection<int, Court> */
    public function topRatedCourts(): Collection
    {
        return Court::query()
            ->select('courts.*')
            ->join('court_clients', 'courts.court_client_id', '=', 'court_clients.id')
            ->where('court_clients.is_active', true)
            ->where('courts.is_available', true)
            ->orderByRaw('court_clients.public_rating_average IS NULL')
            ->orderByDesc('court_clients.public_rating_average')
            ->orderByDesc('court_clients.public_rating_count')
            ->with('courtClient')
            ->limit(8)
            ->get();
    }

    /** @return Collection<int, Court> */
    public function recentlyViewedCourts(): Collection
    {
        $ids = session('book_now_recent_courts', []);
        if ($ids === []) {
            return collect();
        }

        $courts = Court::query()
            ->whereIn('id', $ids)
            ->where('is_available', true)
            ->whereHas('courtClient', fn ($q) => $q->where('is_active', true))
            ->with('courtClient')
            ->get()
            ->keyBy('id');

        return collect($ids)
            ->map(fn (string $id) => $courts->get($id))
            ->filter()
            ->values();
    }

    public function render(): View
    {
        $view = view('livewire.book-now-page');

        if (request()->routeIs('account.book')) {
            return $view->layout('layouts::member')->title('Book now');
        }

        return $view->layout('layouts::guest')->title('Book now');
    }
}
