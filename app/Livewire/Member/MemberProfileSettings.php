<?php

namespace App\Livewire\Member;

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

    public string $current_password = '';

    public string $new_password = '';

    public string $new_password_confirmation = '';

    public function mount(): void
    {
        $user = auth()->user();
        $this->name = $user->name;
        $this->email = $user->email;
    }

    public function saveProfile(): void
    {
        $user = auth()->user();

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
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
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
        return view('livewire.member.member-profile-settings');
    }
}
