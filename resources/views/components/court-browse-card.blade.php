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
    $comingSoon = $client !== null && $client->isOpeningSoonVenue();
@endphp

<article
    {{ $attributes->class([
        'group relative flex h-full flex-col overflow-hidden rounded-2xl border bg-white shadow-sm transition dark:bg-zinc-900',
        'border-zinc-300 bg-zinc-100 shadow-xl shadow-zinc-900/20 saturate-[0.92] ring-1 ring-zinc-900/[0.07] dark:border-zinc-600 dark:bg-zinc-950 dark:shadow-black/45 dark:ring-white/[0.06]' => $comingSoon,
        'border-zinc-200 hover:border-emerald-200 hover:shadow-md dark:border-zinc-800 dark:hover:border-emerald-900/60' => ! $comingSoon,
    ]) }}
>
    @if ($comingSoon)
        <span
            class="pointer-events-none absolute inset-0 z-[3] rounded-2xl bg-gradient-to-b from-zinc-950/20 to-zinc-950/35 dark:from-black/30 dark:to-black/50"
            aria-hidden="true"
        ></span>
        <span
            class="absolute inset-0 z-[1] cursor-not-allowed rounded-2xl ring-1 ring-inset ring-zinc-900/[0.12] dark:ring-white/[0.08]"
            role="presentation"
            aria-hidden="true"
        ></span>
    @else
        <a
            href="{{ $href }}"
            wire:navigate
            class="absolute inset-0 z-[1] rounded-2xl focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 dark:focus-visible:ring-emerald-400 dark:ring-offset-zinc-900"
            aria-label="View {{ $court->name }}{{ $client ? ' at '.$client->name : '' }}"
        ></a>
    @endif
    <div class="relative z-[2] flex flex-1 flex-col pointer-events-none">
        <div @class([
            'relative aspect-[4/3] shrink-0 overflow-hidden bg-zinc-100 dark:bg-zinc-800',
            'brightness-[0.92] contrast-[0.98]' => $comingSoon,
        ])>
            <img
                src="{{ $court->primaryDisplayImageUrl() }}"
                alt=""
                role="presentation"
                class="size-full object-cover object-center transition duration-300 group-hover:scale-105"
                loading="lazy"
            />
            <div class="absolute left-3 top-3 z-[4] flex flex-wrap items-start gap-2">
                <span
                    class="rounded-full bg-white/95 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-zinc-800 shadow-sm dark:bg-zinc-950/90 dark:text-zinc-100"
                >
                    {{ $court->environment === Court::ENV_INDOOR ? 'Indoor' : 'Outdoor' }}
                </span>
                @if ($comingSoon)
                    <span
                        class="rounded-full bg-zinc-950 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white shadow-lg shadow-black/35 ring-1 ring-white/20 backdrop-blur-sm"
                    >
                        Coming soon
                    </span>
                @endif
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
            @if (public_reviews_enabled() && $client && $client->public_rating_average !== null)
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
                @if ($comingSoon)
                    Coming soon — booking not open yet
                @else
                    Tap card to view
                @endif
            </p>
        </div>
    </div>
</article>
