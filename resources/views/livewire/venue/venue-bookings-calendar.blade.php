<div class="mx-auto max-w-5xl space-y-6">
    <div>
        <p class="text-sm text-zinc-600 dark:text-zinc-400">
            Month view of all bookings at
            <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $courtClient->name }}</span>
            . Select a slot to open the booking summary.
        </p>
    </div>

    @include('partials.bookings-calendar-month', [
        'weeks' => $weeks,
        'monthLabel' => $monthLabel,
        'tz' => $tz,
        'calendarContext' => $calendarContext,
    ])
</div>
