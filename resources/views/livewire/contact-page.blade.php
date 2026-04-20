@php
    $supportEmail = config('mail.from.address');
    if (! is_string($supportEmail) || $supportEmail === '') {
        $supportEmail = 'support@picklecorner.ph';
    }
    $mailtoSubject = rawurlencode(config('app.name').' — Contact');
@endphp

<div>
    <section class="border-b border-zinc-200 bg-gradient-to-b from-emerald-950 via-teal-950 to-zinc-950 py-14 dark:border-zinc-800 sm:py-16">
        <div class="mx-auto max-w-3xl px-4 text-center sm:px-6 lg:px-8">
            <p class="font-display text-xs font-bold uppercase tracking-[0.25em] text-emerald-300/90">
                {{ config('app.name') }}
            </p>
            <h1 class="font-display mt-4 text-3xl font-bold uppercase tracking-tight text-white sm:text-4xl">
                Reach out to us!
            </h1>
            <p class="mx-auto mt-4 max-w-xl text-sm leading-relaxed text-emerald-100/90">
                Questions about partnering, a demo, or bringing {{ config('app.name') }} to your venue? Tap the address
                below to open your email app — we’ll reply when we can.
            </p>
        </div>
    </section>

    <section class="mx-auto max-w-lg px-4 py-14 sm:px-6 lg:px-8">
        <div
            class="rounded-2xl border border-zinc-200 bg-white p-8 text-center shadow-sm dark:border-zinc-800 dark:bg-zinc-900/80 sm:p-10"
        >
            <p class="font-display text-xs font-bold uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">
                Email
            </p>
            <a
                href="mailto:{{ $supportEmail }}?subject={{ $mailtoSubject }}"
                class="font-display mt-4 inline-block break-all text-xl font-bold text-emerald-700 underline-offset-4 hover:text-emerald-600 hover:underline dark:text-emerald-400 dark:hover:text-emerald-300"
            >
                {{ $supportEmail }}
            </a>
        </div>
        <p class="mx-auto mt-10 max-w-xl text-center text-xs leading-relaxed text-zinc-500 dark:text-zinc-400">
            <a href="{{ url('/') }}" wire:navigate class="font-semibold text-emerald-700 hover:underline dark:text-emerald-400">
                ← Back to home
            </a>
        </p>
    </section>
</div>
