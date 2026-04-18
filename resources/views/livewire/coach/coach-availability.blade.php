<div class="space-y-10">
    <div>
        <h1 class="font-display text-2xl font-bold text-zinc-900 dark:text-white">Schedule &amp; rate</h1>
        <p class="mt-2 max-w-3xl text-sm text-zinc-600 dark:text-zinc-400">
            Choose a venue, pick a day on the calendar (or use the date controls), and tap the hours you’re free to coach.
            Your rate is saved here too — players see it when they add you to a booking.
            <a
                href="{{ route('account.coach.profile') }}"
                wire:navigate
                class="font-semibold text-violet-600 underline decoration-violet-300 underline-offset-2 hover:text-violet-700 dark:text-violet-400 dark:hover:text-violet-300"
            >
                Coach profile
            </a>
            is where you can edit your bio.
        </p>
    </div>

    <div class="grid gap-8 lg:grid-cols-12 lg:items-start">
        <div class="lg:col-span-5">
            <div
                class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
            >
                <div
                    class="flex flex-wrap items-center justify-between gap-2 border-b border-zinc-200 px-3 py-2.5 dark:border-zinc-800 sm:px-4"
                >
                    <button
                        type="button"
                        wire:click="shiftCalendarMonth(-1)"
                        class="rounded-lg border border-zinc-200 px-2.5 py-1.5 text-xs font-semibold text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800"
                    >
                        Prev
                    </button>
                    <p class="text-center text-sm font-bold text-zinc-900 dark:text-white">{{ $calendarMonthLabel }}</p>
                    <button
                        type="button"
                        wire:click="shiftCalendarMonth(1)"
                        class="rounded-lg border border-zinc-200 px-2.5 py-1.5 text-xs font-semibold text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800"
                    >
                        Next
                    </button>
                </div>
                <p class="border-b border-zinc-200 px-3 py-2 text-[11px] text-zinc-500 dark:border-zinc-800 dark:text-zinc-400 sm:px-4">
                    Tap a day to work on that date. Amber dot = you set hours; red = venue closed (no bookings).
                </p>
                <div class="overflow-x-auto">
                    <div class="min-w-[280px]">
                        <div
                            class="grid grid-cols-7 border-b border-zinc-200 bg-zinc-50 text-center text-[10px] font-bold uppercase tracking-wider text-zinc-500 dark:border-zinc-800 dark:bg-zinc-950/80 dark:text-zinc-400"
                        >
                            <div class="px-0.5 py-1.5">M</div>
                            <div class="px-0.5 py-1.5">T</div>
                            <div class="px-0.5 py-1.5">W</div>
                            <div class="px-0.5 py-1.5">T</div>
                            <div class="px-0.5 py-1.5">F</div>
                            <div class="px-0.5 py-1.5">S</div>
                            <div class="px-0.5 py-1.5">S</div>
                        </div>
                        @foreach ($calendarMonthWeeks as $week)
                            <div
                                class="grid grid-cols-7 divide-x divide-zinc-200 border-b border-zinc-200 last:border-b-0 dark:divide-zinc-800 dark:border-zinc-800"
                                wire:key="coach-cal-w-{{ $week[0]['date']->format('Y-m-d') }}"
                            >
                                @foreach ($week as $day)
                                    @php
                                        $ymd = $day['date']->format('Y-m-d');
                                        $isVenueClosed = isset($venueClosureLookup[$ymd]);
                                        $isMarked = isset($coachMarkedDaysLookup[$ymd]);
                                        $isSelected = $ymd === $availabilityDate;
                                        $isToday = $ymd === now($calendarTz)->format('Y-m-d');
                                    @endphp
                                    <div class="p-0.5" wire:key="coach-cal-d-{{ $ymd }}">
                                        <button
                                            type="button"
                                            wire:click="pickCalendarDay('{{ $ymd }}')"
                                            @class([
                                                'relative flex min-h-[2.5rem] w-full flex-col items-center justify-center rounded-md border px-0.5 py-1 text-center text-xs font-semibold transition sm:min-h-[2.75rem]',
                                                'border-zinc-100 bg-zinc-50/90 text-zinc-400 dark:border-zinc-800 dark:bg-zinc-950/60 dark:text-zinc-600' => ! $day['in_month'],
                                                'border-zinc-200 bg-white text-zinc-800 hover:border-violet-400 hover:bg-violet-50/80 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:border-violet-600 dark:hover:bg-violet-950/30' => $day['in_month'] && ! $isVenueClosed,
                                                'border-red-200 bg-red-50/90 text-red-900 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-100' => $day['in_month'] && $isVenueClosed,
                                                'ring-2 ring-violet-500 ring-offset-1 ring-offset-white dark:ring-violet-400 dark:ring-offset-zinc-900' => $isSelected,
                                            ])
                                        >
                                            <span class="tabular-nums">{{ $day['date']->timezone($calendarTz)->day }}</span>
                                            @if ($day['in_month'] && $isToday && ! $isSelected)
                                                <span class="absolute bottom-0.5 size-1 rounded-full bg-violet-500" title="Today"></span>
                                            @endif
                                            @if ($day['in_month'] && $isMarked && ! $isVenueClosed)
                                                <span
                                                    class="mt-0.5 size-1.5 rounded-full bg-amber-500"
                                                    title="You set availability"
                                                ></span>
                                            @endif
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-6 lg:col-span-7">
            @if ($this->coachedVenues->isEmpty())
                <div
                    class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-950 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-100"
                    role="status"
                >
                    Turn on at least one venue under
                    <a href="{{ route('account.coach.courts') }}" wire:navigate class="font-bold underline">Venues you coach</a>
                    before setting availability.
                </div>
            @else
                <div class="flex flex-col gap-4 sm:flex-row sm:flex-wrap sm:items-end">
                    <div class="min-w-[12rem] max-w-md flex-1">
                        <label
                            class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                            for="coach-avail-venue"
                        >
                            Venue
                        </label>
                        <select
                            id="coach-avail-venue"
                            wire:model.live="courtClientId"
                            class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                        >
                            @foreach ($this->coachedVenues as $v)
                                <option value="{{ $v->id }}">
                                    {{ $v->name }}
                                    @if ($v->city)
                                        — {{ $v->city }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div
                    class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/80 sm:p-5"
                >
                    <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        Selected day
                    </p>
                    <div
                        class="mt-3 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between sm:gap-4"
                    >
                        <div class="flex flex-wrap items-center gap-2">
                            <button
                                type="button"
                                wire:click="shiftAvailabilityDate(-1)"
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
                                wire:model.live="availabilityDate"
                                class="rounded-lg border border-zinc-200 px-3 py-2 text-sm text-zinc-800 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                            />
                            <button
                                type="button"
                                wire:click="shiftAvailabilityDate(1)"
                                aria-label="Next day"
                                class="inline-flex items-center justify-center rounded-lg border border-zinc-200 px-2 py-2 text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800"
                            >
                                <span class="sr-only">Next day</span>
                                <svg class="size-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                </svg>
                            </button>
                            <button
                                type="button"
                                wire:click="goToToday"
                                class="rounded-lg border border-violet-200 bg-violet-50 px-3 py-2 text-xs font-semibold text-violet-900 hover:bg-violet-100 dark:border-violet-800 dark:bg-violet-950/50 dark:text-violet-200 dark:hover:bg-violet-900/40"
                            >
                                Today
                            </button>
                        </div>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200">
                            {{ $this->availabilityDateLabel() }}
                        </p>
                    </div>
                </div>

                @if ($this->slotHoursForDate === [])
                    <p class="rounded-xl border border-dashed border-zinc-300 px-4 py-6 text-center text-sm text-zinc-600 dark:border-zinc-600 dark:text-zinc-400">
                        @if ($this->isSelectedDateVenueClosure())
                            This venue is closed on this date (for example a holiday). You can’t set coaching hours — pick
                            another day on the calendar.
                        @else
                            This venue is closed on that weekday or has no bookable start hours — pick another date.
                        @endif
                    </p>
                @else
                    @php
                        $hours = $this->slotHoursForDate;
                        $on = $this->availableHourLookup;
                    @endphp
                    <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900/80">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                    Hours you’re available
                                </p>
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                    Tap to toggle. We apply the same hours to every court at this venue you coach.
                                </p>
                            </div>
                            <button
                                type="button"
                                wire:click="clearHoursForSelectedDate"
                                wire:confirm="Clear all your availability hours for this day at this venue?"
                                class="shrink-0 text-xs font-semibold text-zinc-600 underline decoration-zinc-300 underline-offset-2 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white"
                            >
                                Clear this day
                            </button>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach ($hours as $h)
                                <button
                                    type="button"
                                    wire:click="toggleHour({{ $h }})"
                                    wire:key="coach-h-{{ $courtClientId }}-{{ $availabilityDate }}-{{ $h }}"
                                    @class([
                                        'rounded-lg border px-3 py-2 text-sm font-semibold transition',
                                        'border-violet-500 bg-violet-500 text-white shadow-sm dark:border-violet-400 dark:bg-violet-600' => isset($on[$h]),
                                        'border-zinc-200 bg-zinc-50 text-zinc-700 hover:border-violet-300 hover:bg-violet-50 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-200 dark:hover:border-violet-700' => ! isset($on[$h]),
                                    ])
                                >
                                    {{ \Carbon\Carbon::createFromTime($h, 0, 0, $calendarTz)->format('g A') }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                <form
                    wire:submit="saveCoachRate"
                    class="space-y-4 rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900/80"
                >
                    <div>
                        <h2 class="font-display text-base font-bold text-zinc-900 dark:text-white">Your coaching rate</h2>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            Preview: <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $ratePreviewFormatted }}</span>
                            per hour (before saving, reflects the numbers below).
                        </p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label
                                class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                                for="coach-schedule-rate"
                            >
                                Hourly rate (whole units — no cents)
                            </label>
                            <input
                                id="coach-schedule-rate"
                                type="number"
                                min="0"
                                max="500000"
                                wire:model.live="hourlyRatePesos"
                                class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                            />
                            @error('hourlyRatePesos')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label
                                class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                                for="coach-schedule-ccy"
                            >
                                Currency
                            </label>
                            <select
                                id="coach-schedule-ccy"
                                wire:model.live="currency"
                                class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                            >
                                <option value="PHP">PHP</option>
                                <option value="USD">USD</option>
                            </select>
                            @error('currency')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <button
                        type="submit"
                        class="inline-flex rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-bold text-white shadow transition hover:bg-violet-500 dark:bg-violet-500 dark:hover:bg-violet-400"
                    >
                        Save rate
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
