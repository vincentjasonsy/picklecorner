<?php

namespace App\Livewire;

use App\Models\CityFeaturedCourtClient;
use App\Models\Court;
use App\Models\CourtClient;
use App\Support\BrowseCourtOpenSlots;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Component;

class BookNowPage extends Component
{
    /** all | indoor | outdoor */
    public string $environment = 'all';

    /** null = all cities */
    public ?string $city = null;

    /** Search court name, venue name, or city */
    public string $search = '';

    /** all = any matching court; open_soon = at least one bookable hour in the next 14 days */
    public string $availability = 'all';

    public function mount(): void
    {
        if (! session()->has('book_now_nearby_city')) {
            $default = $this->resolvedDefaultNearbyCity();
            if ($default !== null) {
                session(['book_now_nearby_city' => $default]);
            }
        }
    }

    protected function resolvedDefaultNearbyCity(): ?string
    {
        $fromUser = auth()->user()?->preferredCourtBookingCity();
        if (filled($fromUser)) {
            return $fromUser;
        }

        return CourtClient::query()
            ->where('is_active', true)
            ->whereNotNull('city')
            ->orderBy('city')
            ->value('city');
    }

    /** City used to rank courts when browsing (profile or inferred bookings). */
    public function userPreferredCity(): ?string
    {
        return auth()->user()?->preferredCourtBookingCity();
    }

    /**
     * City used for the featured-venues strip: explicit filter, then profile/inferred preference, then session default.
     */
    public function effectiveCityForFeatured(): ?string
    {
        if ($this->city !== null && $this->city !== '') {
            return $this->city;
        }
        if (filled($this->userPreferredCity())) {
            return $this->userPreferredCity();
        }

        return $this->nearbyCity();
    }

