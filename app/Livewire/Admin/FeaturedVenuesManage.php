<?php

namespace App\Livewire\Admin;

use App\Models\CityFeaturedCourtClient;
use App\Models\CourtClient;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::admin')]
#[Title('Featured venues')]
class FeaturedVenuesManage extends Component
{
    public string $selectedCity = '';

    /** @var list<string> */
    public array $orderedIds = [];

    public function mount(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $cities = $this->cityOptions();
        if ($cities->isNotEmpty()) {
            $this->selectedCity = (string) $cities->first();
            $this->loadOrderedIdsForSelectedCity();
        }
    }

    public function updatedSelectedCity(): void
    {
        $this->loadOrderedIdsForSelectedCity();
        $this->resetErrorBag();
    }

    protected function loadOrderedIdsForSelectedCity(): void
    {
        if ($this->selectedCity === '') {
            $this->orderedIds = [];

            return;
        }

        $this->orderedIds = CityFeaturedCourtClient::query()
            ->where('city', $this->selectedCity)
            ->orderBy('sort_order')
            ->pluck('court_client_id')
            ->map(fn ($id) => $this->normalizeCourtClientId((string) $id))
            ->all();
    }

    /**
     * @return Collection<int, string>
     */
    public function cityOptions()
    {
        return CourtClient::query()
            ->where('is_active', true)
            ->whereNotNull('city')
            ->distinct()
            ->orderBy('city')
            ->pluck('city');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, CourtClient>
     */
    public function venuesInSelectedCity(): \Illuminate\Database\Eloquent\Collection
    {
        if ($this->selectedCity === '') {
            return new \Illuminate\Database\Eloquent\Collection;
        }

        return CourtClient::query()
            ->where('is_active', true)
            ->where('city', $this->selectedCity)
            ->orderBy('name')
            ->get();
    }

    public function toggleVenue(string $courtClientId): void
    {
        $this->resetErrorBag();
        $courtClientId = $this->normalizeCourtClientId($courtClientId);
        if ($courtClientId === '') {
            return;
        }

        $key = array_search($courtClientId, $this->orderedIds, true);
        if ($key !== false) {
            unset($this->orderedIds[$key]);
            $this->orderedIds = array_values($this->orderedIds);

            return;
        }

        if (count($this->orderedIds) >= 10) {
            $this->addError('orderedIds', 'You can feature at most 10 venues per city.');

            return;
        }

        $this->orderedIds[] = $courtClientId;
    }

    public function moveUp(int $index): void
    {
        if ($index <= 0 || $index >= count($this->orderedIds)) {
            return;
        }
        $tmp = $this->orderedIds[$index - 1];
        $this->orderedIds[$index - 1] = $this->orderedIds[$index];
        $this->orderedIds[$index] = $tmp;
    }

    public function moveDown(int $index): void
    {
        if ($index < 0 || $index >= count($this->orderedIds) - 1) {
            return;
        }
        $tmp = $this->orderedIds[$index + 1];
        $this->orderedIds[$index + 1] = $this->orderedIds[$index];
        $this->orderedIds[$index] = $tmp;
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        if ($this->selectedCity === '') {
            $this->addError('selectedCity', 'Pick a city first.');

            return;
        }

        $count = count($this->orderedIds);
        if ($count !== count(array_unique($this->orderedIds))) {
            $this->addError('orderedIds', 'Each venue can only appear once.');

            return;
        }

        if ($count !== 0 && ($count < 5 || $count > 10)) {
            $this->addError(
                'orderedIds',
                'Feature between 5 and 10 venues for this city, or clear all checkboxes to remove the featured strip.',
            );

            return;
        }

        $validIds = CourtClient::query()
            ->where('city', $this->selectedCity)
            ->where('is_active', true)
            ->whereIn('id', $this->orderedIds)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        if ($count !== count($validIds)) {
            $this->addError('orderedIds', 'One or more venues are invalid for this city. Refresh and try again.');

            return;
        }

        DB::transaction(function () use ($count): void {
            CityFeaturedCourtClient::query()->where('city', $this->selectedCity)->delete();

            if ($count === 0) {
                return;
            }

            foreach ($this->orderedIds as $i => $courtClientId) {
                CityFeaturedCourtClient::query()->create([
                    'city' => $this->selectedCity,
                    'court_client_id' => $courtClientId,
                    'sort_order' => $i,
                ]);
            }
        });

        session()->flash('status', $count === 0
            ? 'Featured venues cleared for this city.'
            : 'Featured venues saved for '.$this->selectedCity.'.');
    }

    public function isSelected(string $courtClientId): bool
    {
        $id = $this->normalizeCourtClientId($courtClientId);

        return in_array($id, $this->orderedIds, true);
    }

    private function normalizeCourtClientId(string $courtClientId): string
    {
        return strtolower(trim($courtClientId));
    }

    public function render(): View
    {
        return view('livewire.admin.featured-venues-manage', [
            'venues' => $this->venuesInSelectedCity(),
        ]);
    }
}
