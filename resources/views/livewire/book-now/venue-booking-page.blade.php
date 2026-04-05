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
                        <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">
                            <span class="text-amber-500 dark:text-amber-400">★</span>
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
        <div class="min-w-0 flex-1 space-y-6">
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
                                                        {{ $court->name }}
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

                    <div class="flex flex-wrap justify-end gap-3">
                        <button
                            type="button"
                            wire:click="goToReview"
                            class="rounded-xl bg-teal-600 px-6 py-3 text-sm font-bold uppercase tracking-wide text-white shadow-md shadow-teal-900/20 hover:bg-teal-700 dark:bg-teal-600 dark:hover:bg-teal-500"
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
                        <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Selected slots
                        </p>
                        @if (count($reviewSpecs) === 0)
                            <p class="mt-3 text-sm text-amber-800 dark:text-amber-200">
                                These times are no longer all available. Go back and adjust your selection.
                            </p>
                        @endif
                        <ul class="mt-3 space-y-2 text-sm text-zinc-800 dark:text-zinc-200">
                            @foreach ($reviewSpecs as $spec)
                                <li class="flex flex-wrap justify-between gap-2 border-b border-zinc-100 pb-2 last:border-0 dark:border-zinc-800">
                                    <span class="font-medium">{{ $spec['court']->name }}</span>
                                    <span class="text-zinc-600 dark:text-zinc-400">
                                        {{ $spec['starts']->format('g:i A') }} – {{ $spec['ends']->format('g:i A') }}
                                    </span>
                                    <span class="w-full text-xs text-zinc-500 sm:w-auto sm:text-sm">
                                        {{ Money::formatMinor($spec['gross_cents'], $currency) }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                        <p class="mt-4 flex flex-wrap items-baseline justify-between gap-2 border-t border-zinc-200 pt-4 text-base font-bold text-zinc-900 dark:border-zinc-700 dark:text-white">
                            <span>Estimated total</span>
                            <span>{{ Money::formatMinor($this->reviewEstimateCents, $currency) }}</span>
                        </p>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            The venue confirms the final amount when they review your request.
                        </p>
                    </div>

                    <div
                        class="overflow-hidden rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900"
                    >
                        <h3 class="font-display text-sm font-bold text-zinc-900 dark:text-white">
                            Notes &amp; payment (optional)
                        </h3>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            Add anything the venue should know. Payment reference helps match your transfer if you have
                            one.
                        </p>
                        <div class="mt-4 space-y-4">
                            <div>
                                <label
                                    class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                                >
                                    Notes
                                </label>
                                <textarea
                                    wire:model="bookingNotes"
                                    rows="2"
                                    class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                                    placeholder="e.g. doubles, need parking info"
                                ></textarea>
                            </div>
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
