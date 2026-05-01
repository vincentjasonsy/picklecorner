@php
    use App\Models\Booking;
    use App\Support\Money;

    $tz = config('app.timezone');
@endphp

<div class="space-y-8">
    <div class="flex flex-col gap-4 lg:flex-row lg:flex-wrap lg:items-end lg:justify-between">
        <div>
            <h1 class="font-display text-2xl font-bold text-zinc-900 dark:text-white">Booking history</h1>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                <span class="font-medium text-zinc-800 dark:text-zinc-200">
                    {{ $rangeStart->timezone($tz)->isoFormat('MMM D, YYYY') }} – {{ $rangeEnd->timezone($tz)->isoFormat('MMM D, YYYY') }}
                </span>
                · {{ number_format($rangeBookingCount) }} booking{{ $rangeBookingCount === 1 ? '' : 's' }}
                <span class="text-zinc-500">· app timezone {{ $tz }} · max {{ $maxHistoryRangeDays }} days</span>
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button
                type="button"
                wire:click="presetThisWeek"
                class="rounded-lg border border-zinc-200 bg-white px-3 py-2 text-xs font-semibold text-zinc-700 shadow-sm hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800"
            >
                This week
            </button>
            <button
                type="button"
                wire:click="presetLast7Days"
                class="rounded-lg border border-zinc-200 bg-white px-3 py-2 text-xs font-semibold text-zinc-700 shadow-sm hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800"
            >
                Last 7 days
            </button>
            <button
                type="button"
                wire:click="presetLast30Days"
                class="rounded-lg border border-zinc-200 bg-white px-3 py-2 text-xs font-semibold text-zinc-700 shadow-sm hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800"
            >
                Last 30 days
            </button>
            <button
                type="button"
                wire:click="presetThisMonth"
                class="rounded-lg border border-zinc-200 bg-white px-3 py-2 text-xs font-semibold text-zinc-700 shadow-sm hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800"
            >
                This month
            </button>
            <button
                type="button"
                wire:click="shiftCalendarWeek(-1)"
                class="rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-700 shadow-sm hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800"
            >
                ← Prev week
            </button>
            <button
                type="button"
                wire:click="shiftCalendarWeek(1)"
                class="rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-700 shadow-sm hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800"
            >
                Next week →
            </button>
            <a
                href="{{ route('admin.reports.export.bookings', ['from' => $rangeStart->toDateString(), 'to' => $rangeEnd->toDateString()]) }}"
                class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600"
            >
                Export range (CSV)
            </a>
        </div>
    </div>

    <div class="flex flex-col gap-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900 sm:flex-row sm:items-end">
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                From
            </label>
            <input
                type="date"
                wire:model.live="from"
                class="mt-1 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
            />
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                To
            </label>
            <input
                type="date"
                wire:model.live="to"
                class="mt-1 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
            />
        </div>
        <p class="text-xs text-zinc-500 dark:text-zinc-400 sm:pb-2">
            Adjusting dates clamps to a maximum window of {{ $maxHistoryRangeDays }} days.
        </p>
    </div>

    <div class="max-w-md">
        <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
            Venue filter
        </label>
        <select
            wire:model.live="venue"
            class="mt-1 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
        >
            <option value="">All venues</option>
            @foreach ($venues as $v)
                <option value="{{ $v->id }}">{{ $v->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="space-y-6">
        @foreach ($days as $dayKey => $dayBookings)
            @php
                $dayCarbon = \Carbon\Carbon::parse($dayKey, $tz)->startOfDay();
            @endphp
            <section
                class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900"
                wire:key="day-{{ $dayKey }}"
            >
                <div class="border-b border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-800 dark:bg-zinc-800/50">
                    <h2 class="font-display text-base font-bold text-zinc-900 dark:text-white">
                        {{ $dayCarbon->isoFormat('dddd, MMM D') }}
                    </h2>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        {{ $dayBookings->count() }} slot{{ $dayBookings->count() === 1 ? '' : 's' }}
                    </p>
                </div>
                @if ($dayBookings->isEmpty())
                    <p class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">No bookings.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                            <thead class="bg-zinc-50/80 dark:bg-zinc-800/30">
                                <tr>
                                    <th class="px-4 py-2 text-left font-semibold text-zinc-700 dark:text-zinc-300">
                                        Time
                                    </th>
                                    <th class="px-4 py-2 text-left font-semibold text-zinc-700 dark:text-zinc-300">
                                        Venue
                                    </th>
                                    <th class="px-4 py-2 text-left font-semibold text-zinc-700 dark:text-zinc-300">
                                        Court
                                    </th>
                                    <th class="px-4 py-2 text-left font-semibold text-zinc-700 dark:text-zinc-300">
                                        Guest
                                    </th>
                                    <th class="px-4 py-2 text-left font-semibold text-zinc-700 dark:text-zinc-300">
                                        Status
                                    </th>
                                    <th class="px-4 py-2 text-right font-semibold text-zinc-700 dark:text-zinc-300">
                                        Amount
                                    </th>
                                    <th class="px-4 py-2 text-right font-semibold text-zinc-700 dark:text-zinc-300">
                                        Conv. fee
                                    </th>
                                    <th class="px-4 py-2 text-right font-semibold text-zinc-700 dark:text-zinc-300"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                                @foreach ($dayBookings as $b)
                                    <tr wire:key="b-{{ $b->id }}">
                                        <td class="whitespace-nowrap px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                            {{ $b->starts_at?->timezone($tz)->format('g:i A') }}
                                            <span class="text-zinc-400">–</span>
                                            {{ $b->ends_at?->timezone($tz)->format('g:i A') }}
                                        </td>
                                        <td class="max-w-[10rem] truncate px-4 py-3 text-zinc-800 dark:text-zinc-200">
                                            {{ $b->courtClient?->name ?? '—' }}
                                        </td>
                                        <td class="max-w-[8rem] truncate px-4 py-3 text-zinc-800 dark:text-zinc-200">
                                            {{ $b->court?->name ?? '—' }}
                                        </td>
                                        <td class="max-w-[12rem] px-4 py-3 text-zinc-800 dark:text-zinc-200">
                                            <span class="block truncate font-medium">{{ $b->user?->name ?? '—' }}</span>
                                            @if ($b->user?->email)
                                                <span class="block truncate text-xs text-zinc-500">{{ $b->user->email }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            <span
                                                class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $this->statusBadgeClasses($b->status) }}"
                                            >
                                                {{ Booking::statusDisplayLabel($b->status) }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right text-zinc-600 dark:text-zinc-400">
                                            {{ Money::formatMinor($b->amount_cents, $b->currency) }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right text-zinc-600 dark:text-zinc-400">
                                            {{ Money::formatMinor((int) ($b->platform_booking_fee_cents ?? 0), $b->currency) }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right">
                                            <a
                                                href="{{ route('admin.bookings.show', $b) }}"
                                                wire:navigate
                                                class="text-sm font-semibold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400"
                                            >
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        @endforeach
    </div>
</div>