    /**
     * Curated venues for Book now (super-admin), when the viewer’s city matches a configured list.
     *
     * @return Collection<int, CourtClient>
     */
    public function featuredVenueClients(): Collection
    {
        $city = $this->effectiveCityForFeatured();
        if ($city === null || $city === '') {
            return collect();
        }

        return CityFeaturedCourtClient::activeVenuesForCityOrdered($city);
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

    public function setAvailability(string $value): void
    {
        if (! in_array($value, ['all', 'open_soon'], true)) {
            return;
        }
        $this->availability = $value;
    }

    /** @return Collection<int, Court> */
    protected function baseCourtsQuery()
    {
        return Court::query()
            ->where('is_available', true)
            ->whereHas('courtClient', fn ($q) => $q->where('is_active', true))
            ->with(['courtClient', 'approvedGalleryImages'])
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

        $this->applySearchFilter($q);

        $courts = $this->sortCourtsForBrowse($q->get());

        if ($this->availability === 'open_soon') {
            $openIds = BrowseCourtOpenSlots::courtIdsWithAnyOpenSlot($courts, 14);
            $courts = $courts->filter(fn (Court $c) => isset($openIds[(string) $c->id]))->values();
        }

        return $courts;
    }

    protected function applySearchFilter(Builder $q): void
    {
        $term = trim($this->search);
        if ($term === '') {
            return;
        }

        $escaped = addcslashes($term, '%_\\');
        $pattern = '%'.$escaped.'%';

        $q->where(function (Builder $query) use ($pattern): void {
            $query->where('courts.name', 'like', $pattern)
                ->orWhereHas('courtClient', function (Builder $venue) use ($pattern): void {
                    $venue->where(function (Builder $v) use ($pattern): void {
                        $v->where('name', 'like', $pattern)->orWhere('city', 'like', $pattern);
                    });
                });
        });
    }

    /**
     * Preferred area first (when set), then highest guest rating, then review count, then name.
     *
     * @param  Collection<int, Court>  $courts
     * @return Collection<int, Court>
     */
    protected function sortCourtsForBrowse(Collection $courts): Collection
    {
        $preferred = $this->userPreferredCity();

        return $courts
            ->sort(fn (Court $a, Court $b): int => $this->compareCourtsForBrowse($a, $b, $preferred))
            ->values();
    }

    protected function compareCourtsForBrowse(Court $a, Court $b, ?string $preferred): int
    {
        $va = $a->courtClient;
        $vb = $b->courtClient;

        if ($va !== null && $vb !== null) {
            return $this->compareVenuesForBrowse($va, $vb, $preferred);
        }

        if ($va === null && $vb === null) {
            return strnatcasecmp((string) $a->name, (string) $b->name);
        }

        return $va === null ? 1 : -1;
    }

    /**
     * @return int<-1, 1>
     */
    protected function compareVenuesForBrowse(CourtClient $a, CourtClient $b, ?string $preferred): int
    {
        if ($preferred !== null && $preferred !== '') {
            $aLocal = $a->city === $preferred ? 0 : 1;
            $bLocal = $b->city === $preferred ? 0 : 1;
            if ($aLocal !== $bLocal) {
                return $aLocal <=> $bLocal;
            }
        }

        $cmp = $this->comparePublicRatingTuples(
            $a->public_rating_average,
            (int) ($a->public_rating_count ?? 0),
            $b->public_rating_average,
            (int) ($b->public_rating_count ?? 0),
        );
        if ($cmp !== 0) {
            return $cmp;
        }

        return strnatcasecmp((string) $a->name, (string) $b->name);
    }

    /**
     * Higher average first; null averages last; then higher review count.
     *
     * @return int<-1, 1>
     */
    protected function comparePublicRatingTuples(mixed $aAvg, int $aCount, mixed $bAvg, int $bCount): int
    {
        $aNull = $aAvg === null || $aAvg === '';
        $bNull = $bAvg === null || $bAvg === '';
        if ($aNull && $bNull) {
            return $bCount <=> $aCount;
        }
        if ($aNull) {
            return 1;
        }
        if ($bNull) {
            return -1;
        }
        $af = (float) $aAvg;
        $bf = (float) $bAvg;
        if ($af !== $bf) {
            return $bf <=> $af;
        }

        return $bCount <=> $aCount;
    }

    /**
     * Active venues that have at least one court matching current browse filters, with counts.
     *
     * @return Collection<int, array{venue: CourtClient, court_count: int}>
     */
    public function browseVenueRows(): Collection
    {
        $courts = $this->filteredCourts();
        $counts = $courts->countBy('court_client_id');

        if ($counts->isEmpty()) {
            return collect();
        }

        $preferred = $this->userPreferredCity();

        return CourtClient::query()
            ->whereIn('id', $counts->keys())
            ->with('approvedGalleryImages')
            ->get()
            ->sort(fn (CourtClient $a, CourtClient $b): int => $this->compareVenuesForBrowse($a, $b, $preferred))
            ->values()
            ->map(fn (CourtClient $venue): array => [
                'venue' => $venue,
                'court_count' => (int) ($counts->get($venue->id) ?? 0),
            ]);
    }

    /** @return Collection<int, string> */
    public function cityPills(): Collection
    {
        $cities = CourtClient::query()
            ->where('is_active', true)
            ->whereNotNull('city')
            ->distinct()
            ->orderBy('city')
            ->pluck('city');

        $preferred = $this->userPreferredCity();
        if ($preferred === null || $preferred === '' || ! $cities->contains($preferred)) {
            return $cities;
        }

        $rest = $cities->filter(fn (string $c) => $c !== $preferred)->values();

        return collect([$preferred])->merge($rest)->values();
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

        $courts = $this->baseCourtsQuery()
            ->whereHas('courtClient', fn ($q) => $q->where('city', $city))
            ->get();

        return $this->sortCourtsForBrowse($courts)->take(8);
    }

    /** @return Collection<int, Court> */
    public function topRatedCourts(): Collection
    {
        $preferred = $this->userPreferredCity();

        $q = Court::query()
            ->select('courts.*')
            ->join('court_clients', 'courts.court_client_id', '=', 'court_clients.id')
            ->where('court_clients.is_active', true)
            ->where('courts.is_available', true);

        if (filled($preferred)) {
            $q->orderByRaw('CASE WHEN court_clients.city = ? THEN 0 ELSE 1 END', [$preferred]);
        }

        return $q
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

    public function venueBookUrl(CourtClient $courtClient): string
    {
        return request()->routeIs('account.book')
            ? route('account.book.venue', $courtClient)
            : route('book-now.venue.book', $courtClient);
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
