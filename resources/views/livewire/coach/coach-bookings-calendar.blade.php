<div class="mx-auto max-w-5xl space-y-6">
    <div>
        <p class="text-sm text-zinc-600 dark:text-zinc-400">
            Court bookings where you are listed as the coach. Open any entry for a quick summary.
        </p>
    </div>

    @include('partials.bookings-calendar-month', [
        'weeks' => $weeks,
        'monthLabel' => $monthLabel,
        'tz' => $tz,
        'calendarContext' => $calendarContext,
    ])
</div>
