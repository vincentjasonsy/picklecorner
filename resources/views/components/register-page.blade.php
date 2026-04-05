<?php

use App\Models\User;
use App\Models\UserType;
use App\Services\ActivityLogger;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::auth'), Title('Create account')] class extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        $userTypeId = UserType::query()->where('slug', UserType::SLUG_USER)->value('id');

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'user_type_id' => $userTypeId,
        ]);

        event(new Registered($user));

        Auth::login($user);

        Session::regenerate();

        ActivityLogger::log('auth.registered', ['email' => $user->email], $user, 'New account registered');

        $this->redirect($user->memberHomeUrl(), navigate: true);
    }
};
?>

<div>
    <div
        class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white p-8 shadow-xl shadow-zinc-900/5 dark:border-zinc-800 dark:bg-zinc-900/80 dark:shadow-none"
    >
        <div>
            <h2 class="font-display text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                Join the club
            </h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                Create your player account and start booking courts in minutes.
            </p>
        </div>

        <form wire:submit="register" class="mt-8 space-y-5">
            <div>
                <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                    Full name
                </label>
                <input
                    wire:model="name"
                    id="name"
                    type="text"
                    autocomplete="name"
                    required
                    class="mt-1.5 block w-full rounded-xl border border-zinc-200 bg-zinc-50/80 px-4 py-3 text-sm text-zinc-900 outline-none ring-emerald-500/40 transition placeholder:text-zinc-400 focus:border-emerald-500 focus:bg-white focus:ring-4 dark:border-zinc-700 dark:bg-zinc-950/50 dark:text-zinc-100 dark:focus:border-emerald-400 dark:focus:bg-zinc-950"
                    placeholder="Alex Johnson"
                />
                @error('name')
                    <p class="mt-1.5 text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                    Email
                </label>
                <input
                    wire:model="email"
                    id="email"
                    type="email"
                    autocomplete="email"
                    required
                    class="mt-1.5 block w-full rounded-xl border border-zinc-200 bg-zinc-50/80 px-4 py-3 text-sm text-zinc-900 outline-none ring-emerald-500/40 transition placeholder:text-zinc-400 focus:border-emerald-500 focus:bg-white focus:ring-4 dark:border-zinc-700 dark:bg-zinc-950/50 dark:text-zinc-100 dark:focus:border-emerald-400 dark:focus:bg-zinc-950"
                    placeholder="you@example.com"
                />
                @error('email')
                    <p class="mt-1.5 text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label
                    for="password"
                    class="block text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                >
                    Password
                </label>
                <input
                    wire:model="password"
                    id="password"
                    type="password"
                    autocomplete="new-password"
                    required
                    class="mt-1.5 block w-full rounded-xl border border-zinc-200 bg-zinc-50/80 px-4 py-3 text-sm text-zinc-900 outline-none ring-emerald-500/40 transition placeholder:text-zinc-400 focus:border-emerald-500 focus:bg-white focus:ring-4 dark:border-zinc-700 dark:bg-zinc-950/50 dark:text-zinc-100 dark:focus:border-emerald-400 dark:focus:bg-zinc-950"
                    placeholder="Min. 8 characters"
                />
                @error('password')
                    <p class="mt-1.5 text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label
                    for="password_confirmation"
                    class="block text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                >
                    Confirm password
                </label>
                <input
                    wire:model="password_confirmation"
                    id="password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    required
                    class="mt-1.5 block w-full rounded-xl border border-zinc-200 bg-zinc-50/80 px-4 py-3 text-sm text-zinc-900 outline-none ring-emerald-500/40 transition placeholder:text-zinc-400 focus:border-emerald-500 focus:bg-white focus:ring-4 dark:border-zinc-700 dark:bg-zinc-950/50 dark:text-zinc-100 dark:focus:border-emerald-400 dark:focus:bg-zinc-950"
                    placeholder="Repeat password"
                />
            </div>

            <button
                type="submit"
                class="font-display flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 px-4 py-3.5 text-sm font-bold uppercase tracking-wide text-white shadow-lg shadow-emerald-900/25 transition hover:from-emerald-500 hover:to-teal-500 focus:outline-none focus-visible:ring-4 focus-visible:ring-emerald-500/40 active:scale-[0.99] disabled:opacity-60 dark:shadow-emerald-950/40"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove wire:target="register">Create account</span>
                <span wire:loading wire:target="register">Creating…</span>
            </button>
        </form>

        <p class="mt-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
            Already have an account?
            <a
                href="{{ route('login') }}"
                wire:navigate
                class="font-semibold text-emerald-600 hover:text-emerald-500 dark:text-emerald-400 dark:hover:text-emerald-300"
            >
                Sign in
            </a>
        </p>
    </div>
</div>
