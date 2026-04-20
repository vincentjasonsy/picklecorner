@php($allVenueRows = $this->browseVenueRows())
@php($featuredVenues = $this->featuredVenueClients())

<div class="mx-auto max-w-6xl px-4 pb-14 pt-5 sm:px-6 lg:px-8 lg:pb-16 lg:pt-6">
    {{-- Minimal page label --}}
    <p class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Book now</p>

    {{-- Search & filters --}}
    <div class="mt-6 space-y-4" aria-label="Search and filters">
        <div class="relative max-w-lg">
            <label for="book-now-search" class="sr-only">Search venues</label>
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-zinc-400 dark:text-zinc-500" aria-hidden="true">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </span>
            <input
                id="book-now-search"
                type="search"
                wire:model.live.debounce.300ms="search"
                autocomplete="off"
                placeholder="Search venue, court, city…"
                class="w-full rounded-lg border border-zinc-200 bg-white py-2.5 pl-9 pr-3 text-sm text-zinc-900 placeholder:text-zinc-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white dark:placeholder:text-zinc-500 dark:focus:border-emerald-400"
            />
        </div>

        @if ($this->cityPills()->isNotEmpty())
            <div class="flex flex-wrap items-center gap-1.5">
                @foreach ($this->cityPills() as $cityName)
                    <button
                        type="button"
                        wire:click="setCity(@js($cityName))"
                        @class([
                            'rounded-md px-2.5 py-1 text-xs font-medium transition',
                            $city === $cityName
                                ? 'bg-emerald-600 text-white dark:bg-emerald-500'
                                : 'border border-zinc-200 bg-white text-zinc-700 hover:border-zinc-300 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:border-zinc-600',
                        ])
                    >
                        {{ $cityName }}
                    </button>
                @endforeach
                @if ($city !== null)
                    <button
                        type="button"
                        wire:click="setCity(null)"
                        class="px-2 py-1 text-xs font-medium text-zinc-500 underline-offset-2 hover:text-zinc-800 hover:underline dark:text-zinc-400 dark:hover:text-zinc-200"
                    >
                        Clear
                    </button>
                @endif
            </div>
        @endif

        <div class="relative overflow-hidden rounded-2xl border border-emerald-200/70 bg-gradient-to-br from-emerald-50/95 via-white to-teal-50/90 shadow-[0_12px_40px_-16px_rgba(16,185,129,0.35)] ring-1 ring-emerald-400/15 dark:border-emerald-800/55 dark:from-emerald-950/50 dark:via-zinc-900 dark:to-teal-950/45 dark:shadow-emerald-950/30 dark:ring-emerald-500/20">
            <div class="pointer-events-none absolute -right-16 -top-12 size-40 rounded-full bg-teal-400/25 blur-3xl dark:bg-teal-500/15" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -bottom-10 -left-10 size-36 rounded-full bg-violet-400/20 blur-3xl dark:bg-violet-600/15" aria-hidden="true"></div>

            <div class="relative flex flex-col gap-4 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:gap-6">
                <div class="flex min-w-0 flex-1 gap-4">
                    <span class="relative flex size-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-500 via-teal-500 to-cyan-500 text-white shadow-lg shadow-emerald-600/35 ring-2 ring-white/40 dark:shadow-emerald-950/50 dark:ring-emerald-400/30" aria-hidden="true">
                        <svg class="size-7" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-display text-lg font-bold tracking-tight text-emerald-950 dark:text-emerald-50">
                                Match my schedule
                            </span>
                            @if ($slotFilterEnabled)
                                <span class="inline-flex items-center rounded-full bg-emerald-600 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-white shadow-sm dark:bg-emerald-500">
                                    On
                                </span>
                            @endif
                        </div>
                        <p class="mt-0.5 text-sm text-emerald-900/75 dark:text-emerald-200/80">
                            Only show clubs with real open court time for the day and window you pick.
                        </p>
                        @if ($slotFilterEnabled)
                            <p class="mt-2 text-xs font-medium text-emerald-800/90 dark:text-emerald-200/90">
                                {{ $this->slotFilterSummary() }}
                            </p>
                        @endif
                    </div>
                </div>
                <div class="flex shrink-0 flex-wrap items-center gap-2 sm:justify-end">
                    @if ($slotFilterEnabled)
                        <button
                            type="button"
                            wire:click="clearSlotFilter"
                            class="rounded-xl border border-emerald-200/90 bg-white/90 px-4 py-2.5 text-xs font-bold text-emerald-900 shadow-sm transition hover:bg-emerald-50 dark:border-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-100 dark:hover:bg-emerald-950/80"
                        >
                            Clear
                        </button>
                    @endif
                    <button
                        type="button"
                        wire:click="openSlotFilterModal"
                        class="rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 px-5 py-2.5 text-xs font-bold uppercase tracking-wide text-white shadow-lg shadow-emerald-900/25 ring-2 ring-white/25 transition hover:from-emerald-500 hover:to-teal-500 dark:from-emerald-500 dark:to-teal-500 dark:shadow-emerald-950/40 dark:ring-emerald-400/20"
                    >
                        {{ $slotFilterEnabled ? 'Edit' : 'Set schedule' }}
                    </button>
                </div>
            </div>
        </div>

        @if ($slotFilterModalOpen)
            <div
                class="fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4"
                wire:click="closeSlotFilterModal"
                role="presentation"
            >
                <div class="absolute inset-0 z-0 bg-zinc-900/60 backdrop-blur-[2px]" aria-hidden="true"></div>
                <div
                    class="relative z-10 flex max-h-[min(92vh,640px)] w-full max-w-lg flex-col rounded-t-[1.75rem] border border-emerald-200/80 bg-white shadow-2xl dark:border-emerald-800/60 dark:bg-zinc-900 sm:rounded-[1.75rem]"
                    wire:click.stop
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="slot-filter-modal-title"
                >
                    <div class="flex items-start justify-between gap-3 border-b border-emerald-100 px-5 py-4 dark:border-emerald-900/50">
                        <div>
                            <h2 id="slot-filter-modal-title" class="font-display text-xl font-extrabold text-zinc-900 dark:text-white">
                                Match my schedule
                            </h2>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                                We check closures, blocks, and bookings — not just “venue exists.”
                            </p>
                        </div>
                        <button
                            type="button"
                            class="rounded-2xl p-2 text-zinc-500 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800"
                            wire:click="closeSlotFilterModal"
                            aria-label="Close"
                        >
                            ✕
                        </button>
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto px-5 py-4">
                        <div class="space-y-6">
                            <div>
                                <p class="flex items-center gap-2 font-display text-sm font-bold text-zinc-900 dark:text-white">
                                    <span class="flex size-7 items-center justify-center rounded-lg bg-teal-100 text-teal-700 dark:bg-teal-950 dark:text-teal-300">1</span>
                                    Which day?
                                </p>
                                <input
                                    type="date"
                                    wire:model.live="filterDate"
                                    class="mt-2 w-full rounded-xl border-2 border-dashed border-teal-300/80 bg-white px-3 py-2.5 text-sm font-semibold text-zinc-900 transition hover:border-teal-400 focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-500/20 dark:border-teal-700 dark:bg-zinc-950 dark:text-white dark:focus:border-emerald-400"
                                />
                            </div>

                            <div>
                                <p class="flex items-center gap-2 font-display text-sm font-bold text-zinc-900 dark:text-white">
                                    <span class="flex size-7 items-center justify-center rounded-lg bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-300">2</span>
                                    How long in one stretch?
                                </p>
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Contiguous hours inside your time window.</p>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach ([1, 2, 3, 4] as $mh)
                                        <button
                                            type="button"
                                            wire:click="$set('filterMinHours', {{ $mh }})"
                                            @class([
                                                'rounded-full px-4 py-2 text-xs font-bold transition',
                                                (int) $filterMinHours === (int) $mh
                                                    ? 'bg-gradient-to-r from-violet-600 to-fuchsia-600 text-white shadow-md shadow-violet-900/25 ring-2 ring-white/30 dark:from-violet-500 dark:to-fuchsia-600'
                                                    : 'border border-violet-200 bg-white text-violet-900 hover:border-violet-400 hover:bg-violet-50 dark:border-violet-800 dark:bg-violet-950/40 dark:text-violet-100 dark:hover:bg-violet-950/70',
                                            ])
                                        >
                                            {{ $mh }} {{ \Illuminate\Support\Str::plural('hr', $mh) }}
                                        </button>
                                    @endforeach
                                    <select
                                        wire:model.live="filterMinHours"
                                        class="rounded-full border border-zinc-200 bg-white px-3 py-2 text-xs font-semibold text-zinc-800 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-200"
                                        aria-label="More hour options"
                                    >
                                        @for ($h = 1; $h <= 12; $h++)
                                            <option value="{{ $h }}">{{ $h }} {{ \Illuminate\Support\Str::plural('hr', $h) }}</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>

                            <div>
                                <p class="flex items-center gap-2 font-display text-sm font-bold text-zinc-900 dark:text-white">
                                    <span class="flex size-7 items-center justify-center rounded-lg bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200">3</span>
                                    Between what times?
                                </p>
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Your free window — court starts must fall inside it.</p>
                                <div class="mt-2 flex flex-col gap-3 sm:flex-row sm:items-end">
                                    <div class="min-w-0 flex-1">
                                        <span class="text-[11px] font-bold uppercase tracking-wide text-teal-700 dark:text-teal-300/90">From</span>
                                        <select
                                            wire:model.live="filterWindowStart"
                                            class="mt-1 w-full rounded-xl border border-teal-200 bg-white px-3 py-2.5 text-sm font-semibold tabular-nums text-zinc-900 shadow-sm dark:border-teal-800 dark:bg-zinc-950 dark:text-white"
                                        >
                                            @for ($h = 0; $h < 24; $h++)
                                                <option value="{{ $h }}">{{ \Carbon\Carbon::parse(sprintf('2000-01-01 %02d:00:00', $h))->format('g:i A') }}</option>
                                            @endfor
                                        </select>
                                    </div>
                                    <span class="hidden pb-2 text-lg font-black text-teal-400 sm:block" aria-hidden="true">→</span>
                                    <div class="min-w-0 flex-1">
                                        <span class="text-[11px] font-bold uppercase tracking-wide text-teal-700 dark:text-teal-300/90">Until (cutoff)</span>
                                        <select
                                            wire:model.live="filterWindowEnd"
                                            class="mt-1 w-full rounded-xl border border-teal-200 bg-white px-3 py-2.5 text-sm font-semibold tabular-nums text-zinc-900 shadow-sm dark:border-teal-800 dark:bg-zinc-950 dark:text-white"
                                        >
                                            @for ($h = 1; $h <= 24; $h++)
                                                <option value="{{ $h }}">
                                                    @if ($h === 24)
                                                        Midnight — end of window
                                                    @else
                                                        {{ \Carbon\Carbon::parse(sprintf('2000-01-01 %02d:00:00', $h))->format('g:i A') }}
                                                    @endif
                                                </option>
                                            @endfor
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <p class="rounded-xl bg-gradient-to-r from-emerald-600/10 to-teal-600/10 px-3 py-2 text-xs font-medium text-emerald-950 dark:from-emerald-400/10 dark:to-teal-500/10 dark:text-emerald-100">
                                “Until” stops the window (exclusive). Example: cutoff 10:00 PM → last start hour is 9.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-col-reverse gap-2 border-t border-zinc-200 px-5 py-4 dark:border-zinc-700 sm:flex-row sm:justify-end">
                        <button
                            type="button"
                            wire:click="closeSlotFilterModal"
                            class="w-full rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm font-bold text-zinc-800 transition hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800 sm:w-auto"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            wire:click="applySlotFilter"
                            class="w-full rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-900/20 transition hover:from-emerald-500 hover:to-teal-500 dark:shadow-emerald-950/40 sm:w-auto"
                        >
                            Apply filter
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Indoor / outdoor — above venue list --}}
    <div class="mt-10 flex justify-center px-2" aria-label="Court surface">
        <div class="flex flex-wrap items-center justify-center gap-3 sm:gap-4">
            <button
                type="button"
                wire:click="setEnvironment('all')"
                @class([
                    'rounded-full px-6 py-3 text-base font-bold tracking-tight transition sm:text-lg sm:py-3.5',
                    $environment === 'all'
                        ? 'bg-zinc-900 text-white shadow-md shadow-zinc-900/20 ring-2 ring-emerald-500/40 dark:bg-white dark:text-zinc-900 dark:shadow-none dark:ring-emerald-400/50'
                        : 'border border-zinc-200 bg-white text-zinc-700 hover:border-emerald-300 hover:bg-emerald-50 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-emerald-700 dark:hover:bg-emerald-950/40',
                ])
            >
                All
            </button>
            <button
                type="button"
                wire:click="setEnvironment('outdoor')"
                @class([
                    'rounded-full px-6 py-3 text-base font-bold tracking-tight transition sm:text-lg sm:py-3.5',
                    $environment === 'outdoor'
                        ? 'bg-zinc-900 text-white shadow-md shadow-zinc-900/20 ring-2 ring-emerald-500/40 dark:bg-white dark:text-zinc-900 dark:shadow-none dark:ring-emerald-400/50'
                        : 'border border-zinc-200 bg-white text-zinc-700 hover:border-emerald-300 hover:bg-emerald-50 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-emerald-700 dark:hover:bg-emerald-950/40',
                ])
            >
                Outdoor
            </button>
            <button
                type="button"
                wire:click="setEnvironment('indoor')"
                @class([
                    'rounded-full px-6 py-3 text-base font-bold tracking-tight transition sm:text-lg sm:py-3.5',
                    $environment === 'indoor'
                        ? 'bg-zinc-900 text-white shadow-md shadow-zinc-900/20 ring-2 ring-emerald-500/40 dark:bg-white dark:text-zinc-900 dark:shadow-none dark:ring-emerald-400/50'
                        : 'border border-zinc-200 bg-white text-zinc-700 hover:border-emerald-300 hover:bg-emerald-50 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-emerald-700 dark:hover:bg-emerald-950/40',
                ])
            >
                Indoor
            </button>
        </div>
    </div>

    {{-- Primary: venues & courts --}}
    <section class="mt-6 scroll-mt-20 sm:mt-8" aria-labelledby="venues-heading" id="venues">
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
                    @elseif ($slotFilterEnabled)
                        Try another day, widen the time window, lower minimum hours, or clear the schedule filter above.
                    @else
                        Clear the city filter or switch indoor / outdoor to see more clubs.
                    @endif
                </p>
            </div>
        @else
            <ul class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3" role="list">
                @foreach ($allVenueRows as $row)
                    @php($venue = $row['venue'])
                    @php($venueOpeningSoon = $venue->isOpeningSoonVenue())
                    <li
                        wire:key="all-venues-{{ $venue->id }}"
                        @class([
                            'group relative flex flex-col overflow-hidden rounded-2xl border bg-white shadow-md shadow-zinc-900/5 ring-1 ring-black/[0.03] transition dark:bg-zinc-900',
                            'border-zinc-300/95 bg-zinc-100 shadow-xl shadow-zinc-900/25 ring-zinc-900/[0.08] saturate-[0.92] dark:border-zinc-600 dark:bg-zinc-950 dark:shadow-black/45 dark:ring-white/[0.06]' => $venueOpeningSoon,
                            'border-zinc-200/90 shadow-zinc-900/5 ring-black/[0.03] hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-lg hover:shadow-emerald-900/10 dark:border-zinc-700 dark:ring-white/[0.04] dark:hover:border-emerald-700' => ! $venueOpeningSoon,
                        ])
                    >
                        @if ($venueOpeningSoon)
                            <span
                                class="pointer-events-none absolute inset-0 z-[4] rounded-2xl bg-gradient-to-b from-zinc-950/15 to-zinc-950/30 dark:from-black/35 dark:to-black/50"
                                aria-hidden="true"
                            ></span>
                            <span
                                class="absolute inset-0 z-[1] cursor-not-allowed rounded-2xl ring-1 ring-inset ring-zinc-900/[0.12] dark:ring-white/[0.08]"
                                role="presentation"
                                aria-hidden="true"
                            ></span>
                        @else
                            <a
                                href="{{ $this->venueBookUrl($venue) }}"
                                wire:navigate
                                class="absolute inset-0 z-[1] rounded-2xl focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 dark:focus-visible:ring-emerald-400 dark:ring-offset-zinc-900"
                                aria-label="Book {{ $venue->name }}"
                            ></a>
                        @endif
                        <div class="relative z-[2] flex flex-col pointer-events-none">
                            <div class="relative bg-zinc-100 dark:bg-zinc-800">
                                @if ($venueOpeningSoon)
                                    <div
                                        class="pointer-events-none absolute right-3 top-3 z-[6] rounded-full bg-zinc-950 px-3 py-1.5 text-[11px] font-bold uppercase tracking-wide text-white shadow-lg shadow-black/40 ring-1 ring-white/15 backdrop-blur-sm"
                                    >
                                        Coming soon
                                    </div>
                                @endif
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
                            <div class="flex flex-1 flex-col p-5">
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
                                    @if ($venueOpeningSoon)
                                        <span class="font-normal text-zinc-500 dark:text-zinc-400">· Coming soon</span>
                                    @else
                                        <span class="font-normal text-zinc-500 dark:text-zinc-400">· Tap card to book</span>
                                    @endif
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
                Featured picks and shortcuts{{ public_reviews_enabled() ? ', and ratings' : '' }} — optional extras after you’ve scanned the full list above.
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
                                            <div class="relative z-[2] flex flex-1 flex-col pointer-events-none">
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
                                                    <h4 class="mt-1 font-display text-base font-bold text-zinc-900 dark:text-white">
                                                        {{ $venue->name }}
                                                    </h4>
                                                    @if (public_reviews_enabled() && $venue->public_rating_average !== null)
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
                            <x-app-icon name="{{ public_reviews_enabled() ? 'star-solid' : 'building-office-2' }}" class="size-4" />
                        </span>
                        <div>
                            <h3 id="rated-heading" class="font-display text-base font-bold text-zinc-900 dark:text-white">
                                {{ public_reviews_enabled() ? 'Top rated venues' : 'More courts' }}
                            </h3>
                            <p class="mt-0.5 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ public_reviews_enabled() ? 'Highest guest ratings.' : 'Explore more bookable courts.' }}
                            </p>
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
