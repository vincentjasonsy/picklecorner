<?php

namespace App\Livewire\Member;

use App\Models\CourtClient;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::member')]
#[Title('Book now')]
class MemberBookNow extends Component
{
    public function render(): View
    {
        $venues = CourtClient::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'city', 'hourly_rate_cents', 'currency']);

        return view('livewire.member.member-book-now', [
            'venues' => $venues,
        ]);
    }
}
