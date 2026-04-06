@php
    use App\Models\Court;
    use App\Support\Money;

    $courts = $this->courtsOrderedForGrid();
    $slotHours = $this->slotHoursForSelectedDate();
    $dow = $this->bookingDayOfWeek();
    $dateBlocks = $this->dateBlockLookup;
    $occupancy = $this->occupancyBySlot;
    $currency = $courtClient->currency ?? 'PHP';
    $cover = $courtClient->coverImageUrl();
    $minRate = $courts
        ->map(fn (Court $c) => $c->effectiveHourlyRateCents())
        ->filter()
        ->min();
@endphp

<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
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
                <div class="relative aspect-[4/3] bg-zinc-100 dark:bg-zinc-800">
                    @if ($cover)
                        <img src="{{ $cover }}" alt="" class="size-full object-cover" loading="lazy" />
                    @else
                        <div
                            class="flex size-full items-center justify-center bg-gradient-to-br from-emerald-500 to-teal-800"
                            aria-hidden="true"
                        >
                            <span class="font-display text-3xl font-extrabold text-white/90">
                                {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($courtClient->name, 0, 2)) }}
                            </span>
                        </div>
                    @endif
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
                    @if ($courtClient->public_rating_average !== null)
                        <p class="inline-flex flex-wrap items-center gap-1 text-sm font-semibold text-zinc-800 dark:text-zinc-200">
                            <x-icon name="star-solid" class="size-4 text-amber-500 dark:text-amber-400" />
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
                        {{ \Illuminate\Support\Str::plural('court', $courts->count()) }} available to book
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
                'max-lg:pb-28' => $step === 'times',
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
                                    class="rounded-lg border border-zinc-200 px-3 py-2 text-xs font-semibold text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                >
                                    Previous day
                                </button>
                                <input
                                    type="date"
                                    wire:model.live="bookingCalendarDate"
                                    class="rounded-lg border border-zinc-200 px-3 py-2 text-sm text-zinc-800 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                                />
                                <button
                                    type="button"
                                    wire:click="shiftBookingDate(1)"
                                    class="rounded-lg border border-zinc-200 px-3 py-2 text-xs font-semibold text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                >
                                    Next day
                                </button>
                            </div>
                            <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                {{ $this->bookingCalendarDateLabel() }}
                                <span class="text-zinc-500">({{ $dayLabels[$dow] ?? '' }})</span>
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
                            This date is closed. Try another day.
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
                                                            if ($booked !== null) {
                                                                $availStyle =
                                                                    'cursor-default border-slate-300 bg-slate-200/95 text-slate-900 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100';
                                                                $cellTitle = 'Booked';
                                                                $isBookedCell = true;
                                                            } elseif ($slotSelected) {
                                                                $availStyle =
                                                                    'border-teal-600 bg-teal-600 text-white shadow-sm ring-2 ring-teal-400/40 dark:ring-teal-500/30';
                                                                $cellTitle = 'Selected';
                                                                $isBookedCell = false;
                                                            } elseif ($blocked) {
                                                                $availStyle =
                                                                    'border-red-200 bg-red-50/90 dark:border-red-900/50 dark:bg-red-950/25';
                                                                $cellTitle = 'Blocked';
                                                                $isBookedCell = false;
                                                            } else {
                                                                $availStyle =
                                                                    'border-emerald-200 bg-emerald-50/50 dark:border-emerald-900/40 dark:bg-emerald-950/20';
                                                                $cellTitle = 'Open';
                                                                $isBookedCell = false;
                                                            }
                                                        @endphp
                                                        <td class="p-1.5 align-middle">
                                                            @if ($isBookedCell)
                                                                <div
                                                                    class="flex min-h-[3.25rem] w-full flex-col items-center justify-center rounded-lg border px-1.5 py-1.5 text-center {{ $availStyle }}"
                                                                >
                                                                    <span class="text-[10px] font-bold uppercase tracking-wide">
                                                                        Booked
                                                                    </span>
                                                                </div>
                                                            @else
                                                                <button
                                                                    type="button"
                                                                    wire:click="toggleSlot('{{ $court->id }}', {{ $hour }})"
                                                                    class="flex min-h-[3.25rem] w-full flex-col items-center justify-center rounded-lg border px-2 py-2 text-center text-xs font-semibold transition-colors hover:border-teal-500/60 hover:bg-teal-50/80 dark:hover:bg-teal-950/30 {{ $availStyle }}"
                                                                >
                                                                    {{ $cellTitle }}
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

                    <div class="hidden flex-wrap justify-end gap-3 lg:flex">
                        <button
                            type="button"
                            wire:click="goToReview"
                            class="rounded-xl bg-teal-600 px-6 py-3 text-sm font-bold uppercase tracking-wide text-white shadow-md shadow-teal-900/20 hover:bg-teal-700 dark:bg-teal-600 dark:hover:bg-teal-500"
                        >
                            Continue to review
                        </button>
                    </div>
                </div>

                {{-- Sticky CTA: mobile & tablet --}}
                <div
                    class="fixed inset-x-0 bottom-0 z-30 border-t border-zinc-200/90 bg-white/95 px-4 pt-3 pb-[max(0.75rem,env(safe-area-inset-bottom,0px))] shadow-[0_-8px_30px_-10px_rgba(0,0,0,0.12)] backdrop-blur-md dark:border-zinc-700/90 dark:bg-zinc-950/95 dark:shadow-black/40 lg:hidden"
                >
                    <div class="mx-auto max-w-7xl">
                        <button
                            type="button"
                            wire:click="goToReview"
                            class="w-full rounded-xl bg-teal-600 px-6 py-3.5 text-sm font-bold uppercase tracking-wide text-white shadow-lg shadow-teal-900/25 transition hover:bg-teal-700 active:scale-[0.99] dark:bg-teal-600 dark:hover:bg-teal-500"
                        >
                            Continue to review
                        </button>
                    </div>
                </div>
            @else
                @php($reviewSpecs = $this->buildSpecsForSubmit())
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
                                    @php($nCourtsPicked = collect($reviewSpecs)->pluck('court.id')->unique()->count())
                                    · {{ $nCourtsPicked }} {{ \Illuminate\Support\Str::plural('court', $nCourtsPicked) }}
                                @endif
                            </p>
                        </div>
                        <button
                            type="button"
                            wire:click="backToTimes"
                            class="text-sm font-semibold text-teal-700 hover:text-teal-800 dark:text-teal-400"
                        >
                            ← Edit times
                        </button>
                    </div>

                    <div
                        class="overflow-hidden rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900"
                    >
                        <h3 class="font-display text-sm font-bold text-zinc-900 dark:text-white">
                            Coach (optional)
                        </h3>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            Add a coach to the same request. You choose how many hours they’re paid for (up to your
                            selected slot hours).
                        </p>
                        @if ($this->coachSelectionBlockedReason())
                            <p class="mt-3 text-sm text-amber-800 dark:text-amber-200/90">
                                {{ $this->coachSelectionBlockedReason() }}
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
                        @php($giftEst = $this->reviewGiftEstimateCents)
                        @php($grossTotal = $this->reviewEstimateCents)
                        @php($courtSub = $this->reviewCourtSubtotalCents)
                        @php($coachFee = $this->reviewCoachFeeCents)
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
                                            </td>
                                            <td class="py-2.5 text-right align-top tabular-nums">
                                                {{ Money::formatMinor($spec['court_gross_cents'] ?? $spec['gross_cents'], $currency) }}
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
                                    </tr>
                                    @if ($coachUserId !== '' && $this->coachPaidHours > 0)
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
                                        </tr>
                                    @endif
                                    <tr>
                                        <td
                                            colspan="2"
                                            class="pt-2 text-sm font-bold text-zinc-900 dark:text-white"
                                        >
                                            Total
                                        </td>
                                        <td class="pt-2 text-right text-sm font-bold tabular-nums text-zinc-900 dark:text-white">
                                            {{ Money::formatMinor($grossTotal, $currency) }}
                                        </td>
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
                                        </tr>
                                    @endif
                                </tfoot>
                            </table>
                        </div>
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

                    <div
                        class="overflow-hidden rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900"
                    >
                        <h3 class="font-display text-sm font-bold text-zinc-900 dark:text-white">
                            Payment (optional)
                        </h3>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            Add a payment reference or proof if you have already paid—this helps the venue match your
                            transfer.
                        </p>
                        <div class="mt-4 space-y-4">
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div class="sm:col-span-2 sm:max-w-xs">
                                    <label
                                        class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                                    >
                                        Payment method
                                    </label>
                                    <select
                                        wire:model="paymentMethod"
                                        class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                                    >
                                        @foreach (\App\Models\Booking::paymentMethodOptions() as $pm)
                                            <option value="{{ $pm }}">
                                                {{ \App\Models\Booking::paymentMethodLabel($pm) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="sm:col-span-2">
                                    <x-gcash-payment-hint :method="$paymentMethod" />
                                </div>
                                <div class="sm:col-span-2">
                                    <label
                                        class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                                    >
                                        Reference <span class="font-normal normal-case text-zinc-400">(optional)</span>
                                    </label>
                                    <input
                                        type="text"
                                        wire:model="paymentReference"
                                        class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 font-mono text-sm dark:border-zinc-700 dark:bg-zinc-950"
                                        placeholder="Transaction ID / ref"
                                        autocomplete="off"
                                    />
                                </div>
                                <div class="sm:col-span-2">
                                    <label
                                        class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                                    >
                                        Proof screenshot (optional)
                                    </label>
                                    <input
                                        type="file"
                                        wire:model="paymentProof"
                                        accept="image/jpeg,image/png,image/webp,image/gif"
                                        class="mt-1 block w-full text-sm text-zinc-600 file:mr-3 file:rounded-lg file:border-0 file:bg-zinc-200 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-zinc-800 dark:text-zinc-400 dark:file:bg-zinc-700 dark:file:text-zinc-100"
                                    />
                                    <div
                                        wire:loading
                                        wire:target="paymentProof"
                                        class="mt-1 text-xs text-zinc-500"
                                    >
                                        Uploading…
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

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
                        @if ($this->canSubmitBookings())
                            <div class="flex flex-wrap justify-end gap-3">
                                <button
                                    type="button"
                                    wire:click="submitRequest"
                                    class="rounded-xl bg-teal-600 px-6 py-3 text-sm font-bold uppercase tracking-wide text-white shadow-md hover:bg-teal-700 dark:bg-teal-600 dark:hover:bg-teal-500"
                                >
                                    Submit request
                                </button>
                            </div>
                        @else
                            <p class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-100">
                                Player and coach accounts can submit requests from this page. Please use your venue or
                                desk tools if you work for the club.
                            </p>
                        @endif
                    @endguest
                </div>
            @endif
        </div>
    </div>
</div>
