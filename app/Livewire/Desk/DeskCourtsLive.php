<?php

namespace App\Livewire\Desk;

use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtClient;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::desk-portal')]
#[Title('Courts live')]
class DeskCourtsLive extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()?->deskCourtClient, 404);
    }

    #[Computed]
    public function courtClient(): ?CourtClient
    {
        return auth()->user()->deskCourtClient;
    }

    /**
     * @return Collection<int, array{court: Court, current: ?Booking, next: ?Booking}>
     */
    #[Computed]
    public function courtsLiveRows(): Collection
    {
        $client = $this->courtClient;
        if (! $client) {
            return collect();
        }

        $courts = Court::orderedForGridColumns($client->courts()->with('approvedGalleryImages')->get());

        $now = now();
        $statuses = Booking::statusesBlockingCourtAvailability();

        $rows = collect();
        foreach ($courts as $court) {
            $current = Booking::query()
                ->where('court_id', $court->id)
                ->where('court_client_id', $client->id)
                ->whereIn('status', $statuses)
                ->where('starts_at', '<=', $now)
                ->where('ends_at', '>', $now)
                ->orderBy('starts_at')
                ->with(['user'])
                ->first();

            $anchor = $current !== null ? $current->ends_at : $now;

            $nextQuery = Booking::query()
                ->where('court_id', $court->id)
                ->where('court_client_id', $client->id)
                ->whereIn('status', $statuses)
                ->where('starts_at', '>=', $anchor)
                ->with(['user'])
                ->orderBy('starts_at');

            if ($current !== null) {
                $nextQuery->where('id', '!=', $current->id);
            }

            $next = $nextQuery->first();

            $rows->push([
                'court' => $court,
                'current' => $current,
                'next' => $next,
            ]);
        }

        return $rows;
    }

    public function render(): View
    {
        return view('livewire.desk.desk-courts-live');
    }
}
