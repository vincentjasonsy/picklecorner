<?php

namespace App\Livewire\Member;

use App\GameQ\ProfileOpponentAggregator;
use App\Models\CourtClient;
use App\Models\OpenPlaySession;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::member')]
#[Title('Profile')]
class MemberProfileSettings extends Component
{
    public string $name = '';

    public string $email = '';

    /** Empty string = no preference; otherwise must match an active venue city. */
    public string $home_city = '';

    public string $current_password = '';

    public string $new_password = '';

    public string $new_password_confirmation = '';

    public function mount(): void
    {
        $user = auth()->user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->home_city = $user->home_city ?? '';
    }

    public function saveProfile(): void
    {
        $user = auth()->user();

        $cityOptions = CourtClient::query()
            ->where('is_active', true)
            ->whereNotNull('city')
            ->distinct()
            ->orderBy('city')
            ->pluck('city')
            ->all();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],
            'home_city' => ['nullable', 'string', 'max:128', Rule::in(array_merge([''], $cityOptions))],
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'home_city' => $validated['home_city'] !== '' ? $validated['home_city'] : null,
        ]);

        session()->flash('status', 'Profile updated — you’re all set!');
    }

    public function updatePassword(): void
    {
        $this->validate([
            'current_password' => ['required', 'current_password'],
            'new_password' => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        auth()->user()->update([
            'password' => $this->new_password,
        ]);

        $this->reset('current_password', 'new_password', 'new_password_confirmation');

        session()->flash('status', 'Password updated. Stay secure out there!');
    }

    public function render(): View
    {
        $user = auth()->user();
        $gameqSessions = OpenPlaySession::query()
            ->where('user_id', $user->id)
            ->orderByDesc('updated_at')
            ->get();
        $gameqProfile = ProfileOpponentAggregator::forUser($user, $gameqSessions);

        $homeCityOptions = CourtClient::query()
            ->where('is_active', true)
            ->whereNotNull('city')
            ->distinct()
            ->orderBy('city')
            ->pluck('city');

        return view('livewire.member.member-profile-settings', [
            'gameqSessionsTotal' => $gameqSessions->count(),
            'gameqProfile' => $gameqProfile,
            'homeCityOptions' => $homeCityOptions,
        ]);
    }
}
