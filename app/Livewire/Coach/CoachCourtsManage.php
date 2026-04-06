<?php

namespace App\Livewire\Coach;

use App\Models\CoachCourt;
use App\Models\CoachHourAvailability;
use App\Models\Court;
use App\Models\CourtClient;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::member')]
#[Title('Venues you coach')]
class CoachCourtsManage extends Component
{
    public function toggleVenue(string $courtClientId): void
    {
        $courtIds = Court::query()
            ->where('court_client_id', $courtClientId)
            ->where('is_available', true)
            ->pluck('id')
            ->all();

        if ($courtIds === []) {
            abort(404);
        }

        $uid = auth()->id();

        $linkedCount = CoachCourt::query()
            ->where('coach_user_id', $uid)
            ->whereIn('court_id', $courtIds)
            ->count();

        $allLinked = $linkedCount === count($courtIds);

        if ($allLinked) {
            CoachHourAvailability::query()
                ->where('coach_user_id', $uid)
                ->whereIn('court_id', $courtIds)
                ->delete();
            CoachCourt::query()
                ->where('coach_user_id', $uid)
                ->whereIn('court_id', $courtIds)
                ->delete();

            return;
        }

        foreach ($courtIds as $courtId) {
            CoachCourt::query()->firstOrCreate([
                'coach_user_id' => $uid,
                'court_id' => $courtId,
            ]);
        }
    }

    public function venueIsFullyCoached(string $courtClientId): bool
    {
        $courtIds = Court::query()
            ->where('court_client_id', $courtClientId)
            ->where('is_available', true)
            ->pluck('id');

        if ($courtIds->isEmpty()) {
            return false;
        }

        $uid = auth()->id();
        $linked = CoachCourt::query()
            ->where('coach_user_id', $uid)
            ->whereIn('court_id', $courtIds)
            ->count();

        return $linked === $courtIds->count();
    }

    public function render(): View
    {
        $venues = CourtClient::query()
            ->where('is_active', true)
            ->whereHas('courts', fn ($q) => $q->where('is_available', true))
            ->withCount([
                'courts as bookable_courts_count' => fn ($q) => $q->where('is_available', true),
            ])
            ->orderBy('name')
            ->get(['id', 'name', 'city']);

        return view('livewire.coach.coach-courts-manage', [
            'venues' => $venues,
        ]);
    }
}
