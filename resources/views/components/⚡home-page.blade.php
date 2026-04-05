<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::guest'), Title('Home')] class extends Component {};
?>

<div
    class="mx-auto flex max-w-5xl flex-col items-center justify-center px-4 py-24 text-center sm:px-6 lg:px-8"
>
    <h1 class="text-3xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-100 sm:text-4xl">
        {{ config('app.name') }}
    </h1>
    <p class="mt-4 max-w-lg text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
        An all-in-one pickleball app — starting with booking and growing into everything your club needs. Built with
        Laravel {{ app()->version() }} and Livewire.
    </p>
    @guest
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
    @endguest
</div>