@php($allVenueRows = $this->browseVenueRows())
@php($featuredVenues = $this->featuredVenueClients())

<div class="mx-auto max-w-6xl px-4 pb-14 pt-5 sm:px-6 lg:px-8 lg:pb-16 lg:pt-6">
    {{-- Minimal page label --}}
    <p class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Book now</p>

    {{-- Search & filters — tinted panel + gradient accent --}}
    <div class="relative mt-5" aria-label="Search and filters">
        <div
            class="pointer-events-none absolute inset-x-0 -top-px h-px bg-gradient-to-r from-transparent via-emerald-400/80 to-transparent dark:via-emerald-500/50"
            aria-hidden="true"
        ></div>
        <div
            class="relative overflow-hidden rounded-2xl border border-emerald-200/70 bg-gradient-to-br from-emerald-50/95 via-white to-teal-50/90 shadow-lg shadow-emerald-900/[0.06] ring-1 ring-emerald-500/10 dark:border-emerald-800/60 dark:from-emerald-950/40 dark:via-zinc-900 dark:to-teal-950/35 dark:shadow-emerald-950/20 dark:ring-emerald-500/15"
        >
            <div
                class="pointer-events-none absolute -right-20 -top-16 size-48 rounded-full bg-teal-400/20 blur-3xl dark:bg-teal-500/10"
                aria-hidden="true"
            ></div>
            <div
                class="pointer-events-none absolute -bottom-12 -left-12 size-40 rounded-full bg-emerald-400/15 blur-3xl dark:bg-emerald-500/10"
                aria-hidden="true"
            ></div>

            <div class="relative border-b border-emerald-200/50 bg-gradient-to-r from-emerald-600/10 via-teal-600/10 to-cyan-600/10 px-5 py-3 dark:border-emerald-800/50 dark:from-emerald-500/15 dark:via-teal-600/10 dark:to-cyan-600/10">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="flex size-8 items-center justify-center rounded-lg bg-gradient-to-br from-emerald-600 to-teal-600 text-white shadow-sm dark:from-emerald-500 dark:to-teal-500" aria-hidden="true">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                    </span>
                    <div>
                        <p class="font-display text-sm font-bold text-emerald-950 dark:text-emerald-100">Find your spot</p>
                        <p class="text-xs text-emerald-800/80 dark:text-emerald-200/70">
                            Search, filters, and availability — the venue list updates as you tap.
                        </p>
                    </div>
                </div>
            </div>

            <div class="relative space-y-5 p-5 sm:p-6">
                {{-- Availability filter (court-level open slot in horizon) --}}
                <div
                    class="rounded-xl border border-indigo-300/55 bg-gradient-to-br from-indigo-100/70 via-white to-amber-100/60 p-4 shadow-inner shadow-indigo-900/5 ring-1 ring-indigo-400/15 dark:border-indigo-800/50 dark:from-indigo-950/55 dark:via-zinc-900/80 dark:to-amber-950/35 dark:ring-indigo-500/20"
                >
                    <div class="flex flex-wrap items-start gap-3">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-indigo-600 to-violet-600 text-white shadow-md dark:from-indigo-500 dark:to-violet-500" aria-hidden="true">
                            <svg class="size-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5a2.25 2.25 0 0 0 2.25-2.25m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5a2.25 2.25 0 0 1 2.25 2.25v7.5" />
                            </svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-[11px] font-bold uppercase tracking-wide text-indigo-900 dark:text-indigo-200">Court availability</p>
                            <p class="mt-0.5 text-xs text-indigo-950/75 dark:text-indigo-200/75">
                                “Open soon” keeps venues that still have at least one bookable hour in the next
                                <span class="font-semibold text-indigo-950 dark:text-white">14 days</span>
                                (schedule, closures, blocks &amp; existing bookings).
                            </p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    wire:click="setAvailability('all')"
                                    @class([
                                        'rounded-full px-4 py-2 text-xs font-bold transition sm:text-sm',
                                        $availability === 'all'
                                            ? 'bg-gradient-to-r from-zinc-700 to-zinc-900 text-white shadow-md ring-2 ring-white/25 dark:from-zinc-600 dark:to-zinc-800'
                                            : 'border border-indigo-200/90 bg-white/90 text-indigo-950 hover:border-indigo-400 hover:bg-white dark:border-indigo-800/60 dark:bg-indigo-950/40 dark:text-indigo-100 dark:hover:bg-indigo-950/70',
                                    ])
                                >
                                    All listings
                                </button>
                                <button
                                    type="button"
                                    wire:click="setAvailability('open_soon')"
                                    @class([
                                        'inline-flex items-center gap-2 rounded-full px-4 py-2 text-xs font-bold transition sm:text-sm',
                                        $availability === 'open_soon'
                                            ? 'bg-gradient-to-r from-amber-500 via-orange-500 to-rose-500 text-white shadow-lg shadow-orange-900/25 ring-2 ring-amber-200/50 dark:from-amber-500 dark:via-orange-500 dark:to-rose-600 dark:ring-amber-400/30'
                                            : 'border border-amber-300/90 bg-amber-50/95 text-amber-950 hover:border-amber-500 hover:bg-amber-100/90 dark:border-amber-800/60 dark:bg-amber-950/45 dark:text-amber-100 dark:hover:bg-amber-950/70',
                                    ])
                                >
                                    <span aria-hidden="true" class="text-base leading-none">✨</span>
                                    Open slots soon
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:gap-6">
                    <div class="relative min-w-0 flex-1 lg:max-w-lg">
                        <label for="book-now-search" class="sr-only">Search venues</label>
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-emerald-600 dark:text-emerald-400" aria-hidden="true">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                            </svg>
                        </span>
                        <input
                            id="book-now-search"
                            type="search"
                            wire:model.live.debounce.300ms="search"
                            autocomplete="off"
                            placeholder="Venue, court, or city…"
                            class="w-full rounded-xl border border-emerald-200/80 bg-white/90 py-3 pl-11 pr-4 text-sm text-zinc-900 shadow-inner shadow-emerald-900/5 placeholder:text-emerald-900/35 focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-500/20 dark:border-emerald-700/60 dark:bg-zinc-950/70 dark:text-white dark:placeholder:text-emerald-200/35 dark:focus:border-emerald-400 dark:focus:ring-emerald-500/25"
                        />
                    </div>
                    <div class="flex flex-wrap items-center gap-2 lg:shrink-0">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-teal-700/90 dark:text-teal-300/90">Surface</span>
                        <button
                            type="button"
                            wire:click="setEnvironment('all')"
                            @class([
                                'rounded-full px-3.5 py-2 text-xs font-bold transition sm:text-sm',
                                $environment === 'all'
                                    ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white shadow-md shadow-emerald-900/25 ring-2 ring-white/30 dark:from-emerald-500 dark:to-teal-500 dark:shadow-emerald-950/40'
                                    : 'border border-emerald-200/80 bg-white/90 text-emerald-900 hover:border-emerald-400 hover:bg-emerald-50/90 dark:border-emerald-700/60 dark:bg-emerald-950/30 dark:text-emerald-100 dark:hover:bg-emerald-950/50',
                            ])
                        >
                            All
                        </button>
                        <button
                            type="button"
                            wire:click="setEnvironment('outdoor')"
                            @class([
                                'rounded-full px-3.5 py-2 text-xs font-bold transition sm:text-sm',
                                $environment === 'outdoor'
                                    ? 'bg-gradient-to-r from-sky-600 to-emerald-600 text-white shadow-md shadow-sky-900/25 ring-2 ring-white/30 dark:from-sky-500 dark:to-emerald-500'
                                    : 'border border-sky-200/90 bg-sky-50/90 text-sky-900 hover:border-sky-400 hover:bg-sky-100/80 dark:border-sky-800/60 dark:bg-sky-950/40 dark:text-sky-100 dark:hover:bg-sky-950/60',
                            ])
                        >
                            Outdoor
                        </button>
                        <button
                            type="button"
                            wire:click="setEnvironment('indoor')"
                            @class([
                                'rounded-full px-3.5 py-2 text-xs font-bold transition sm:text-sm',
                                $environment === 'indoor'
                                    ? 'bg-gradient-to-r from-violet-600 to-teal-600 text-white shadow-md shadow-violet-900/25 ring-2 ring-white/30 dark:from-violet-500 dark:to-teal-500'
                                    : 'border border-violet-200/90 bg-violet-50/90 text-violet-900 hover:border-violet-400 hover:bg-violet-100/80 dark:border-violet-800/60 dark:bg-violet-950/40 dark:text-violet-100 dark:hover:bg-violet-950/60',
                            ])
                        >
                            Indoor
                        </button>
                    </div>
                </div>

                @if ($this->cityPills()->isNotEmpty())
                    <div class="flex flex-wrap items-center gap-2 border-t border-emerald-200/60 pt-5 dark:border-emerald-800/50">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-cyan-800 dark:text-cyan-300/90">City</span>
                        @foreach ($this->cityPills() as $cityName)
                            <button
                                type="button"
                                wire:click="setCity(@js($cityName))"
                                @class([
                                    'rounded-full px-3.5 py-2 text-xs font-bold transition sm:text-sm',
                                    $city === $cityName
                                        ? 'bg-gradient-to-r from-cyan-600 to-emerald-600 text-white shadow-md shadow-cyan-900/20 ring-2 ring-white/25 dark:from-cyan-500 dark:to-emerald-500'
                                        : 'border border-cyan-200/80 bg-cyan-50/80 text-cyan-950 hover:border-cyan-400 hover:bg-cyan-100/70 dark:border-cyan-800/50 dark:bg-cyan-950/35 dark:text-cyan-50 dark:hover:bg-cyan-950/55',
                                ])
                            >
                                {{ $cityName }}
                            </button>
                        @endforeach
                        @if ($city !== null)
                            <button
                                type="button"
                                wire:click="setCity(null)"
                                class="rounded-full px-2 py-1.5 text-xs font-bold text-emerald-800 underline decoration-2 decoration-emerald-400/50 underline-offset-2 hover:text-emerald-950 dark:text-emerald-300 dark:hover:text-white"
                            >
                                Clear city
                            </button>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Primary: venues & courts --}}
    <section class="mt-8 scroll-mt-20" aria-labelledby="venues-heading" id="venues">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 id="venues-heading" class="font-display text-3xl font-extrabold tracking-tight text-zinc-900 dark:text-white sm:text-4xl">
                    Venues &amp; courts
                </h1>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                    @if ($allVenueRows->isEmpty())
                        Partner clubs with bookable courts — adjust search or filters above.
                    @else
                        <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $allVenueRows->count() }}</span>
                        {{ \Illuminate\Support\Str::plural('venue', $allVenueRows->count()) }}
                        ·
                        <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $this->filteredCourts()->count() }}</span>
                        {{ \Illuminate\Support\Str::plural('court', $this->filteredCourts()->count()) }}
                        match your filters.
                    @endif
                </p>
            </div>
        </div>

        @if ($allVenueRows->isEmpty())
            <div
                class="mt-8 flex flex-col items-center justify-center rounded-2xl border border-dashed border-zinc-300 bg-white px-6 py-14 text-center dark:border-zinc-600 dark:bg-zinc-900/50"
            >
                <span class="flex size-12 items-center justify-center rounded-xl bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                    <x-app-icon name="magnifying-glass" class="size-7" />
                </span>
                <p class="mt-4 max-w-md text-base font-semibold text-zinc-800 dark:text-zinc-200">
                    @if (trim($search) !== '')
                        No venues match that search
                    @else
                        Nothing matches these filters yet
                    @endif
                </p>
                <p class="mt-2 max-w-md text-sm text-zinc-600 dark:text-zinc-400">
                    @if (trim($search) !== '')
                        Try another keyword or clear the search box.
                    @else
                        Clear the city filter or switch indoor / outdoor to see more clubs.
                    @endif
                </p>
            </div>
        @else
            <ul class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3" role="list">
                @foreach ($allVenueRows as $row)
                    @php($venue = $row['venue'])
                    <li
                        wire:key="all-venues-{{ $venue->id }}"
                        class="group relative flex flex-col overflow-hidden rounded-2xl border border-zinc-200/90 bg-white shadow-md shadow-zinc-900/5 ring-1 ring-black/[0.03] transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-lg hover:shadow-emerald-900/10 dark:border-zinc-700 dark:bg-zinc-900 dark:ring-white/[0.04] dark:hover:border-emerald-700"
                    >
                        <a
                            href="{{ $this->venueBookUrl($venue) }}"
                            wire:navigate
                            class="absolute inset-0 z-[1] rounded-2xl focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 dark:focus-visible:ring-emerald-400 dark:ring-offset-zinc-900"
                            aria-label="Book {{ $venue->name }}"
                        ></a>
                        <div class="relative z-[2] flex flex-col">
                            <div class="relative bg-zinc-100 dark:bg-zinc-800">
                                <div class="pointer-events-auto">
                                    <x-image-carousel
                                        :slides="$venue->carouselSlides()"
                                        :interval="6000"
                                        aria-label="{{ $venue->name }} photos"
                                        aspect-class="aspect-[4/3] sm:aspect-[16/10]"
                                        class="w-full"
                                    >
                                        <div
                                            class="relative flex aspect-[4/3] items-center justify-center bg-gradient-to-br from-emerald-500 to-teal-800 sm:aspect-[16/10]"
                                            aria-hidden="true"
                                        >
                                            <span class="font-display text-3xl font-extrabold text-white/90 transition group-hover:scale-105">
                                                {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($venue->name, 0, 2)) }}
                                            </span>
                                        </div>
                                    </x-image-carousel>
                                </div>
                            </div>
                            <div class="pointer-events-none flex flex-1 flex-col p-5">
                                <p class="text-xs font-bold uppercase tracking-wider text-emerald-700/90 dark:text-emerald-400/90">
                                    Venue
                                </p>
                                <h2 class="mt-1 font-display text-lg font-bold text-zinc-900 dark:text-white">
                                    {{ $venue->name }}
                                </h2>
                                @if ($venue->city)
                                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $venue->city }}</p>
                                @endif
                                <p class="mt-3 text-xs font-semibold text-emerald-700 dark:text-emerald-400">
                                    {{ $row['court_count'] }} {{ \Illuminate\Support\Str::plural('court', $row['court_count']) }}
                                    match filters
                                    <span class="font-normal text-zinc-500 dark:text-zinc-400">· Tap card to book</span>
                                </p>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    {{-- Secondary discovery --}}
    @if ($featuredVenues->isNotEmpty() || $this->recentlyViewedCourts()->isNotEmpty() || ($this->nearbyCity() && $this->nearbyCourts()->isNotEmpty()) || $this->topRatedCourts()->isNotEmpty())
        <div class="mt-14 border-t border-zinc-200 pt-12 dark:border-zinc-800">
            <h2 class="font-display text-lg font-bold text-zinc-500 dark:text-zinc-400">More to explore</h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-500">
                Featured picks, shortcuts, and ratings — optional extras after you’ve scanned the full list above.
            </p>

            @if ($featuredVenues->isNotEmpty())
                <section class="mt-10" aria-labelledby="featured-venues-heading">
                    <div class="flex flex-wrap items-end justify-between gap-4">
                        <div class="flex items-start gap-3">
                            <span class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700 dark:bg-amber-950/80 dark:text-amber-300">
                                <x-app-icon name="star-solid" class="size-4" />
                            </span>
                            <div>
                                <h3 id="featured-venues-heading" class="font-display text-base font-bold text-zinc-900 dark:text-white">
                                    Featured
                                    <span class="font-medium text-zinc-500 dark:text-zinc-400">· {{ $this->effectiveCityForFeatured() }}</span>
                                </h3>
                                <p class="mt-0.5 text-sm text-zinc-600 dark:text-zinc-400">Partner picks — swipe or use arrows.</p>
                            </div>
                        </div>
                    </div>
                    <div
                        class="featured-venues-splide splide mt-5 pb-6"
                        data-featured-venues-slider
                        aria-label="Featured venues carousel"
                    >
                        <div class="splide__track">
                            <ul class="splide__list">
                                @foreach ($featuredVenues as $venue)
                                    <li class="splide__slide" wire:key="featured-venue-{{ $venue->id }}">
                                        <article
                                            class="relative flex h-full flex-col overflow-hidden rounded-2xl border border-amber-200/90 bg-gradient-to-b from-amber-50/90 to-white shadow-md shadow-amber-900/10 dark:border-amber-900/40 dark:from-amber-950/40 dark:to-zinc-900 dark:shadow-none"
                                        >
                                            <a
                                                href="{{ $this->venueBookUrl($venue) }}"
                                                wire:navigate
                                                class="absolute inset-0 z-[1] rounded-2xl focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 dark:focus-visible:ring-amber-400 dark:ring-offset-zinc-900"
                                                aria-label="Book featured venue {{ $venue->name }}"
                                            ></a>
                                            <div class="relative z-[2] flex flex-1 flex-col">
                                                <div class="bg-zinc-100 dark:bg-zinc-800">
                                                    <div class="pointer-events-auto">
                                                        <x-image-carousel
                                                            :slides="$venue->carouselSlides()"
                                                            :interval="6500"
                                                            aria-label="{{ $venue->name }} photos"
                                                            aspect-class="aspect-[4/3]"
                                                            class="w-full"
                                                        >
                                                            <div
                                                                class="relative flex aspect-[4/3] items-center justify-center bg-gradient-to-br from-amber-500 to-orange-900"
                                                                aria-hidden="true"
                                                            >
                                                                <span class="font-display text-3xl font-extrabold text-white/90">
                                                                    {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($venue->name, 0, 2)) }}
                                                                </span>
                                                            </div>
                                                        </x-image-carousel>
                                                    </div>
                                                </div>
                                                <div class="pointer-events-none flex flex-1 flex-col p-4">
                                                    <p class="text-[11px] font-bold uppercase tracking-wider text-amber-800 dark:text-amber-300/90">
                                                        Featured venue
                                                    </p>
                                                    <h4 class="mt-1 font-display text-base font-bold text-zinc-900 dark:text-white">
                                                        {{ $venue->name }}
                                                    </h4>
                                                    @if ($venue->public_rating_average !== null)
                                                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                                                            {{ number_format((float) $venue->public_rating_average, 1) }}★ guest rating
                                                            @if ($venue->public_rating_count > 0)
                                                                <span class="text-zinc-500">({{ $venue->public_rating_count }})</span>
                                                            @endif
                                                        </p>
                                                    @endif
                                                    <p class="mt-3 text-xs font-semibold text-amber-800 dark:text-amber-300/90">
                                                        Tap card to book
                                                    </p>
                                                </div>
                                            </div>
                                        </article>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </section>
            @endif

            @if ($this->recentlyViewedCourts()->isNotEmpty())
                <section class="mt-10 rounded-xl border border-zinc-200/80 bg-zinc-50/70 p-5 dark:border-zinc-800 dark:bg-zinc-900/50 sm:p-6" aria-labelledby="recently-viewed-heading">
                    <div class="flex items-start gap-3">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-white text-emerald-600 shadow-sm dark:bg-zinc-800 dark:text-emerald-400">
                            <x-app-icon name="clock" class="size-4" />
                        </span>
                        <div>
                            <h3 id="recently-viewed-heading" class="font-display text-base font-bold text-zinc-900 dark:text-white">
                                Recently viewed
                            </h3>
                            <p class="mt-0.5 text-sm text-zinc-600 dark:text-zinc-400">Courts you opened before.</p>
                        </div>
                    </div>
                    <div
                        class="mt-4 flex gap-4 overflow-x-auto pb-2 [-ms-overflow-style:none] [scrollbar-width:none] sm:grid sm:grid-cols-2 sm:overflow-visible lg:grid-cols-4 [&::-webkit-scrollbar]:hidden"
                    >
                        @foreach ($this->recentlyViewedCourts() as $c)
                            <div class="w-[min(100%,280px)] shrink-0 sm:w-auto">
                                <x-court-browse-card :court="$c" />
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($this->nearbyCity() && $this->nearbyCourts()->isNotEmpty())
                <section class="mt-10" aria-labelledby="nearby-heading">
                    <div class="flex items-start gap-3">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-950/80 dark:text-emerald-300">
                            <x-app-icon name="map-pin" class="size-4" />
                        </span>
                        <div>
                            <h3 id="nearby-heading" class="font-display text-base font-bold text-zinc-900 dark:text-white">
                                Nearby
                                <span class="font-medium text-zinc-500 dark:text-zinc-400">· {{ $this->nearbyCity() }}</span>
                            </h3>
                            <p class="mt-0.5 text-sm text-zinc-600 dark:text-zinc-400">Default area — change with city pills above.</p>
                        </div>
                    </div>
                    <div
                        class="mt-4 flex gap-4 overflow-x-auto pb-2 [-ms-overflow-style:none] [scrollbar-width:none] sm:grid sm:grid-cols-2 sm:overflow-visible lg:grid-cols-4 [&::-webkit-scrollbar]:hidden"
                    >
                        @foreach ($this->nearbyCourts() as $c)
                            <div class="w-[min(100%,280px)] shrink-0 sm:w-auto">
                                <x-court-browse-card :court="$c" />
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($this->topRatedCourts()->isNotEmpty())
                <section class="mt-10 rounded-xl border border-zinc-200/80 bg-gradient-to-br from-white to-emerald-50/40 p-5 dark:border-zinc-800 dark:from-zinc-900 dark:to-emerald-950/30 sm:p-6" aria-labelledby="rated-heading">
                    <div class="flex items-start gap-3">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700 dark:bg-amber-950/80 dark:text-amber-300">
                            <x-app-icon name="star-solid" class="size-4" />
                        </span>
                        <div>
                            <h3 id="rated-heading" class="font-display text-base font-bold text-zinc-900 dark:text-white">
                                Top rated venues
                            </h3>
                            <p class="mt-0.5 text-sm text-zinc-600 dark:text-zinc-400">Highest guest ratings.</p>
                        </div>
                    </div>
                    <div
                        class="mt-4 flex gap-4 overflow-x-auto pb-2 [-ms-overflow-style:none] [scrollbar-width:none] sm:grid sm:grid-cols-2 sm:overflow-visible lg:grid-cols-4 [&::-webkit-scrollbar]:hidden"
                    >
                        @foreach ($this->topRatedCourts() as $c)
                            <div class="w-[min(100%,280px)] shrink-0 sm:w-auto">
                                <x-court-browse-card :court="$c" />
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    @endif
</div>
