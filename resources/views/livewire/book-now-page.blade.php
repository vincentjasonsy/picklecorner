<div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
    <header class="max-w-2xl">
        <h1 class="font-display text-3xl font-extrabold tracking-tight text-zinc-900 dark:text-white sm:text-4xl">
            Book now
        </h1>
        <p class="mt-3 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
            Explore courts across our partner venues — filter by location or surface, check ratings, and pick up where
            you left off with recently viewed spots.
        </p>
        <div
            class="mt-5 rounded-xl border border-emerald-200/80 bg-emerald-50/80 px-4 py-3 text-sm text-emerald-950 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-100"
        >
            <span class="font-semibold">Player-facing tool.</span>
            <span class="text-emerald-900/90 dark:text-emerald-100/90">
                Venues, desk staff, coaches, and GameQ use other workspaces —
            </span>
            <a href="{{ url('/#pricing') }}" wire:navigate class="font-semibold text-emerald-800 underline-offset-2 hover:underline dark:text-emerald-300">
                how convenience fees work
            </a>
            ·
            <a href="{{ url('/#tools') }}" wire:navigate class="font-semibold text-emerald-800 underline-offset-2 hover:underline dark:text-emerald-300">
                full platform tour
            </a>
            .
        </div>
    </header>

    {{-- Filter pills --}}
    <div class="mt-8 flex flex-col gap-3">
        <p class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Filters</p>
        <div class="flex flex-wrap gap-2">
            <button
                type="button"
                wire:click="setEnvironment('all')"
                @class([
                    'rounded-full px-4 py-2 text-sm font-semibold transition',
                    $environment === 'all'
                        ? 'bg-emerald-600 text-white shadow-sm dark:bg-emerald-500'
                        : 'border border-zinc-200 bg-white text-zinc-700 hover:border-emerald-300 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-emerald-800',
                ])
            >
                All courts
            </button>
            <button
                type="button"
                wire:click="setEnvironment('outdoor')"
                @class([
                    'rounded-full px-4 py-2 text-sm font-semibold transition',
                    $environment === 'outdoor'
                        ? 'bg-emerald-600 text-white shadow-sm dark:bg-emerald-500'
                        : 'border border-zinc-200 bg-white text-zinc-700 hover:border-emerald-300 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-emerald-800',
                ])
            >
                Outdoor
            </button>
            <button
                type="button"
                wire:click="setEnvironment('indoor')"
                @class([
                    'rounded-full px-4 py-2 text-sm font-semibold transition',
                    $environment === 'indoor'
                        ? 'bg-emerald-600 text-white shadow-sm dark:bg-emerald-500'
                        : 'border border-zinc-200 bg-white text-zinc-700 hover:border-emerald-300 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-emerald-800',
                ])
            >
                Indoor
            </button>
            @foreach ($this->cityPills() as $cityName)
                <button
                    type="button"
                    wire:click="setCity(@js($cityName))"
                    @class([
                        'rounded-full px-4 py-2 text-sm font-semibold transition',
                        $city === $cityName
                            ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900'
                            : 'border border-zinc-200 bg-white text-zinc-700 hover:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200',
                    ])
                >
                    {{ $cityName }}
                </button>
            @endforeach
            @if ($city !== null)
                <button
                    type="button"
                    wire:click="setCity(null)"
                    class="rounded-full px-4 py-2 text-sm font-semibold text-zinc-500 underline decoration-zinc-300 underline-offset-2 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200"
                >
                    Clear city
                </button>
            @endif
        </div>
    </div>

    @php($featuredVenues = $this->featuredVenueClients())
    @if ($featuredVenues->isNotEmpty())
        <section class="mt-12" aria-labelledby="featured-venues-heading">
            <h2 id="featured-venues-heading" class="font-display text-lg font-bold text-zinc-900 dark:text-white">
                Featured
                <span class="font-medium text-zinc-500 dark:text-zinc-400">· {{ $this->effectiveCityForFeatured() }}</span>
            </h2>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                Hand-picked partner venues in your area — use arrows or swipe to explore.
            </p>
            <div
                class="featured-venues-splide splide mt-4 pb-8"
                data-featured-venues-slider
                aria-label="Featured venues carousel"
            >
                <div class="splide__track">
                    <ul class="splide__list">
                        @foreach ($featuredVenues as $venue)
                            <li class="splide__slide" wire:key="featured-venue-{{ $venue->id }}">
                                <article
                                    class="flex h-full flex-col overflow-hidden rounded-2xl border border-amber-200/90 bg-gradient-to-b from-amber-50/90 to-white shadow-sm dark:border-amber-900/40 dark:from-amber-950/40 dark:to-zinc-900"
                                >
                                    <div class="bg-zinc-100 dark:bg-zinc-800">
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
                                    <div class="flex flex-1 flex-col p-4">
                                        <p class="text-[11px] font-bold uppercase tracking-wider text-amber-800 dark:text-amber-300/90">
                                            Featured venue
                                        </p>
                                        <h3 class="mt-1 font-display text-base font-bold text-zinc-900 dark:text-white">
                                            {{ $venue->name }}
                                        </h3>
                                        @if ($venue->public_rating_average !== null)
                                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                                                {{ number_format((float) $venue->public_rating_average, 1) }}★ guest rating
                                                @if ($venue->public_rating_count > 0)
                                                    <span class="text-zinc-500">({{ $venue->public_rating_count }})</span>
                                                @endif
                                            </p>
                                        @endif
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            <a
                                                href="{{ $this->venueBookUrl($venue) }}"
                                                wire:navigate
                                                class="inline-flex flex-1 items-center justify-center rounded-xl bg-emerald-600 px-3 py-2 text-sm font-bold text-white shadow-sm hover:bg-emerald-700 dark:bg-emerald-600 dark:hover:bg-emerald-500"
                                            >
                                                Book
                                            </a>
                                            <a
                                                href="{{ $this->venueBookUrl($venue) }}#venue-reviews"
                                                wire:navigate
                                                class="inline-flex items-center justify-center rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm font-semibold text-zinc-800 hover:border-emerald-300 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-200"
                                            >
                                                Reviews
                                            </a>
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
        <section class="mt-12" aria-labelledby="recently-viewed-heading">
            <div class="flex items-baseline justify-between gap-4">
                <h2
                    id="recently-viewed-heading"
                    class="font-display text-lg font-bold text-zinc-900 dark:text-white"
                >
                    Recently viewed
                </h2>
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
        <section class="mt-12" aria-labelledby="nearby-heading">
            <h2 id="nearby-heading" class="font-display text-lg font-bold text-zinc-900 dark:text-white">
                Nearby
                <span class="font-medium text-zinc-500 dark:text-zinc-400">· {{ $this->nearbyCity() }}</span>
            </h2>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                Courts in your default area. Tap a city pill above to change this region.
            </p>
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
        <section class="mt-12" aria-labelledby="rated-heading">
            <h2 id="rated-heading" class="font-display text-lg font-bold text-zinc-900 dark:text-white">
                Top rated venues
            </h2>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Highest guest ratings across partner clubs.</p>
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

    @php($allVenueRows = $this->browseVenueRows())
    <section class="mt-12" aria-labelledby="all-venues-heading">
        <h2 id="all-venues-heading" class="font-display text-lg font-bold text-zinc-900 dark:text-white">
            All venues
        </h2>
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
            @if ($allVenueRows->isEmpty())
                Adjust filters above to find partner clubs with bookable courts.
            @else
                {{ $allVenueRows->count() }}
                {{ \Illuminate\Support\Str::plural('venue', $allVenueRows->count()) }}
                ·
                {{ $this->filteredCourts()->count() }}
                {{ \Illuminate\Support\Str::plural('court', $this->filteredCourts()->count()) }}
                match your filters.
            @endif
        </p>
        @if ($allVenueRows->isEmpty())
            <p class="mt-8 rounded-2xl border border-dashed border-zinc-300 py-16 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                No venues match these filters. Try clearing the city or switching indoor / outdoor.
            </p>
        @else
            <ul
                class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
                role="list"
            >
                @foreach ($allVenueRows as $row)
                    @php($venue = $row['venue'])
                    <li
                        wire:key="all-venues-{{ $venue->id }}"
                        class="flex flex-col overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
                    >
                        <div class="bg-zinc-100 dark:bg-zinc-800">
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
                                    <span class="font-display text-3xl font-extrabold text-white/90">
                                        {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($venue->name, 0, 2)) }}
                                    </span>
                                </div>
                            </x-image-carousel>
                        </div>
                        <div class="flex flex-1 flex-col p-5">
                            <p class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                Venue
                            </p>
                            <h3 class="mt-1 font-display text-lg font-bold text-zinc-900 dark:text-white">
                                {{ $venue->name }}
                            </h3>
                            @if ($venue->city)
                                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $venue->city }}</p>
                            @endif
                            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                                {{ $row['court_count'] }} {{ \Illuminate\Support\Str::plural('court', $row['court_count']) }}
                                match filters
                            </p>
                            <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
                                <a
                                    href="{{ $this->venueBookUrl($venue) }}"
                                    wire:navigate
                                    class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-emerald-700 dark:bg-emerald-600 dark:hover:bg-emerald-500"
                                >
                                    Pick a time
                                </a>
                                <a
                                    href="{{ $this->venueBookUrl($venue) }}#venue-reviews"
                                    wire:navigate
                                    class="inline-flex items-center justify-center rounded-xl border border-zinc-200 bg-white px-4 py-2.5 text-sm font-semibold text-zinc-800 hover:border-emerald-300 hover:text-emerald-800 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-emerald-700 dark:hover:text-emerald-300"
                                >
                                    Read reviews
                                </a>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</div>
