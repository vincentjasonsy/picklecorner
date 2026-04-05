@props([
    'court',
])

@php
    use App\Models\Court;
    use App\Support\Money;

    $client = $court->courtClient;
    $cover = $client?->coverImageUrl();
    $palettes = [
        ['from-emerald-500', 'to-teal-700'],
        ['from-teal-500', 'to-cyan-700'],
        ['from-cyan-600', 'to-blue-800'],
        ['from-lime-500', 'to-emerald-800'],
        ['from-green-600', 'to-teal-900'],
    ];
    $pi = abs(crc32((string) $court->id)) % count($palettes);
    $from = $palettes[$pi][0];
    $to = $palettes[$pi][1];
    $rate = $court->effectiveHourlyRateCents();
    $currency = $client?->currency;
@endphp

<article
    {{ $attributes->class([
        'group flex h-full flex-col overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm transition hover:border-emerald-200 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-emerald-900/60',
    ]) }}
>
    <a href="{{ route('book-now.court', $court) }}" wire:navigate class="block shrink-0">
        <div class="relative aspect-[4/3] overflow-hidden bg-zinc-100 dark:bg-zinc-800">
            @if ($cover)
                <img
                    src="{{ $cover }}"
                    alt=""
                    class="size-full object-cover transition duration-300 group-hover:scale-105"
                    loading="lazy"
                />
            @else
                <div
                    class="flex size-full items-center justify-center bg-gradient-to-br {{ $from }} {{ $to }}"
                    aria-hidden="true"
                >
                    <span class="font-display text-2xl font-extrabold tracking-tight text-white/90">
                        {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($court->name, 0, 2)) }}
                    </span>
                </div>
            @endif
            <div class="absolute left-3 top-3">
                <span
                    class="rounded-full bg-white/95 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-zinc-800 shadow-sm dark:bg-zinc-950/90 dark:text-zinc-100"
                >
                    {{ $court->environment === Court::ENV_INDOOR ? 'Indoor' : 'Outdoor' }}
                </span>
            </div>
        </div>
    </a>
    <div class="flex flex-1 flex-col p-4">
        <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
            {{ $client?->name ?? 'Venue' }}
        </p>
        <h3 class="mt-1 font-display text-lg font-bold text-zinc-900 dark:text-white">
            <a
                href="{{ route('book-now.court', $court) }}"
                wire:navigate
                class="hover:text-emerald-600 dark:hover:text-emerald-400"
            >
                {{ $court->name }}
            </a>
        </h3>
        @if ($client?->city)
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $client->city }}</p>
        @endif
        @if ($client && $client->public_rating_average !== null)
            <p class="mt-2 text-sm font-semibold text-zinc-800 dark:text-zinc-200">
                <span class="text-amber-500 dark:text-amber-400">★</span>
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
    </div>
</article>
