{{--
    Closure calendar body (whole-venue closed days). Split out so IDE parsers
    do not mis-parse nested foreach / php / class directives in the parent template.
--}}
<div class="min-w-[520px]">
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
    @foreach ($closureMonthWeeks as $week)
        @php
            $weekKey = isset($week[0]['date'])
                ? $week[0]['date']->format('Y-m-d')
                : 'closure-w';
        @endphp
        <div
            class="grid grid-cols-7 divide-x divide-zinc-200 border-b border-zinc-200 last:border-b-0 dark:divide-zinc-800 dark:border-zinc-800"
            wire:key="closure-w-{{ $weekKey }}"
        >
            @foreach ($week as $day)
                @php
                    $closureYmd = $day['date']->format('Y-m-d');
                    $closureIsClosed = isset($closureMonthClosedLookup[$closureYmd]);
                    $closureIsAvailDate = $closureYmd === $availabilityCalendarDate;
                @endphp
                <div class="p-1 sm:p-1.5" wire:key="closure-d-{{ $closureYmd }}">
                    <button
                        type="button"
                        wire:click="toggleVenueClosedOnCalendarDay('{{ $closureYmd }}')"
                        @class([
                            'flex min-h-[3.25rem] w-full flex-col items-center justify-center rounded-lg border px-1 py-1.5 text-center text-xs font-semibold transition sm:min-h-[3.5rem]',
                            'ring-2 ring-emerald-500 ring-offset-1 ring-offset-white dark:ring-emerald-400 dark:ring-offset-zinc-900' => $closureIsAvailDate,
                            'border-zinc-200 bg-zinc-50/90 text-zinc-400 dark:border-zinc-700 dark:bg-zinc-950/50 dark:text-zinc-500' => ! $day['in_month'],
                            'border-zinc-200 bg-white text-zinc-800 hover:border-emerald-400/60 hover:bg-emerald-50/70 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-emerald-950/25' => $day['in_month'] && ! $closureIsClosed,
                            'border-red-300 bg-red-50 text-red-900 dark:border-red-900/60 dark:bg-red-950/35 dark:text-red-100' => $day['in_month'] && $closureIsClosed,
                        ])
                    >
                        <span class="tabular-nums">{{ $day['date']->timezone($closureCalendarTz)->day }}</span>
                        @if ($day['in_month'] && $closureIsClosed)
                            <span class="mt-0.5 text-[10px] font-bold uppercase tracking-wide text-red-700 dark:text-red-300">
                                Closed
                            </span>
                        @elseif ($day['in_month'])
                            <span class="mt-0.5 text-[10px] font-medium text-zinc-500 dark:text-zinc-400">Open</span>
                        @endif
                    </button>
                </div>
            @endforeach
        </div>
    @endforeach
</div>
