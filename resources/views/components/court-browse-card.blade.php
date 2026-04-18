@props([
    'court',
])

@php
    use App\Models\Court;
    use App\Support\Money;

    $client = $court->courtClient;
    $rate = $court->effectiveHourlyRateCents();
    $currency = $client?->currency;
    $href = route('book-now.court', $court);
@endphp

<article
    {{ $attributes->class([
        'group relative flex h-full flex-col overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm transition hover:border-emerald-200 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-emerald-900/60',
    ]) }}
>
    <a
        href="{{ $href }}"
        wire:navigate
        class="absolute inset-0 z-[1] rounded-2xl focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 dark:focus-visible:ring-emerald-400 dark:ring-offset-zinc-900"
        aria-label="View {{ $court->name }}{{ $client ? ' at '.$client->name : '' }}"
    ></a>
    <div class="relative z-[2] flex flex-1 flex-col pointer-events-none">
        <div class="relative aspect-[4/3] shrink-0 overflow-hidden bg-zinc-100 dark:bg-zinc-800">
            <img
                src="{{ $court->primaryDisplayImageUrl() }}"
                alt=""
                role="presentation"
                class="size-full object-cover object-center transition duration-300 group-hover:scale-105"
                loading="lazy"
            />
            <div class="absolute left-3 top-3">
                <span
                    class="rounded-full bg-white/95 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-zinc-800 shadow-sm dark:bg-zinc-950/90 dark:text-zinc-100"
                >
                    {{ $court->environment === Court::ENV_INDOOR ? 'Indoor' : 'Outdoor' }}
                </span>
            </div>
        </div>
        <div class="flex flex-1 flex-col p-4">
            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                {{ $client?->name ?? 'Venue' }}
            </p>
            <h3 class="mt-1 font-display text-lg font-bold text-zinc-900 dark:text-white">
                {{ $court->name }}
            </h3>
            @if ($client?->city)
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $client->city }}</p>
            @endif
            @if ($client && $client->public_rating_average !== null)
                <p class="mt-2 inline-flex flex-wrap items-center gap-1 text-sm font-semibold text-zinc-800 dark:text-zinc-200">
                    <x-app-icon name="star-solid" class="size-4 text-amber-500 dark:text-amber-400" />
                    {{ number_format((float) $client->public_rating_average, 1) }}
                    @if ($client->public_rating_count > 0)
                        <span class="font-normal text-zinc-500 dark:text-zinc-400">
                            ({{ number_format($client->public_rating_count) }})
                        </span>
                    @endif
                </p>
            @endif
            @if ($rate !== null)
                <p class="mt-auto pt-3 text-sm font-bold text-emerald-700 dark:text-emerald-400">
                    {{ Money::formatMinor($rate, $currency) }}
                    <span class="font-medium text-zinc-500 dark:text-zinc-400">/ hr</span>
                </p>
            @endif
            <p class="mt-3 text-xs font-semibold text-emerald-700 dark:text-emerald-400">
                Tap card to view
            </p>
        </div>
    </div>
</article>
