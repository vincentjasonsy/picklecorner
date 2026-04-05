<div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
    <header class="max-w-2xl">
        <h1 class="font-display text-3xl font-extrabold tracking-tight text-zinc-900 dark:text-white sm:text-4xl">
            Book now
        </h1>
        <p class="mt-3 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
            Explore courts across our partner venues — filter by location or surface, check ratings, and pick up where
            you left off with recently viewed spots.
        </p>
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

    <section class="mt-12" aria-labelledby="all-heading">
        <h2 id="all-heading" class="font-display text-lg font-bold text-zinc-900 dark:text-white">
            All courts
        </h2>
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
            {{ $this->filteredCourts()->count() }}
            {{ \Illuminate\Support\Str::plural('court', $this->filteredCourts()->count()) }} match your filters.
        </p>
        @if ($this->filteredCourts()->isEmpty())
            <p class="mt-8 rounded-2xl border border-dashed border-zinc-300 py-16 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                No courts match these filters. Try clearing the city or switching indoor / outdoor.
            </p>
        @else
            <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($this->filteredCourts() as $c)
                    <x-court-browse-card :court="$c" />
                @endforeach
            </div>
        @endif
    </section>
</div>
