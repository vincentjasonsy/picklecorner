<div class="mx-auto max-w-5xl space-y-6">
    <div>
        <p class="text-sm text-stone-600 dark:text-stone-400">
            Bookings you submitted from the front desk at
            <span class="font-semibold text-stone-800 dark:text-stone-200">{{ $venue->name }}</span>
            . Tap a block for details.
        </p>
    </div>

    @include('partials.bookings-calendar-month', [
        'weeks' => $weeks,
        'monthLabel' => $monthLabel,
        'tz' => $tz,
        'calendarContext' => $calendarContext,
    ])
</div>
