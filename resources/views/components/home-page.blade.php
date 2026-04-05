<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::guest'), Title('Home')] class extends Component {};
?>

<div class="mx-auto max-w-5xl px-4 py-16 text-center sm:px-6 sm:py-24 lg:px-8">
    <h1 class="text-3xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-100 sm:text-4xl">
        {{ config('app.name') }}
    </h1>
    <p class="mx-auto mt-4 max-w-lg text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
        An all-in-one pickleball app — starting with booking and growing into everything your club needs. Built with
        Laravel {{ app()->version() }} and Livewire.
    </p>

    @auth
        @php($homeUser = auth()->user())
        <div
            class="mx-auto mt-10 max-w-md rounded-2xl border border-emerald-200/80 bg-gradient-to-br from-emerald-50 to-teal-50 p-6 text-center shadow-sm dark:border-emerald-900/50 dark:from-emerald-950/40 dark:to-teal-950/30"
        >
            <x-icon name="squares-2x2" class="mx-auto size-10 text-emerald-600 dark:text-emerald-400" />
            <p class="mt-4 text-base font-semibold text-zinc-900 dark:text-zinc-100">
                @if ($homeUser->usesStaffAppNav())
                    You’re signed in — jump back into operations anytime.
                @else
                    You’re in! Your locker room has bookings, stats, and profile settings.
                @endif
            </p>
            <div class="mt-5 flex flex-wrap items-center justify-center gap-3">
                @if ($homeUser->usesStaffAppNav())
                    <a
                        href="{{ $homeUser->staffAppHomeUrl() }}"
                        wire:navigate
                        class="inline-flex items-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600"
                    >
                        Open app
                    </a>
                @else
                    <a
                        href="{{ $homeUser->memberHomeUrl() }}"
                        wire:navigate
                        class="inline-flex items-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600"
                    >
                        My court
                    </a>
                @endif
                <a
                    href="{{ route('about') }}"
                    wire:navigate
                    class="text-sm font-semibold text-emerald-800 underline-offset-4 hover:underline dark:text-emerald-300"
                >
                    About the project
                </a>
            </div>
        </div>
    @else
        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
            <a
                href="{{ route('login') }}"
                wire:navigate
                class="inline-flex items-center rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-800 transition-colors hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-100 dark:hover:bg-zinc-800"
            >
                Log in
            </a>
            <a
                href="{{ route('register') }}"
                wire:navigate
                class="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600"
            >
                Register
            </a>
        </div>
    @endauth
</div>