@props([
    /** @var \App\Models\CourtClient $venue */
    'venue',
])

@php
    $amenities = $venue->publicAmenitiesList();
    $mapEmbed = $venue->openStreetMapEmbedUrl();
    $gmaps = $venue->googleMapsUrl();
    $telDigits = $venue->phone !== null && trim((string) $venue->phone) !== ''
        ? preg_replace('/[^0-9+]/', '', (string) $venue->phone)
        : '';
@endphp

<div class="space-y-8">
    @if (! $venue->hasPublicListingExtras())
        <p class="text-sm text-zinc-500 dark:text-zinc-400">
            This venue has not added a public address, map pin, or amenities yet.@if (public_reviews_enabled()) You can still see member reviews beside this panel.@endif
        </p>
    @else
        @if ($venue->address !== null && trim((string) $venue->address) !== '')
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Address</h3>
                <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-zinc-800 dark:text-zinc-200">
                    {{ trim((string) $venue->address) }}
                </p>
                @if ($venue->city)
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $venue->city }}</p>
                @endif
            </div>
        @elseif ($venue->city)
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">City</h3>
                <p class="mt-2 text-sm text-zinc-800 dark:text-zinc-200">{{ $venue->city }}</p>
            </div>
        @endif

        @if (
            ($venue->phone !== null && trim((string) $venue->phone) !== '')
            || ($venue->facebook_url !== null && trim((string) $venue->facebook_url) !== '')
            || $gmaps !== null
        )
            <div class="flex flex-wrap gap-3">
                @if ($venue->phone !== null && trim((string) $venue->phone) !== '')
                    <a
                        href="{{ $telDigits !== '' ? 'tel:'.$telDigits : '#' }}"
                        class="inline-flex items-center gap-2 rounded-xl border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-zinc-800 hover:border-emerald-400 hover:text-emerald-800 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:border-emerald-600 dark:hover:text-emerald-200"
                    >
                        <x-app-icon name="phone" class="size-4 text-emerald-600 dark:text-emerald-400" />
                        {{ trim((string) $venue->phone) }}
                    </a>
                @endif
                @if ($venue->facebook_url !== null && trim((string) $venue->facebook_url) !== '')
                    <a
                        href="{{ $venue->facebook_url }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-2 rounded-xl border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-zinc-800 hover:border-emerald-400 hover:text-emerald-800 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:border-emerald-600 dark:hover:text-emerald-200"
                    >
                        <span class="text-[11px] font-bold uppercase tracking-wide text-blue-600 dark:text-blue-400">f</span>
                        Facebook
                    </a>
                @endif
                @if ($gmaps !== null)
                    <a
                        href="{{ $gmaps }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-2 rounded-xl border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-zinc-800 hover:border-emerald-400 hover:text-emerald-800 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:border-emerald-600 dark:hover:text-emerald-200"
                    >
                        <x-app-icon name="map-pin" class="size-4 text-emerald-600 dark:text-emerald-400" />
                        Open in Google Maps
                    </a>
                @endif
            </div>
        @endif

        @if ($amenities !== [])
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Amenities</h3>
                <ul class="mt-3 list-inside list-disc space-y-1.5 text-sm text-zinc-800 dark:text-zinc-200">
                    @foreach ($amenities as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($mapEmbed !== null)
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Map</h3>
                <div class="mt-3 overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
                    <iframe
                        title="Map — {{ $venue->name }}"
                        src="{{ $mapEmbed }}"
                        class="aspect-[4/3] h-[min(22rem,50vw)] w-full bg-zinc-100 dark:bg-zinc-800"
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                    ></iframe>
                </div>
                <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                    Map data ©
                    <a href="https://www.openstreetmap.org/copyright" class="underline hover:text-zinc-700 dark:hover:text-zinc-300" target="_blank" rel="noopener">OpenStreetMap</a>
                    contributors.
                </p>
            </div>
        @endif
    @endif
</div>
