<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Models\UserType;
use App\Services\ActivityLogger;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

class RegisterPage extends Component
{
    public bool $demo = false;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $accept_privacy = false;

    public bool $subscribe_marketing_emails = false;

    public function mount(): void
    {
        $this->demo = request()->routeIs('register.demo');

        if ($this->demo && ! config('demo.registration_enabled')) {
            $this->redirect(route('register'), navigate: true);
        }
    }

    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'accept_privacy' => ['accepted'],
            'subscribe_marketing_emails' => ['boolean'],
        ], [
            'accept_privacy.accepted' => 'You must accept the Privacy Policy to create an account.',
        ]);

        $userTypeId = UserType::query()->where('slug', UserType::SLUG_USER)->value('id');

        $now = now();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'user_type_id' => $userTypeId,
            'privacy_consent_at' => $now,
            'marketing_emails_consent_at' => $validated['subscribe_marketing_emails'] ? $now : null,
        ]);

        if ($this->demo) {
            $user->forceFill([
                'demo_expires_at' => now()->addHours(config('demo.ttl_hours')),
            ])->save();
        }

        event(new Registered($user));

        Auth::login($user);

        Session::regenerate();

        $description = $this->demo
            ? 'Demo account registered (expires '.$user->demo_expires_at?->timezone(config('app.timezone'))->format('M j, Y g:i a').')'
            : 'New account registered';

        ActivityLogger::log('auth.registered', ['email' => $user->email, 'demo' => $this->demo], $user, $description);

        $this->redirectIntended(default: $user->memberHomeUrl(), navigate: true);
    }

    public function render()
    {
        $title = $this->demo ? 'Try demo' : 'Create account';

        return view('livewire.auth.register-page')
            ->layout('layouts::auth', ['title' => $title]);
    }
}
