<?php

namespace App\Livewire\Coach;

use App\Models\CoachProfile;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::member')]
#[Title('Coach profile')]
class CoachProfileEdit extends Component
{
    public int $hourlyRatePesos = 0;

    public string $currency = 'PHP';

    public string $bio = '';

    public function mount(): void
    {
        $p = CoachProfile::query()->firstOrCreate(
            ['user_id' => auth()->id()],
            ['hourly_rate_cents' => 0, 'currency' => 'PHP', 'bio' => null],
        );

        $this->hourlyRatePesos = (int) floor($p->hourly_rate_cents / 100);
        $this->currency = $p->currency ?: 'PHP';
        $this->bio = (string) ($p->bio ?? '');
    }

    public function save(): void
    {
        $validated = $this->validate([
            'hourlyRatePesos' => ['required', 'integer', 'min:0', 'max:500000'],
            'currency' => ['required', 'string', 'size:3'],
            'bio' => ['nullable', 'string', 'max:5000'],
        ]);

        $cents = $validated['hourlyRatePesos'] * 100;

        CoachProfile::query()->updateOrCreate(
            ['user_id' => auth()->id()],
            [
                'hourly_rate_cents' => $cents,
                'currency' => strtoupper($validated['currency']),
                'bio' => $validated['bio'] !== '' ? $validated['bio'] : null,
            ],
        );

        session()->flash('status', 'Coach profile saved.');
    }

    public function render(): View
    {
        return view('livewire.coach.coach-profile-edit');
    }
}
