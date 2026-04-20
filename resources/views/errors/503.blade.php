@component('layouts.guest', ['title' => 'Be right back'])
    <div class="mx-auto max-w-2xl px-4 py-20 sm:px-6 sm:py-28 lg:px-8">
        <p class="font-display text-sm font-semibold uppercase tracking-[0.2em] text-sky-600 dark:text-sky-400">
            Error 503
        </p>
        <h1 class="mt-3 font-display text-4xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50 sm:text-5xl">
            Be right back
        </h1>
        <p class="mt-4 text-lg leading-relaxed text-zinc-600 dark:text-zinc-400">
            {{ config('app.name') }} is temporarily unavailable—usually during maintenance. Refresh in a minute or two.
        </p>
        <div class="mt-10">
            <a
                href="{{ route('home') }}"
                wire:navigate
                class="font-display inline-flex items-center justify-center rounded-full bg-gradient-to-r from-emerald-600 to-teal-600 px-6 py-3 text-sm font-bold uppercase tracking-wide text-white shadow-md shadow-emerald-900/20 transition hover:from-emerald-500 hover:to-teal-500 dark:shadow-emerald-950/40"
            >
                Try again
            </a>
        </div>
    </div>
@endcomponent
