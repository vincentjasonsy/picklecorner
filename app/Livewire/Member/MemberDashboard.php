<?php

namespace App\Livewire\Member;

use App\Models\Booking;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::member')]
#[Title('Home court')]
class MemberDashboard extends Component
{
    #[Computed]
    public function firstName(): string
    {
        $name = trim((string) auth()->user()->name);

        return $name !== '' ? (string) Str::of($name)->before(' ') : 'Champ';
    }

    #[Computed]
    public function upcomingBookings()
    {
        return auth()->user()->bookings()
            ->with(['courtClient:id,name,city', 'court:id,name'])
            ->where('starts_at', '>=', now()->subHour())
            ->whereNotIn('status', [Booking::STATUS_CANCELLED, Booking::STATUS_DENIED])
            ->orderBy('starts_at')
            ->limit(5)
            ->get();
    }

    #[Computed]
    public function recentBookings()
    {
        return auth()->user()->bookings()
            ->with(['courtClient:id,name,city', 'court:id,name'])
            ->where('starts_at', '<', now())
            ->orderByDesc('starts_at')
            ->limit(4)
            ->get();
    }

    #[Computed]
    public function stats(): array
    {
        $uid = auth()->id();

        $upcoming = Booking::query()
            ->where('user_id', $uid)
            ->where('starts_at', '>=', now())
            ->whereNotIn('status', [Booking::STATUS_CANCELLED, Booking::STATUS_DENIED])
            ->count();

        $played = Booking::query()
            ->where('user_id', $uid)
            ->whereIn('status', [Booking::STATUS_COMPLETED, Booking::STATUS_CONFIRMED])
            ->where('starts_at', '<', now())
            ->count();

        $completed = Booking::query()
            ->where('user_id', $uid)
            ->where('status', Booking::STATUS_COMPLETED)
            ->count();

        return [
            'upcoming' => $upcoming,
            'played' => $played,
            'wins_on_the_board' => $completed,
        ];
    }

    public function render(): View
    {
        return view('livewire.member.member-dashboard');
    }
}
