<?php

namespace App\Livewire\Member;

use App\Support\MemberBookingNudge as MemberBookingNudgeHelper;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class MemberBookingNudge extends Component
{
    public bool $open = false;

    /** @var array{headline: string, body: string, days: ?int, never_booked: bool}|null */
    public ?array $copy = null;

    public function mount(): void
    {
        $user = Auth::user();
        if ($user === null) {
            return;
        }

        if (Cache::has(MemberBookingNudgeHelper::cacheKey($user))) {
            return;
        }

        if (! MemberBookingNudgeHelper::shouldPrompt($user)) {
            return;
        }

        $this->copy = MemberBookingNudgeHelper::copy($user);
        $this->open = true;
    }

    public function dismiss(): void
    {
        $this->rememberDismissal();
        $this->open = false;
    }

    public function goBook(): mixed
    {
        $this->rememberDismissal();
        $this->open = false;

        return $this->redirect(route('account.book'), navigate: true);
    }

    protected function rememberDismissal(): void
    {
        $user = Auth::user();
        if ($user === null) {
            return;
        }

        $hours = max(1, (int) config('booking.member_booking_nudge_dismiss_hours', 72));
        Cache::put(MemberBookingNudgeHelper::cacheKey($user), true, now()->addHours($hours));
    }

    public function render(): View
    {
        return view('livewire.member.member-booking-nudge');
    }
}
