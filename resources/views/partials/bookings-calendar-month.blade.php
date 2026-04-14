@php
    use App\Models\Booking;

    $bookingUrl = match ($calendarContext) {
        'venue' => fn (Booking $b): string => route('venue.bookings.show', $b),
        'desk' => fn (Booking $b): string => route('desk.bookings.show', $b),
        'coach' => fn (Booking $b): string => route('account.coach.bookings.show', $b),
        default => fn (Booking $b): string => '#',
    };
@endphp

<div
    class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
>
    <div
        class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 px-4 py-3 dark:border-zinc-800 sm:px-5"
    >
        <button
            type="button"
            wire:click="shiftCalendarMonth(-1)"
            class="rounded-lg border border-zinc-200 px-3 py-1.5 text-sm font-semibold text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800"
        >
            Previous
        </button>
        <h2 class="text-center text-base font-bold text-zinc-900 dark:text-white sm:text-lg">
            {{ $monthLabel }}
        </h2>
        <button
            type="button"
            wire:click="shiftCalendarMonth(1)"
            class="rounded-lg border border-zinc-200 px-3 py-1.5 text-sm font-semibold text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800"
        >
            Next
        </button>
    </div>

    <div class="overflow-x-auto">
        <div class="min-w-[640px]">
            <div
                class="grid grid-cols-7 border-b border-zinc-200 bg-zinc-50 text-center text-[11px] font-bold uppercase tracking-wider text-zinc-500 dark:border-zinc-800 dark:bg-zinc-950/80 dark:text-zinc-400"
            >
                <div class="px-1 py-2">Mon</div>
                <div class="px-1 py-2">Tue</div>
                <div class="px-1 py-2">Wed</div>
                <div class="px-1 py-2">Thu</div>
                <div class="px-1 py-2">Fri</div>
                <div class="px-1 py-2">Sat</div>
                <div class="px-1 py-2">Sun</div>
            </div>

            @foreach ($weeks as $week)
                <div
                    class="grid grid-cols-7 divide-x divide-zinc-200 border-b border-zinc-200 last:border-b-0 dark:divide-zinc-800 dark:border-zinc-800"
                    wire:key="cal-w-{{ $week[0]['date']->format('Y-m-d') }}"
                >
                    @foreach ($week as $day)
                        <div
                            wire:key="cal-d-{{ $day['date']->format('Y-m-d') }}"
                            class="min-h-[7.5rem] bg-white p-1.5 align-top dark:bg-zinc-900 sm:min-h-[8.5rem] sm:p-2 {{ $day['in_month'] ? '' : 'bg-zinc-50/80 dark:bg-zinc-950/40' }}"
                        >
                            <p
                                class="mb-1 text-right text-xs font-semibold tabular-nums {{ $day['in_month'] ? 'text-zinc-800 dark:text-zinc-100' : 'text-zinc-400 dark:text-zinc-500' }}"
                            >
                                {{ $day['date']->timezone($tz)->day }}
                            </p>
                            <ul class="space-y-1">
                                @foreach ($day['bookings'] as $b)
                                    <li wire:key="cal-b-{{ $b->id }}">
                                        <a
                                            href="{{ $bookingUrl($b) }}"
                                            wire:navigate
                                            class="block truncate rounded-md border border-zinc-200/80 bg-zinc-50 px-1 py-0.5 text-[10px] font-semibold leading-tight text-zinc-800 transition hover:border-emerald-300 hover:bg-emerald-50/90 dark:border-zinc-700 dark:bg-zinc-800/80 dark:text-zinc-100 dark:hover:border-emerald-700 dark:hover:bg-emerald-950/40 sm:text-xs"
                                            title="{{ $b->court?->name ?? 'Court' }} · {{ Booking::statusDisplayLabel($b->status) }}"
                                        >
                                            <span class="tabular-nums">
                                                {{ $b->starts_at?->timezone($tz)->format('g:i') }}
                                            </span>
                                            <span class="text-zinc-500 dark:text-zinc-400">·</span>
                                            {{ $b->court?->name ?? 'Court' }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
</div>
