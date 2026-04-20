@component('layouts.guest', ['title' => 'Something went wrong'])
    <div class="mx-auto max-w-2xl px-4 py-20 sm:px-6 sm:py-28 lg:px-8">
        <p class="font-display text-sm font-semibold uppercase tracking-[0.2em] text-red-600 dark:text-red-400">
            Error 500
        </p>
        <h1 class="mt-3 font-display text-4xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50 sm:text-5xl">
            Something went wrong
        </h1>
        <p class="mt-4 text-lg leading-relaxed text-zinc-600 dark:text-zinc-400">
            We hit a problem on our side. Try again in a moment. If it keeps happening, contact us and we’ll help.
        </p>
        <div class="mt-10 flex flex-col gap-3 sm:flex-row sm:items-center">
            <a
                href="{{ route('home') }}"
                wire:navigate
                class="font-display inline-flex items-center justify-center rounded-full bg-gradient-to-r from-emerald-600 to-teal-600 px-6 py-3 text-sm font-bold uppercase tracking-wide text-white shadow-md shadow-emerald-900/20 transition hover:from-emerald-500 hover:to-teal-500 dark:shadow-emerald-950/40"
            >
                Back to home
            </a>
            <a
                href="{{ route('contact') }}"
                wire:navigate
                class="inline-flex items-center justify-center rounded-full border border-zinc-300 bg-white px-6 py-3 text-sm font-semibold text-zinc-800 transition hover:border-emerald-500/50 hover:text-emerald-800 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:border-emerald-500/40 dark:hover:text-emerald-300"
            >
                Contact
            </a>
        </div>
    </div>
@endcomponent
