@props([
    'title',
    'subtitle' => null,
    'meta' => null,
])
<section class="border-b border-zinc-200 bg-gradient-to-b from-emerald-950 via-teal-950 to-zinc-950 py-12 dark:border-zinc-800 sm:py-14">
    <div class="mx-auto max-w-3xl px-4 text-center sm:px-6 lg:px-8">
        <p class="font-display text-xs font-bold uppercase tracking-[0.25em] text-emerald-300/90">
            {{ config('app.name') }}
        </p>
        <h1 class="font-display mt-4 text-3xl font-bold uppercase tracking-tight text-white sm:text-4xl">
            {{ $title }}
        </h1>
        @if ($subtitle)
            <p class="mx-auto mt-4 max-w-2xl text-sm leading-relaxed text-emerald-100/90 lg:max-w-3xl">
                {{ $subtitle }}
            </p>
        @endif
        @if ($meta)
            <p class="mx-auto mt-4 max-w-xl text-xs font-medium tracking-wide text-emerald-200/85">
                {{ $meta }}
            </p>
        @endif
    </div>
</section>
