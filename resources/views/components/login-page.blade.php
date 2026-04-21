<?php

use App\Services\ActivityLogger;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::auth'), Title('Sign in')] class extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public bool $passwordVisible = false;

    public function login(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $this->ensureIsNotRateLimited();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        Session::regenerate();

        ActivityLogger::log('auth.login', [], Auth::user(), 'Signed in');

        $user = Auth::user();
        $default = $user->usesStaffAppNav()
            ? (string) $user->staffAppHomeUrl()
            : $user->memberHomeUrl();

        $this->redirectIntended(default: $default, navigate: true);
    }

    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
};
?>

<div>
    <div
        class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white p-8 shadow-xl shadow-zinc-900/5 dark:border-zinc-800 dark:bg-zinc-900/80 dark:shadow-none"
    >
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="font-display text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                    Welcome back
                </h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    Sign in to manage bookings and court time.
                </p>
            </div>
        </div>

        <form wire:submit="login" class="mt-8 space-y-5">
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
                <div class="flex items-center justify-between gap-2">
                    <label
                        for="password"
                        class="block text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                    >
                        Password
                    </label>
                </div>
                <div class="relative mt-1.5">
                    <input
                        wire:model="password"
                        id="password"
                        type="{{ $passwordVisible ? 'text' : 'password' }}"
                        autocomplete="current-password"
                        required
                        class="block w-full rounded-xl border border-zinc-200 bg-zinc-50/80 py-3 pl-4 pr-12 text-sm text-zinc-900 outline-none ring-emerald-500/40 transition placeholder:text-zinc-400 focus:border-emerald-500 focus:bg-white focus:ring-4 dark:border-zinc-700 dark:bg-zinc-950/50 dark:text-zinc-100 dark:focus:border-emerald-400 dark:focus:bg-zinc-950"
                        placeholder="••••••••"
                    />
                    <button
                        type="button"
                        wire:click="$toggle('passwordVisible')"
                        class="absolute inset-y-0 right-0 flex items-center rounded-r-xl px-3 text-zinc-500 outline-none transition hover:text-zinc-800 focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 dark:text-zinc-400 dark:hover:text-zinc-200 dark:focus-visible:ring-emerald-400 dark:focus-visible:ring-offset-zinc-900"
                        aria-label="{{ $passwordVisible ? 'Hide password' : 'Show password' }}"
                        aria-pressed="{{ $passwordVisible ? 'true' : 'false' }}"
                    >
                        @if ($passwordVisible)
                            <x-app-icon name="eye-slash" class="size-5" />
                        @else
                            <x-app-icon name="eye" class="size-5" />
                        @endif
                    </button>
                </div>
                @error('password')
                    <p class="mt-1.5 text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-2">
                <input
                    wire:model="remember"
                    id="remember"
                    type="checkbox"
                    class="size-4 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-900"
                />
                <label for="remember" class="text-sm text-zinc-600 dark:text-zinc-400">Remember me</label>
            </div>

            <button
                type="submit"
                class="font-display flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 px-4 py-3.5 text-sm font-bold uppercase tracking-wide text-white shadow-lg shadow-emerald-900/25 transition hover:from-emerald-500 hover:to-teal-500 focus:outline-none focus-visible:ring-4 focus-visible:ring-emerald-500/40 active:scale-[0.99] disabled:opacity-60 dark:shadow-emerald-950/40"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove wire:target="login">Sign in</span>
                <span wire:loading wire:target="login">Signing in…</span>
            </button>
        </form>

        @if (public_registration_enabled())
            <p class="mt-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                New here?
                <a
                    href="{{ route('register') }}"
                    wire:navigate
                    class="font-semibold text-emerald-600 hover:text-emerald-500 dark:text-emerald-400 dark:hover:text-emerald-300"
                >
                    Create an account
                </a>
            </p>
        @endif
    </div>
</div>
