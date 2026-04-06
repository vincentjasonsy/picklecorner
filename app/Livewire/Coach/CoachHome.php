<?php

namespace App\Livewire\Coach;

use App\Models\Booking;
use App\Models\CoachProfile;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::member')]
#[Title('Coaching')]
class CoachHome extends Component
{
    public function mount(): void
    {
        CoachProfile::query()->firstOrCreate(
            ['user_id' => auth()->id()],
            ['hourly_rate_cents' => 0, 'currency' => 'PHP', 'bio' => null],
        );
    }

    #[Computed]
    public function upcomingCoachedSessions()
    {
        return auth()->user()->coachedBookings()
            ->with(['courtClient:id,name,city', 'court:id,name', 'user:id,name'])
            ->where('starts_at', '>=', now()->subHour())
            ->whereNotIn('status', [Booking::STATUS_CANCELLED, Booking::STATUS_DENIED])
            ->orderBy('starts_at')
            ->limit(8)
            ->get();
    }

    public function render(): View
    {
        return view('livewire.coach.coach-home');
    }
}
