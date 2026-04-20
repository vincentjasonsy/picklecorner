<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\WithDashboardTable;
use App\Models\CourtClient;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts::admin')]
#[Title('Manual booking')]
class ManualBookingHub extends Component
{
    use WithDashboardTable;

    #[Url]
    public string $q = '';

    #[Url]
    public string $statusFilter = '';

    /** @return list<string> */
    protected function sortableColumns(): array
    {
        return ['name', 'city', 'hourly_rate_cents', 'venue_status'];
    }

    public function updatedQ(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function venuesPaginator()
    {
        $query = CourtClient::query()->with('admin');

        if ($this->q !== '') {
            $s = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $this->q).'%';
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', $s)->orWhere('city', 'like', $s);
            });
        }

        if ($this->statusFilter === CourtClient::VENUE_STATUS_ACTIVE) {
            $query->where('venue_status', CourtClient::VENUE_STATUS_ACTIVE);
        } elseif ($this->statusFilter === CourtClient::VENUE_STATUS_UPCOMING) {
            $query->where('venue_status', CourtClient::VENUE_STATUS_UPCOMING);
        } elseif ($this->statusFilter === CourtClient::VENUE_STATUS_INACTIVE) {
            $query->where('venue_status', CourtClient::VENUE_STATUS_INACTIVE);
        }

        if ($this->sortField !== '' && in_array($this->sortField, $this->sortableColumns(), true)) {
            $query->orderBy($this->sortField, $this->sortDirection === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('name');
        }

        return $query->paginate($this->perPage);
    }

    public function render(): View
    {
        return view('livewire.admin.manual-booking-hub');
    }
}
