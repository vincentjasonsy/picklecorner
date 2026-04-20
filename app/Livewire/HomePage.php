<?php

namespace App\Livewire;

use App\Support\LandingStats;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::guest')]
#[Title('Home')]
class HomePage extends Component
{
    public function render(): View
    {
        $avgMin = LandingStats::averageBookingSessionMinutes();

        return view('livewire.home-page', [
            'listedCourtsCount' => LandingStats::listedCourtsCount(),
            'happyPlayersCount' => LandingStats::happyPlayersCount(),
            'avgSessionLabel' => LandingStats::formatAverageSession($avgMin),
        ]);
    }
}
