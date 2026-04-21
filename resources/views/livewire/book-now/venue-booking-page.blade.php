@php
    use App\Models\Court;
    use App\Support\Money;

    $paymongoConfigured = config('paymongo.enabled') && (string) config('paymongo.secret_key') !== '';

    $courts = $this->courtsOrderedForGrid();
    $slotHours = $this->slotHoursForSelectedDate();
    $dow = $this->bookingDayOfWeek();
    $dateBlocks = $this->dateBlockLookup;
    $occupancy = $this->occupancyBySlot;
    $currency = $courtClient->currency ?? 'PHP';
    $venueSlides = $courtClient->carouselSlides();
    $minRate = $courts
        ->map(fn (Court $c) => $c->effectiveHourlyRateCents())
        ->filter()
        ->min();
@endphp

<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    @if (session('paymongo_checkout'))
        @php
            $pm = session('paymongo_checkout');
        @endphp
        <div
            class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-900/50 dark:bg-amber-950/35 dark:text-amber-100"
            role="alert"
        >
            <p class="font-display font-semibold text-amber-950 dark:text-amber-50">{{ $pm['title'] ?? 'Checkout update' }}</p>
            <p class="mt-1 leading-relaxed text-amber-900/95 dark:text-amber-100/90">{{ $pm['body'] ?? '' }}</p>
            @if (! empty($pm['amount_label']))
                <dl class="mt-3 flex flex-wrap gap-x-6 gap-y-1 text-xs text-amber-900/90 dark:text-amber-200/90">
                    <div>
                        <dt class="font-semibold uppercase tracking-wide text-amber-800/90 dark:text-amber-300/80">Checkout amount</dt>
                        <dd class="mt-0.5 font-medium tabular-nums">{{ $pm['amount_label'] }}</dd>
                    </div>
                    @if (! empty($pm['date_label']))
                        <div>
                            <dt class="font-semibold uppercase tracking-wide text-amber-800/90 dark:text-amber-300/80">Booking date</dt>
                            <dd class="mt-0.5 font-medium">{{ $pm['date_label'] }}</dd>
                        </div>
                    @endif
                </dl>
            @endif
        </div>
    @endif
    @if (session('status'))
        <div
            class="mb-6 rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm font-medium text-teal-950 dark:border-teal-900/50 dark:bg-teal-950/40 dark:text-teal-100"
            role="status"
        >
            {{ session('status') }}
        </div>
    @endif

    <div class="flex flex-col gap-10 lg:flex-row lg:items-start">
        {{-- Venue profile card --}}
        <aside class="w-full shrink-0 lg:sticky lg:top-24 lg:w-80 xl:w-96">
            <div
                class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
            >
                <div class="bg-zinc-100 dark:bg-zinc-800">
                    <x-image-carousel
                        :slides="$venueSlides"
                        :interval="6000"
                        aria-label="Venue photos"
                        class="w-full"
                    >
                        <div
                            class="relative aspect-[4/3] flex items-center justify-center bg-gradient-to-br from-emerald-500 to-teal-800"
                            aria-hidden="true"
                        >
                            <span class="font-display text-3xl font-extrabold text-white/90">
                                {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($courtClient->name, 0, 2)) }}
                            </span>
                        </div>
                    </x-image-carousel>
                </div>
                <div class="space-y-3 p-5">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Partner venue
                        </p>
                        <h1 class="mt-1 font-display text-2xl font-bold text-zinc-900 dark:text-white">
                            {{ $courtClient->name }}
                        </h1>
                        @if ($courtClient->city)
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $courtClient->city }}</p>
                        @endif
                    </div>
                    @if (public_reviews_enabled() && $courtClient->public_rating_average !== null)
                        <p class="inline-flex flex-wrap items-center gap-1 text-sm font-semibold text-zinc-800 dark:text-zinc-200">
                            <x-app-icon name="star-solid" class="size-4 text-amber-500 dark:text-amber-400" />
                            {{ number_format((float) $courtClient->public_rating_average, 1) }}
                            @if ($courtClient->public_rating_count > 0)
                                <span class="font-normal text-zinc-500 dark:text-zinc-400">
                                    ({{ number_format($courtClient->public_rating_count) }} reviews)
                                </span>
                            @endif
                        </p>
                    @endif
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">
                        <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $courts->count() }}</span>
                        {{ \Illuminate\Support\Str::plural('court', $courts->count()) }}
                        @if ($this->venueIsOpeningSoon())
                            at this venue
                        @else
                            available to book
                        @endif
                    </p>
                    @if ($minRate !== null)
                        <p class="text-sm font-bold text-emerald-700 dark:text-emerald-400">
                            From {{ Money::formatMinor($minRate, $currency) }}
                            <span class="font-medium text-zinc-500 dark:text-zinc-400">/ hr</span>
                            <span class="block text-xs font-normal text-zinc-500 dark:text-zinc-400">
                                Final price depends on court and time.
                            </span>
                        </p>
                    @endif
                    <a
                        href="{{ $this->backUrl() }}"
                        wire:navigate
                        class="inline-flex text-sm font-semibold text-emerald-700 hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-300"
                    >
                        ← Back to browse
                    </a>
                </div>
            </div>
        </aside>

        {{-- Booking panel --}}
        <div
            @class([
                'min-w-0 flex-1 space-y-6',
                'max-lg:pb-28 lg:pb-40' => $step === 'times'
                    || (
                        $step === 'review'
                        && auth()->check()
                        && $this->canSubmitBookings()
                        && ($paymongoConfigured || $this->reviewBalanceAfterGiftCents <= 0)
                    ),
            ])
        >
            <div class="flex flex-wrap items-center gap-2 text-sm">
                <span
                    @class([
                        'rounded-full px-3 py-1 font-semibold',
                        $step === 'times'
                            ? 'bg-teal-600 text-white dark:bg-teal-600'
                            : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400',
                    ])
                >
                    1. Pick times
                </span>
                <span class="text-zinc-400">→</span>
                <span
                    @class([
                        'rounded-full px-3 py-1 font-semibold',
                        $step === 'review'
                            ? 'bg-teal-600 text-white dark:bg-teal-600'
                            : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400',
                    ])
                >
                    2. Review &amp; submit
                </span>
            </div>

            @if ($step === 'times')
                @if ($this->venueIsOpeningSoon())
                    <div
                        class="relative overflow-hidden rounded-2xl border border-zinc-400/60 bg-gradient-to-br from-zinc-200 via-zinc-100 to-zinc-50 px-6 py-16 text-center shadow-[0_12px_40px_-8px_rgba(0,0,0,0.22)] ring-1 ring-zinc-950/10 dark:border-zinc-600 dark:from-zinc-950 dark:via-zinc-950 dark:to-black dark:shadow-[0_16px_48px_-12px_rgba(0,0,0,0.65)] dark:ring-white/10"
                    >
                        <div
                            class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top,_rgba(0,0,0,0.06),_transparent_55%)] dark:bg-[radial-gradient(ellipse_at_top,_rgba(0,0,0,0.35),_transparent_55%)]"
                            aria-hidden="true"
                        ></div>
                        <p
                            class="pointer-events-none absolute left-1/2 top-[42%] z-10 -translate-x-1/2 -translate-y-1/2 whitespace-nowrap rounded-full border border-white/15 bg-zinc-950 px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-white shadow-2xl shadow-black/45 ring-2 ring-black/20 backdrop-blur-sm dark:border-white/10 dark:ring-white/15"
                        >
                            Coming soon
                        </p>
                        <p class="relative z-[1] mx-auto mt-28 max-w-md text-sm leading-relaxed text-zinc-700 dark:text-zinc-400">
                            Public booking isn’t open yet for this venue. Browse other clubs from Book now, or check back when we go
                            live.
                        </p>
                        <a
                            href="{{ $this->backUrl() }}"
                            wire:navigate
                            class="relative z-[1] mt-8 inline-flex items-center justify-center rounded-xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-700 dark:bg-emerald-600 dark:hover:bg-emerald-500"
                        >
                            ← Back to browse
                        </a>
                    </div>
                @else
                <div class="space-y-6">
                    <div>
                        <h2 class="font-display text-xl font-bold text-zinc-900 dark:text-white">
                            Choose date &amp; courts
                        </h2>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                            Tap open cells to select one or more hours. On each court, selections must be in a single
                            continuous block (gaps create separate bookings).
                        </p>
                    </div>

                    <div
                        class="overflow-hidden rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900"
                    >
                        <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Date
                        </p>
                        <div
                            class="mt-3 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:gap-4"
                        >
                            <div class="flex flex-wrap items-center gap-2">
                                <button
                                    type="button"
                                    wire:click="shiftBookingDate(-1)"
                                    aria-label="Previous day"
                                    class="inline-flex items-center justify-center rounded-lg border border-zinc-200 px-2 py-2 text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                >
                                    <span class="sr-only">Previous day</span>
                                    <svg class="size-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                                    </svg>
                                </button>
                                <input
                                    type="date"
                                    wire:model.live="bookingCalendarDate"
                                    min="{{ $this->minBookableCalendarDate() }}"
                                    class="rounded-lg border border-zinc-200 px-3 py-2 text-sm text-zinc-800 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                                />
                                <button
                                    type="button"
                                    wire:click="shiftBookingDate(1)"
                                    aria-label="Next day"
                                    class="inline-flex items-center justify-center rounded-lg border border-zinc-200 px-2 py-2 text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                >
                                    <span class="sr-only">Next day</span>
                                    <svg class="size-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                    </svg>
                                </button>
                            </div>
                            <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                {{ $this->bookingCalendarDateLabel() }}
                            </p>
                        </div>
                        @error('bookingCalendarDate')
                            <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    @if ($courts->isEmpty())
                        <p class="rounded-xl border border-dashed border-zinc-300 px-4 py-8 text-center text-sm text-zinc-500 dark:border-zinc-600 dark:text-zinc-400">
                            This venue does not have any courts open for public booking yet.
                        </p>
                    @elseif (count($slotHours) === 0)
                        <p
                            class="rounded-xl border border-dashed border-zinc-300 px-4 py-8 text-center text-sm text-zinc-500 dark:border-zinc-600 dark:text-zinc-400"
                        >
                            @if ($this->isBookingDateVenueClosure())
                                This venue is closed on this date (for example a holiday). Please pick another day.
                            @else
                                This date is closed. Try another day.
                            @endif
                        </p>
                    @else
                        <div
                            class="overflow-hidden rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900"
                        >
                            <div class="flex flex-wrap items-end justify-between gap-2">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                        Availability
                                    </p>
                                    <ul
                                        class="mt-2 flex list-none flex-wrap items-center gap-x-4 gap-y-2 p-0 text-xs text-zinc-600 dark:text-zinc-400"
                                        aria-label="Grid legend"
                                    >
                                        <li class="inline-flex items-center gap-1.5">
                                            <span
                                                class="size-3.5 shrink-0 rounded border border-emerald-200 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-950/40"
                                                aria-hidden="true"
                                            ></span>
                                            <span>Open</span>
                                        </li>
                                        <li class="inline-flex items-center gap-1.5">
                                            <span
                                                class="size-3.5 shrink-0 rounded border border-slate-300 bg-slate-200 dark:border-slate-600 dark:bg-slate-700"
                                                aria-hidden="true"
                                            ></span>
                                            <span>Booked</span>
                                        </li>
                                        <li class="inline-flex items-center gap-1.5">
                                            <span
                                                class="size-3.5 shrink-0 rounded border border-red-200 bg-red-50/90 dark:border-red-900/50 dark:bg-red-950/25"
                                                aria-hidden="true"
                                            ></span>
                                            <span>Blocked</span>
                                        </li>
                                        <li class="inline-flex items-center gap-1.5">
                                            <span
                                                class="size-3.5 shrink-0 rounded border border-teal-600 bg-teal-600 shadow-sm ring-2 ring-teal-400/40 dark:ring-teal-500/30"
                                                aria-hidden="true"
                                            ></span>
                                            <span>Your pick</span>
                                        </li>
                                    </ul>
                                </div>
                                @if (count($selectedSlots) > 0)
                                    <button
                                        type="button"
                                        wire:click="clearSlotSelection"
                                        class="text-xs font-semibold text-zinc-600 underline decoration-zinc-300 underline-offset-2 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white"
                                    >
                                        Clear all
                                    </button>
                                @endif
                            </div>
                            <div class="mt-4 overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-800">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full border-collapse text-left text-sm">
                                        <thead>
                                            <tr
                                                class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800/80"
                                            >
                                                <th
                                                    class="sticky left-0 z-10 bg-zinc-50 px-3 py-2 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:bg-zinc-800/80 dark:text-zinc-400"
                                                >
                                                    Time
                                                </th>
                                                @foreach ($courts as $court)
                                                    <th
                                                        class="min-w-[7.5rem] px-2 py-2 text-center text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-300"
                                                    >
                                                        <span class="line-clamp-3">{{ $court->name }}</span>
                                                    </th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                            @foreach ($slotHours as $hour)
                                                <tr wire:key="vb-grid-{{ $bookingCalendarDate }}-{{ $hour }}">
                                                    <td
                                                        class="sticky left-0 z-10 whitespace-nowrap bg-white px-3 py-2 text-xs font-medium text-zinc-600 dark:bg-zinc-900 dark:text-zinc-300"
                                                    >
                                                        {{ $this->slotHourLabel($hour) }}
                                                    </td>
                                                    @foreach ($courts as $court)
                                                        @php
                                                            $slotKey = $court->id.'-'.$hour;
                                                            $booked = $occupancy[$slotKey] ?? null;
                                                            $weeklyBlocked = $court->isWeeklySlotBlocked($dow, $hour);
                                                            $dateBlocked = isset($dateBlocks[$slotKey]);
                                                            $blocked = $weeklyBlocked || $dateBlocked;
                                                            $slotSelected = $this->isSlotSelected($court->id, $hour);
                                                            $isBookedCell = false;
                                                            $isBlockedCell = false;
                                                            if ($booked !== null) {
                                                                $availStyle =
                                                                    'cursor-default border-slate-300 bg-slate-200/95 text-slate-900 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100';
                                                                $cellTitle = 'Booked';
                                                                $isBookedCell = true;
                                                            } elseif ($blocked) {
                                                                $availStyle =
                                                                    'cursor-default border-red-200 bg-red-50/90 dark:border-red-900/50 dark:bg-red-950/25';
                                                                $cellTitle = 'Blocked';
                                                                $isBlockedCell = true;
                                                            } elseif ($slotSelected) {
                                                                $availStyle =
                                                                    'border-teal-600 bg-teal-600 text-white shadow-sm ring-2 ring-teal-400/40 dark:ring-teal-500/30';
                                                                $cellTitle = 'Selected';
                                                            } else {
                                                                $availStyle =
                                                                    'border-emerald-200 bg-emerald-50/50 dark:border-emerald-900/40 dark:bg-emerald-950/20';
                                                                $cellTitle = 'Open';
                                                            }
                                                            $slotPriceLabel = $this->slotHourPriceLabel($court, $hour);
                                                        @endphp
                                                        <td class="p-1.5 align-middle">
                                                            @if ($isBookedCell)
                                                                <div
                                                                    class="flex min-h-[3.25rem] w-full flex-col items-center justify-center gap-0.5 rounded-lg border px-1.5 py-1.5 text-center {{ $availStyle }}"
                                                                >
                                                                    <span class="text-[10px] font-bold uppercase tracking-wide">
                                                                        Booked
                                                                    </span>
                                                                    @if ($slotPriceLabel !== '')
                                                                        <span class="text-[9px] font-semibold tabular-nums text-slate-700 dark:text-slate-200">
                                                                            {{ $slotPriceLabel }}
                                                                        </span>
                                                                    @endif
                                                                </div>
                                                            @elseif ($isBlockedCell)
                                                                <div
                                                                    title="Not available for booking"
                                                                    class="flex min-h-[3.25rem] w-full flex-col items-center justify-center gap-0.5 rounded-lg border px-1.5 py-1.5 text-center {{ $availStyle }}"
                                                                >
                                                                    <span class="text-[10px] font-bold uppercase tracking-wide">
                                                                        Blocked
                                                                    </span>
                                                                    @if ($slotPriceLabel !== '')
                                                                        <span class="text-[9px] font-semibold tabular-nums text-red-800/90 dark:text-red-200/90">
                                                                            {{ $slotPriceLabel }}
                                                                        </span>
                                                                    @endif
                                                                </div>
                                                            @else
                                                                <button
                                                                    type="button"
                                                                    wire:click="toggleSlot('{{ $court->id }}', {{ $hour }})"
                                                                    @class([
                                                                        'group flex min-h-[3.25rem] w-full flex-col items-center justify-center gap-0.5 rounded-lg border px-2 py-2 text-center text-xs font-semibold transition-colors',
                                                                        $availStyle,
                                                                        'hover:border-teal-500/60 hover:bg-teal-50/80 dark:hover:bg-teal-950/30' => ! $slotSelected,
                                                                        'hover:border-emerald-600 hover:bg-emerald-50 hover:text-emerald-700 dark:hover:border-teal-500/50 dark:hover:bg-teal-950/40 dark:hover:text-white' => $slotSelected,
                                                                    ])
                                                                >
                                                                    <span
                                                                        @class([
                                                                            'text-[10px] font-bold uppercase leading-tight tracking-wide',
                                                                            'group-hover:text-emerald-700 dark:group-hover:text-white' => $slotSelected,
                                                                        ])
                                                                    >
                                                                        {{ $cellTitle }}
                                                                    </span>
                                                                    @if ($slotPriceLabel !== '')
                                                                        <span
                                                                            @class([
                                                                                'text-[9px] font-semibold leading-tight tabular-nums',
                                                                                'text-white/90 group-hover:text-emerald-700 dark:text-white/90 dark:group-hover:text-white/90' => $slotSelected,
                                                                                'text-zinc-600 dark:text-zinc-300' => ! $slotSelected,
                                                                            ])
                                                                        >
                                                                            {{ $slotPriceLabel }}
                                                                        </span>
                                                                    @endif
                                                                </button>
                                                            @endif
                                                        </td>
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            @error('selectedSlots')
                                <p class="mt-3 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    @if (config('booking.venue_checkout_show_coach') && ! $courts->isEmpty() && count($slotHours) > 0)
                        <div
                            class="overflow-hidden rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900"
                        >
                            <h3 class="font-display text-sm font-bold text-zinc-900 dark:text-white">
                                Coach (optional)
                            </h3>
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                Add a coach to the same request. You choose how many hours they’re paid for (up to your
                                selected slot hours). If you pick multiple courts (e.g. indoor and outdoor), the coach must
                                be linked to every court and available for each hour you selected.
                            </p>
                            @if (count($selectedSlots) === 0)
                                <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">
                                    Select at least one open slot on the grid above to see coaches available for these times.
                                </p>
                            @elseif ($this->availableCoachesForReview->isEmpty())
                                <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">
                                    No coaches are available for this venue and time yet. You can still submit a court-only
                                    request.
                                </p>
                            @else
                                <div class="mt-4 max-w-md space-y-4">
                                    <div>
                                        <label
                                            class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                                            for="venue-coach-select"
                                        >
                                            Coach
                                        </label>
                                        <select
                                            id="venue-coach-select"
                                            wire:model.live="coachUserId"
                                            class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                                        >
                                            <option value="">No coach — court only</option>
                                            @foreach ($this->availableCoachesForReview as $c)
                                                <option value="{{ $c->id }}">
                                                    {{ $c->name }}
                                                    @if ($c->coachProfile && $c->coachProfile->hourly_rate_cents > 0)
                                                        — coaching
                                                        {{ Money::formatMinor($c->coachProfile->hourly_rate_cents, $currency) }}/hr
                                                    @endif
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('coachUserId')
                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    @if ($coachUserId !== '')
                                        <div class="max-w-xs">
                                            <label
                                                class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                                                for="venue-coach-paid-hours"
                                            >
                                                Hours to pay the coach
                                            </label>
                                            <input
                                                id="venue-coach-paid-hours"
                                                type="number"
                                                wire:model.live="coachPaidHours"
                                                min="1"
                                                max="{{ $this->totalSelectedSlotHours() }}"
                                                class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm tabular-nums dark:border-zinc-700 dark:bg-zinc-950"
                                            />
                                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                                1–{{ $this->totalSelectedSlotHours() }} (your selected court hours).
                                            </p>
                                            @error('coachPaidHours')
                                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endif

                </div>

                @php
                    $timesCourtSub = $this->reviewCourtSubtotalCents;
                    $timesCoachFee = $this->reviewCoachFeeCents;
                    $timesBookingFee = $this->reviewBookingFeeCents;
                    $timesCheckoutTotal = $this->reviewCheckoutTotalCents;
                    $timesHasSlots = count($selectedSlots) > 0;
                @endphp

                {{-- Floating continue + estimate (all breakpoints; card on lg+) --}}
                <div
                    class="fixed inset-x-0 bottom-0 z-30 lg:inset-x-auto lg:bottom-6 lg:left-auto lg:right-6 lg:w-full lg:max-w-sm"
                    role="region"
                    aria-label="Booking estimate and continue"
                >
                    <div
                        class="border-t border-zinc-200/90 bg-white/95 px-4 pt-3 pb-[max(0.75rem,env(safe-area-inset-bottom,0px))] shadow-[0_-8px_30px_-10px_rgba(0,0,0,0.12)] backdrop-blur-md dark:border-zinc-700/90 dark:bg-zinc-950/95 dark:shadow-black/40 lg:rounded-2xl lg:border lg:px-4 lg:py-4 lg:shadow-2xl"
                    >
                        <div
                            class="mb-3"
                            aria-live="polite"
                            aria-atomic="true"
                        >
                            @if (! $timesHasSlots)
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                    Select open slots for a price estimate.
                                </p>
                            @else
                                <dl class="space-y-1.5 text-xs text-zinc-700 dark:text-zinc-300">
                                    <div class="flex justify-between gap-3">
                                        <dt>Courts subtotal</dt>
                                        <dd class="shrink-0 tabular-nums font-medium text-zinc-900 dark:text-white">
                                            {{ Money::formatMinor($timesCourtSub, $currency) }}
                                        </dd>
                                    </div>
                                    @if (config('booking.venue_checkout_show_coach') && $coachUserId !== '' && $this->coachPaidHours > 0)
                                        <div class="flex justify-between gap-3">
                                            <dt>
                                                Coach
                                                <span class="mt-0.5 block font-normal text-zinc-500 dark:text-zinc-400">
                                                    {{ $this->coachPaidHours }}
                                                    {{ \Illuminate\Support\Str::plural('hr', $this->coachPaidHours) }}
                                                </span>
                                            </dt>
                                            <dd class="shrink-0 self-start tabular-nums text-zinc-800 dark:text-zinc-200">
                                                {{ Money::formatMinor($timesCoachFee, $currency) }}
                                            </dd>
                                        </div>
                                    @endif
                                    <div class="flex justify-between gap-3">
                                        <dt>
                                            Convenience fee
                                            <span class="mt-0.5 block font-normal text-zinc-500 dark:text-zinc-400">
                                                {{ currentBookingFeeSetting()->breakdownLabel() }}
                                            </span>
                                        </dt>
                                        <dd class="shrink-0 self-start tabular-nums text-zinc-800 dark:text-zinc-200">
                                            {{ Money::formatMinor($timesBookingFee, $currency) }}
                                        </dd>
                                    </div>
                                    <div class="flex justify-between gap-3 border-t border-zinc-200 pt-2 text-sm font-bold text-zinc-900 dark:border-zinc-700 dark:text-white">
                                        <dt>Total</dt>
                                        <dd class="shrink-0 tabular-nums">
                                            {{ Money::formatMinor($timesCheckoutTotal, $currency) }}
                                        </dd>
                                    </div>
                                </dl>
                            @endif
                        </div>
                        <button
                            type="button"
                            wire:click="goToReview"
                            @disabled(! $timesHasSlots)
                            class="w-full rounded-xl bg-teal-600 px-6 py-3.5 text-sm font-bold uppercase tracking-wide text-white shadow-lg shadow-teal-900/25 transition hover:bg-teal-700 active:scale-[0.99] disabled:cursor-not-allowed disabled:bg-zinc-400 disabled:shadow-none disabled:hover:bg-zinc-400 dark:bg-teal-600 dark:hover:bg-teal-500 dark:disabled:bg-zinc-600"
                        >
                            Continue to review
                        </button>
                    </div>
                </div>
                @endif
            @else
                @php
                    $reviewSpecs = $this->buildSpecsForSubmit();
                @endphp
                {{-- Review step --}}
                <div class="space-y-6">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h2 class="font-display text-xl font-bold text-zinc-900 dark:text-white">
                                Review your request
                            </h2>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $this->bookingCalendarDateLabel() }}
                                @if (count($reviewSpecs) > 0)
                                    @php
                                        $nCourtsPicked = collect($reviewSpecs)->pluck('court.id')->unique()->count();
                                    @endphp
                                    · {{ $nCourtsPicked }} {{ \Illuminate\Support\Str::plural('court', $nCourtsPicked) }}
                                @endif
                            </p>
                            @if (config('booking.venue_checkout_show_coach') && $coachUserId !== '' && $this->coachPaidHours > 0)
                                @php
                                    $reviewCoachSummary = $this->availableCoachesForReview->firstWhere('id', $coachUserId);
                                @endphp
                                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                                    <span class="font-semibold text-zinc-800 dark:text-zinc-200">Coach:</span>
                                    {{ $reviewCoachSummary?->name ?? 'Selected coach' }}
                                    · {{ $this->coachPaidHours }}
                                    paid {{ \Illuminate\Support\Str::plural('hour', $this->coachPaidHours) }}
                                    <span class="text-zinc-500">(change with Edit times)</span>
                                </p>
                            @endif
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            @if (count($reviewSpecs) > 0)
                                <button
                                    type="button"
                                    wire:click="downloadReviewCalendar"
                                    class="inline-flex items-center rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-xs font-semibold text-zinc-800 shadow-sm transition hover:border-sky-300 hover:text-sky-900 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:border-sky-700"
                                >
                                    Add to calendar (.ics)
                                </button>
                            @endif
                            <button
                                type="button"
                                wire:click="backToTimes"
                                class="text-sm font-semibold text-teal-700 hover:text-teal-800 dark:text-teal-400"
                            >
                                ← Edit times
                            </button>
                        </div>
                    </div>

                    <div
                        class="overflow-hidden rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900"
                    >
                        <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Selected slots & totals
                        </p>
                        @if (count($reviewSpecs) === 0)
                            <p class="mt-3 text-sm text-amber-800 dark:text-amber-200">
                                These times are no longer all available. Go back and adjust your selection.
                            </p>
                        @endif
                        @php
                            $giftEst = $this->reviewGiftEstimateCents;
                            $courtSub = $this->reviewCourtSubtotalCents;
                            $coachFee = $this->reviewCoachFeeCents;
                            $bookingFee = $this->reviewBookingFeeCents;
                            $checkoutTotal = $this->reviewCheckoutTotalCents;
                        @endphp
                        <div class="mt-3 overflow-x-auto">
                            <table class="w-full min-w-[18rem] border-collapse text-left text-sm text-zinc-800 dark:text-zinc-200">
                                <thead>
                                    <tr
                                        class="border-b border-zinc-200 dark:border-zinc-700"
                                    >
                                        <th
                                            scope="col"
                                            class="py-2 pr-3 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                                        >
                                            Court
                                        </th>
                                        <th
                                            scope="col"
                                            class="py-2 pr-3 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                                        >
                                            Time
                                        </th>
                                        <th
                                            scope="col"
                                            class="w-[1%] whitespace-nowrap py-2 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                                        >
                                            Fee
                                        </th>
                                        <th
                                            scope="col"
                                            class="w-[1%] py-2 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                                        >
                                            <span class="sr-only">Remove</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                    @foreach ($reviewSpecs as $spec)
                                        <tr wire:key="vb-review-{{ $spec['court']->id }}-{{ $spec['starts']->timestamp }}">
                                            <td class="py-2.5 pr-3 align-top font-medium">
                                                {{ $spec['court']->name }}
                                            </td>
                                            <td class="py-2.5 pr-3 align-top text-zinc-600 dark:text-zinc-400">
                                                {{ $spec['starts']->format('g:i A') }} – {{ $spec['ends']->format('g:i A') }}
                                                <span class="whitespace-nowrap text-zinc-500 dark:text-zinc-500">
                                                    ({{ count($spec['hours']) }} {{ count($spec['hours']) === 1 ? 'hr' : 'hrs' }})
                                                </span>
                                            </td>
                                            <td class="py-2.5 text-right align-top tabular-nums">
                                                {{ Money::formatMinor($spec['court_gross_cents'] ?? $spec['gross_cents'], $currency) }}
                                            </td>
                                            <td class="py-2.5 pl-2 text-right align-top">
                                                <button
                                                    type="button"
                                                    wire:click="removeReviewSpecSlots('{{ $spec['court']->id }}', {{ json_encode($spec['hours']) }})"
                                                    wire:loading.attr="disabled"
                                                    class="inline-flex items-center justify-center rounded-md p-1 text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-700 disabled:opacity-50 dark:text-zinc-500 dark:hover:bg-zinc-800 dark:hover:text-zinc-200"
                                                    title="Remove this block from your request"
                                                >
                                                    <span class="sr-only">Remove</span>
                                                    <svg
                                                        class="size-4 shrink-0"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke-width="1.5"
                                                        stroke="currentColor"
                                                        aria-hidden="true"
                                                    >
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M6 18 18 6M6 6l12 12"
                                                        />
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="border-t border-zinc-200 dark:border-zinc-700">
                                        <td
                                            colspan="2"
                                            class="pt-3 text-sm font-bold text-zinc-900 dark:text-white"
                                        >
                                            Courts subtotal
                                        </td>
                                        <td class="pt-3 text-right text-sm font-bold tabular-nums text-zinc-900 dark:text-white">
                                            {{ Money::formatMinor($courtSub, $currency) }}
                                        </td>
                                        <td class="pt-3"></td>
                                    </tr>
                                    @if (config('booking.venue_checkout_show_coach') && $coachUserId !== '' && $this->coachPaidHours > 0)
                                        <tr>
                                            <td colspan="2" class="pt-1 text-sm text-zinc-700 dark:text-zinc-300">
                                                Coach
                                                <span class="block text-xs font-normal text-zinc-500 dark:text-zinc-400">
                                                    {{ $this->coachPaidHours }}
                                                    {{ \Illuminate\Support\Str::plural('hr', $this->coachPaidHours) }} at
                                                    their listed rate
                                                </span>
                                            </td>
                                            <td class="pt-1 text-right text-sm tabular-nums text-zinc-800 dark:text-zinc-200">
                                                {{ Money::formatMinor($coachFee, $currency) }}
                                            </td>
                                            <td class="pt-1"></td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <td colspan="2" class="pt-2 text-sm text-zinc-700 dark:text-zinc-300">
                                            Convenience fee
                                            <span class="block text-xs font-normal text-zinc-500 dark:text-zinc-400">
                                                {{ currentBookingFeeSetting()->breakdownLabel() }}
                                            </span>
                                        </td>
                                        <td class="pt-2 text-right text-sm tabular-nums text-zinc-800 dark:text-zinc-200">
                                            {{ Money::formatMinor($bookingFee, $currency) }}
                                        </td>
                                        <td class="pt-2"></td>
                                    </tr>
                                    <tr>
                                        <td
                                            colspan="2"
                                            class="pt-2 text-sm font-bold text-zinc-900 dark:text-white"
                                        >
                                            Total
                                        </td>
                                        <td class="pt-2 text-right text-sm font-bold tabular-nums text-zinc-900 dark:text-white">
                                            {{ Money::formatMinor($checkoutTotal, $currency) }}
                                        </td>
                                        <td class="pt-2"></td>
                                    </tr>
                                    @if ($giftEst > 0)
                                        <tr>
                                            <td colspan="2" class="pt-1 text-sm text-zinc-700 dark:text-zinc-300">
                                                Gift card
                                                <span class="block text-xs font-normal text-zinc-500 dark:text-zinc-400">
                                                    Estimated at submit; limits may apply.
                                                </span>
                                            </td>
                                            <td class="pt-1 text-right text-sm tabular-nums text-emerald-700 dark:text-emerald-400">
                                                −{{ Money::formatMinor($giftEst, $currency) }}
                                            </td>
                                            <td class="pt-1"></td>
                                        </tr>
                                        <tr>
                                            <td
                                                colspan="2"
                                                class="pt-2 text-base font-bold text-zinc-900 dark:text-white"
                                            >
                                                Estimated balance
                                            </td>
                                            <td class="pt-2 text-right text-base font-bold tabular-nums text-zinc-900 dark:text-white">
                                                {{ Money::formatMinor($this->reviewBalanceAfterGiftCents, $currency) }}
                                            </td>
                                            <td class="pt-2"></td>
                                        </tr>
                                    @endif
                                </tfoot>
                            </table>
                        </div>
                        @if ($this->reviewBookingFeeCents > 0)
                            <div class="mt-4 rounded-lg border border-zinc-200 bg-zinc-50/80 px-3 py-3 dark:border-zinc-700 dark:bg-zinc-900/50">
                                <label class="flex cursor-pointer items-start gap-3 text-xs leading-relaxed text-zinc-700 dark:text-zinc-300">
                                    <input
                                        type="checkbox"
                                        wire:model.live="ackConvenienceFeeNonRefundable"
                                        class="mt-0.5 size-4 shrink-0 rounded border-zinc-300 text-teal-600 focus:ring-teal-500 dark:border-zinc-600 dark:bg-zinc-950"
                                    />
                                    <span>
                                        I understand that the
                                        <span class="font-semibold text-zinc-900 dark:text-white">convenience fee</span>
                                        is
                                        <span class="font-semibold text-zinc-900 dark:text-white">non-refundable</span>,
                                        as described in the
                                        <a
                                            href="{{ route('terms') }}#convenience-fee"
                                            wire:navigate
                                            class="font-semibold text-teal-700 underline-offset-2 hover:underline dark:text-teal-400"
                                        >
                                            Terms &amp; conditions
                                        </a>
                                        (including where court or venue amounts may still be refundable under venue rules).
                                    </span>
                                </label>
                                @error('ackConvenienceFeeNonRefundable')
                                    <p class="mt-2 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif
                        <div
                            class="mt-4 border-t border-zinc-200 pt-4 dark:border-zinc-700"
                            x-data="{}"
                        >
                            <div
                                class="flex max-w-md items-stretch overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-950"
                            >
                                <input
                                    x-ref="giftCardInput"
                                    type="text"
                                    wire:model.live.debounce.400ms="giftCardCode"
                                    class="min-w-0 flex-1 border-0 bg-transparent px-3 py-2 font-mono text-sm uppercase text-zinc-900 placeholder:text-zinc-400 focus:outline-none focus:ring-0 dark:text-zinc-100 dark:placeholder:text-zinc-500"
                                    placeholder="Gift card code (optional)"
                                    aria-label="Gift card code"
                                    autocomplete="off"
                                />
                                <button
                                    type="button"
                                    class="flex shrink-0 items-center justify-center border-l border-zinc-200 px-3 text-zinc-500 transition hover:bg-zinc-50 hover:text-zinc-800 dark:border-zinc-600 dark:hover:bg-zinc-800/80 dark:hover:text-zinc-200"
                                    @click="$refs.giftCardInput.focus(); $refs.giftCardInput.select()"
                                    title="Edit gift card code"
                                >
                                    <span class="sr-only">Edit gift card code</span>
                                    <svg
                                        class="size-5 shrink-0"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="2"
                                        stroke="currentColor"
                                        aria-hidden="true"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"
                                        />
                                    </svg>
                                </button>
                            </div>
                            @error('giftCardCode')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <p class="mt-3 text-xs text-zinc-500 dark:text-zinc-400">
                            The venue confirms the final amount when they review your request.
                        </p>
                    </div>

                    @if ($this->canConfigureOpenPlay && auth()->check() && auth()->user()->isOpenPlayHost())
                        <div
                            class="overflow-hidden rounded-xl border border-violet-200 bg-violet-50/40 p-5 dark:border-violet-900/50 dark:bg-violet-950/20"
                        >
                            <div>
                                <h3 class="font-display text-sm font-bold text-zinc-900 dark:text-white">
                                    Open play host
                                </h3>
                                <p class="mt-1 text-xs text-zinc-600 dark:text-zinc-400">
                                    This reservation will be listed as <strong>open play</strong> so other members can request
                                    to join. You approve who plays and share how they pay you. Requires
                                    <strong>one court</strong> in a <strong>single continuous block</strong> (your current
                                    selection).
                                </p>
                            </div>
                            @error('isOpenPlay')
                                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                            <div class="mt-4 space-y-4 border-t border-violet-200/80 pt-4 dark:border-violet-800/60">
                                    <div class="max-w-xs">
                                        <label
                                            class="text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400"
                                            for="open-play-max-slots"
                                        >
                                            Max extra players
                                        </label>
                                        <input
                                            id="open-play-max-slots"
                                            type="number"
                                            wire:model.live="openPlayMaxSlots"
                                            min="1"
                                            max="48"
                                            class="mt-1 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm tabular-nums dark:border-zinc-700 dark:bg-zinc-950"
                                        />
                                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-500">
                                            Not including you — cap on how many can join after you’re confirmed.
                                        </p>
                                        @error('openPlayMaxSlots')
                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label
                                            class="text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400"
                                            for="open-play-notes"
                                        >
                                            Info / notes <span class="font-normal normal-case text-zinc-400">(optional)</span>
                                        </label>
                                        <textarea
                                            id="open-play-notes"
                                            wire:model="openPlayPublicNotes"
                                            rows="3"
                                            class="mt-1 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                                            placeholder="Format, skill level, what to bring…"
                                        ></textarea>
                                        @error('openPlayPublicNotes')
                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label
                                            class="text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400"
                                            for="open-play-pay"
                                        >
                                            Payment details for players
                                        </label>
                                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-500">
                                            Shown to people you accept — e.g. GCash number, amount per head, due before
                                            game day.
                                        </p>
                                        <textarea
                                            id="open-play-pay"
                                            wire:model="openPlayHostPaymentDetails"
                                            rows="3"
                                            class="mt-1 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                                            placeholder="GCash 09xx · ₱200 each · pay before Sat 5pm"
                                            required
                                        ></textarea>
                                        @error('openPlayHostPaymentDetails')
                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label
                                            class="text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400"
                                            for="open-play-external-contact"
                                        >
                                            Refund / contact line
                                            <span class="font-normal normal-case text-zinc-400">(optional)</span>
                                        </label>
                                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-500">
                                            How players can reach you for refunds or questions — e.g. Viber, email, or
                                            phone. Shown on the join page.
                                        </p>
                                        <textarea
                                            id="open-play-external-contact"
                                            wire:model="openPlayExternalContact"
                                            rows="2"
                                            class="mt-1 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                                            placeholder="Viber +63… · refunds@…"
                                        ></textarea>
                                        @error('openPlayExternalContact')
                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label
                                            class="text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400"
                                            for="open-play-refund-policy"
                                        >
                                            Refund policy
                                            <span class="font-normal normal-case text-zinc-400">(optional)</span>
                                        </label>
                                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-500">
                                            Set expectations so players pay with confidence — e.g. partial refunds only
                                            (50%), processing fees, or that you’re not obliged to refund the full amount.
                                            Shown on the join page.
                                        </p>
                                        <textarea
                                            id="open-play-refund-policy"
                                            wire:model="openPlayRefundPolicy"
                                            rows="3"
                                            class="mt-1 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                                            placeholder="e.g. If you cancel after paying, refunds are up to 50% of the fee at the host’s discretion. No full refund guaranteed."
                                        ></textarea>
                                        @error('openPlayRefundPolicy')
                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                        </div>
                    @endif

                    @if (! $paymongoConfigured && $this->reviewBalanceAfterGiftCents > 0)
                        <div
                            class="rounded-xl border border-amber-200/90 bg-amber-50/90 p-4 text-sm text-amber-950 dark:border-amber-900/40 dark:bg-amber-950/35 dark:text-amber-100/95"
                            role="status"
                        >
                            <p class="font-semibold">Online checkout isn’t available</p>
                            <p class="mt-1 text-xs leading-relaxed text-amber-900/95 dark:text-amber-200/90">
                                There’s an amount due, but online payment isn’t enabled here yet. Please contact the club.
                            </p>
                        </div>
                    @endif

                    @error('submit')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror

                    @guest
                        <div
                            class="rounded-xl border border-teal-200 bg-teal-50/80 p-5 dark:border-teal-900/40 dark:bg-teal-950/30"
                        >
                            <p class="text-sm font-medium text-teal-950 dark:text-teal-100">
                                Sign in once to send your request. We’ll bring you right back here with your times saved.
                            </p>
                            <div class="mt-4 flex flex-wrap gap-3">
                                <button
                                    type="button"
                                    wire:click="continueToSignIn"
                                    class="rounded-xl bg-teal-600 px-6 py-3 text-sm font-bold uppercase tracking-wide text-white shadow-md hover:bg-teal-700 dark:bg-teal-600 dark:hover:bg-teal-500"
                                >
                                    Sign in to submit
                                </button>
                                <button
                                    type="button"
                                    wire:click="continueToRegister"
                                    class="inline-flex items-center rounded-xl border border-teal-300 px-6 py-3 text-sm font-bold text-teal-900 hover:bg-teal-100 dark:border-teal-700 dark:text-teal-100 dark:hover:bg-teal-900/40"
                                >
                                    Create account
                                </button>
                            </div>
                        </div>
                    @else
                        @if (! $this->canSubmitBookings())
                            <p class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-100">
                                Player and coach accounts can submit requests from this page. Please use your venue or
                                desk tools if you work for the club.
                            </p>
                        @endif
                    @endguest

                    @auth
                        @if (
                            $step === 'review'
                            && $this->canSubmitBookings()
                            && ($paymongoConfigured || $this->reviewBalanceAfterGiftCents <= 0)
                        )
                            @php
                                $ctaCourtSub = $this->reviewCourtSubtotalCents;
                                $ctaCoachFee = $this->reviewCoachFeeCents;
                                $ctaBookingFee = $this->reviewBookingFeeCents;
                                $ctaCheckoutTotal = $this->reviewCheckoutTotalCents;
                                $ctaGiftEst = $this->reviewGiftEstimateCents;
                                $ctaBalanceAfter = $this->reviewBalanceAfterGiftCents;
                                $ctaNeedsPayment = $paymongoConfigured && $this->reviewBalanceAfterGiftCents > 0;
                            @endphp

                            {{-- Floating checkout + totals (matches times-step continue bar) --}}
                            <div
                                class="fixed inset-x-0 bottom-0 z-30 lg:inset-x-auto lg:bottom-6 lg:left-auto lg:right-6 lg:w-full lg:max-w-sm"
                                role="region"
                                aria-label="Checkout summary and submit"
                            >
                                <div
                                    class="border-t border-zinc-200/90 bg-white/95 px-4 pt-3 pb-[max(0.75rem,env(safe-area-inset-bottom,0px))] shadow-[0_-8px_30px_-10px_rgba(0,0,0,0.12)] backdrop-blur-md dark:border-zinc-700/90 dark:bg-zinc-950/95 dark:shadow-black/40 lg:rounded-2xl lg:border lg:px-4 lg:py-4 lg:shadow-2xl"
                                >
                                    <div
                                        class="mb-3"
                                        aria-live="polite"
                                        aria-atomic="true"
                                    >
                                        @if (! $this->reviewStepHasSpecs())
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                                Fix your slot selection above to continue.
                                            </p>
                                        @else
                                            <dl class="space-y-1.5 text-xs text-zinc-700 dark:text-zinc-300">
                                                <div class="flex justify-between gap-3">
                                                    <dt>Courts subtotal</dt>
                                                    <dd class="shrink-0 tabular-nums font-medium text-zinc-900 dark:text-white">
                                                        {{ Money::formatMinor($ctaCourtSub, $currency) }}
                                                    </dd>
                                                </div>
                                                @if (config('booking.venue_checkout_show_coach') && $coachUserId !== '' && $this->coachPaidHours > 0)
                                                    <div class="flex justify-between gap-3">
                                                        <dt>
                                                            Coach
                                                            <span class="mt-0.5 block font-normal text-zinc-500 dark:text-zinc-400">
                                                                {{ $this->coachPaidHours }}
                                                                {{ \Illuminate\Support\Str::plural('hr', $this->coachPaidHours) }}
                                                            </span>
                                                        </dt>
                                                        <dd class="shrink-0 self-start tabular-nums text-zinc-800 dark:text-zinc-200">
                                                            {{ Money::formatMinor($ctaCoachFee, $currency) }}
                                                        </dd>
                                                    </div>
                                                @endif
                                                <div class="flex justify-between gap-3">
                                                    <dt>
                                                        Convenience fee
                                                        <span class="mt-0.5 block font-normal text-zinc-500 dark:text-zinc-400">
                                                            {{ currentBookingFeeSetting()->breakdownLabel() }}
                                                        </span>
                                                    </dt>
                                                    <dd class="shrink-0 self-start tabular-nums text-zinc-800 dark:text-zinc-200">
                                                        {{ Money::formatMinor($ctaBookingFee, $currency) }}
                                                    </dd>
                                                </div>
                                                <div class="flex justify-between gap-3 border-t border-zinc-200 pt-2 text-sm font-bold text-zinc-900 dark:border-zinc-700 dark:text-white">
                                                    <dt>Total</dt>
                                                    <dd class="shrink-0 tabular-nums">
                                                        {{ Money::formatMinor($ctaCheckoutTotal, $currency) }}
                                                    </dd>
                                                </div>
                                                @if ($ctaGiftEst > 0)
                                                    <div class="flex justify-between gap-3 text-xs text-emerald-800 dark:text-emerald-200">
                                                        <dt>Gift card (est.)</dt>
                                                        <dd class="shrink-0 tabular-nums">
                                                            −{{ Money::formatMinor($ctaGiftEst, $currency) }}
                                                        </dd>
                                                    </div>
                                                    <div class="flex justify-between gap-3 border-t border-zinc-200 pt-2 text-sm font-bold text-zinc-900 dark:border-zinc-700 dark:text-white">
                                                        <dt>Est. balance</dt>
                                                        <dd class="shrink-0 tabular-nums">
                                                            {{ Money::formatMinor($ctaBalanceAfter, $currency) }}
                                                        </dd>
                                                    </div>
                                                @endif
                                            </dl>
                                        @endif
                                    </div>
                                    <button
                                        type="button"
                                        wire:key="review-submit-{{ $this->ackConvenienceFeeNonRefundable ? '1' : '0' }}-{{ $this->reviewBookingFeeCents }}"
                                        wire:click="submitRequest"
                                        @disabled($this->reviewSubmitActionDisabled())
                                        class="w-full rounded-xl bg-teal-600 px-6 py-3.5 text-sm font-bold uppercase tracking-wide text-white shadow-lg shadow-teal-900/25 transition hover:bg-teal-700 active:scale-[0.99] disabled:cursor-not-allowed disabled:bg-zinc-400 disabled:shadow-none disabled:hover:bg-zinc-400 dark:bg-teal-600 dark:hover:bg-teal-500 dark:disabled:bg-zinc-600"
                                    >
                                        @if ($ctaNeedsPayment)
                                            Continue to payment
                                        @else
                                            Submit request
                                        @endif
                                    </button>
                                </div>
                            </div>
                        @endif
                    @endauth
                </div>
            @endif
        </div>
    </div>

    @if (public_reviews_enabled())
            <section id="venue-reviews" class="mt-10 scroll-mt-24 border-t border-zinc-200 pt-10 dark:border-zinc-800">
                <h2 class="font-display text-xl font-bold text-zinc-900 dark:text-white">Venue &amp; reviews</h2>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                    Location, contact, amenities, and what members say about this club.
                </p>
                <div class="mt-8 grid gap-10 lg:grid-cols-2 lg:items-start">
                    <x-venue-public-listing :venue="$courtClient" />
                    <div class="min-w-0">
                        <livewire:reviews.user-reviews-panel
                            target-type="venue"
                            :target-id="$courtClient->id"
                            :show-heading="false"
                            :key="'ur-venue-booking-'.$courtClient->id"
                        />
                    </div>
                </div>
            </section>
        @else
            <section id="venue-details" class="mt-10 scroll-mt-24 border-t border-zinc-200 pt-10 dark:border-zinc-800">
                <h2 class="font-display text-xl font-bold text-zinc-900 dark:text-white">Venue details</h2>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                    Location, contact, and amenities for this club.
                </p>
                <div class="mt-8">
                    <x-venue-public-listing :venue="$courtClient" />
                </div>
            </section>
        @endif

        @if (public_reviews_enabled() && config('booking.venue_checkout_show_coach') && $coachUserId !== '')
            <section id="coach-reviews" class="mt-8 scroll-mt-24 border-t border-zinc-200 pt-8 dark:border-zinc-800">
                <livewire:reviews.user-reviews-panel
                    target-type="coach"
                    :target-id="$coachUserId"
                    :key="'ur-coach-booking-'.$coachUserId"
                />
            </section>
        @endif
</div>
