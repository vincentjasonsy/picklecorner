<?php

namespace App\Livewire\Member;

use App\GameQ\ProfileOpponentAggregator;
use App\Models\Booking;
use App\Models\OpenPlayParticipant;
use App\Models\OpenPlaySession;
use App\Support\MemberBookingStats;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::member')]
#[Title('Dashboard')]
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
    public function upcomingOpenPlayJoins()
    {
        return OpenPlayParticipant::query()
            ->where('user_id', auth()->id())
            ->whereIn('status', [
                OpenPlayParticipant::STATUS_PENDING,
                OpenPlayParticipant::STATUS_ACCEPTED,
                OpenPlayParticipant::STATUS_WAITING_LIST,
            ])
            ->whereHas('booking', function ($q): void {
                $q->where('starts_at', '>=', now()->subHour())
                    ->whereNotIn('status', [Booking::STATUS_CANCELLED, Booking::STATUS_DENIED]);
            })
            ->with([
                'booking' => fn ($q) => $q->with(['courtClient:id,name', 'court:id,name']),
            ])
            ->get()
            ->sortBy(fn (OpenPlayParticipant $p) => $p->booking?->starts_at ?? now())
            ->values();
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
        $user = auth()->user();
        $gameqSessions = OpenPlaySession::query()
            ->where('user_id', $user->id)
            ->orderByDesc('updated_at')
            ->get();

        return view('livewire.member.member-dashboard', [
            'bookingStats' => MemberBookingStats::forUser($user),
            'lastBookingForRepeat' => Booking::query()
                ->where('user_id', $user->id)
                ->whereIn('status', [Booking::STATUS_CONFIRMED, Booking::STATUS_COMPLETED])
                ->whereNotNull('court_id')
                ->with(['courtClient:id,name'])
                ->latest('starts_at')
                ->first(),
            'gameqSessionsTotal' => $gameqSessions->count(),
            'gameqProfile' => ProfileOpponentAggregator::forUser($user, $gameqSessions),
        ]);
    }
}
