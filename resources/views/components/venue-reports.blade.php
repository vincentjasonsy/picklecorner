<?php

use App\Models\Booking;
use App\Services\BookingReporting;
use App\Support\Money;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::venue-portal'), Title('Reports')] class extends Component
{
    #[Computed]
    public function courtClient()
    {
        return auth()->user()->administeredCourtClient;
    }

    #[Computed]
    public function monthWindow(): array
    {
        $start = BookingReporting::monthStart();
        $end = $start->copy()->endOfMonth();

        return ['start' => $start, 'end' => $end];
    }

    #[Computed]
    public function totals(): array
    {
        $c = $this->courtClient;
        if (! $c) {
            return [
                'all_time' => 0,
                'all_time_revenue_cents' => 0,
                'all_time_convenience_fee_cents' => 0,
                'this_month' => 0,
                'this_month_revenue_cents' => 0,
                'this_month_convenience_fee_cents' => 0,
                'cancelled' => 0,
                'pending_approval' => 0,
                'denied' => 0,
            ];
        }

        $base = Booking::query()->where('court_client_id', $c->id);
        $mw = $this->monthWindow;

        return [
            'all_time' => (clone $base)->countingTowardRevenue()->count(),
            'all_time_revenue_cents' => BookingReporting::coalescedRevenueSum($base),
            'all_time_convenience_fee_cents' => BookingReporting::coalescedPlatformBookingFeeSum($base),
            'this_month' => (clone $base)
                ->countingTowardRevenue()
                ->where('starts_at', '>=', $mw['start'])
                ->where('starts_at', '<=', $mw['end'])
                ->count(),
            'this_month_revenue_cents' => BookingReporting::coalescedRevenueSum(
                Booking::query()
                    ->where('court_client_id', $c->id)
                    ->where('starts_at', '>=', $mw['start'])
                    ->where('starts_at', '<=', $mw['end']),
            ),
            'this_month_convenience_fee_cents' => BookingReporting::coalescedPlatformBookingFeeSum(
                Booking::query()
                    ->where('court_client_id', $c->id)
                    ->where('starts_at', '>=', $mw['start'])
                    ->where('starts_at', '<=', $mw['end']),
            ),
            'cancelled' => (clone $base)->where('status', Booking::STATUS_CANCELLED)->count(),
            'pending_approval' => (clone $base)->where('status', Booking::STATUS_PENDING_APPROVAL)->count(),
            'denied' => (clone $base)->where('status', Booking::STATUS_DENIED)->count(),
        ];
    }

    #[Computed]
    public function lastSixMonths(): array
    {
        $c = $this->courtClient;
        if (! $c) {
            return [];
        }

        return BookingReporting::lastNMonthsVolume(
            fn ($q) => $q->where('court_client_id', $c->id),
            6,
        );
    }

    #[Computed]
    public function lastSixMonthsRevenue(): array
    {
        $c = $this->courtClient;
        if (! $c) {
            return [];
        }

        return BookingReporting::lastNMonthsRevenue(
            fn ($q) => $q->where('court_client_id', $c->id),
            6,
        );
    }

    #[Computed]
    public function statusBreakdown()
    {
        $c = $this->courtClient;
        if (! $c) {
            return collect();
        }

        return BookingReporting::statusCounts(fn ($q) => $q->where('court_client_id', $c->id));
    }

    #[Computed]
    public function topBookersThisMonth()
    {
        $c = $this->courtClient;
        if (! $c) {
            return collect();
        }
        $w = $this->monthWindow;

        return BookingReporting::topBookers(
            fn ($q) => $q->where('court_client_id', $c->id),
            $w['start'],
            $w['end'],
            10,
        );
    }

    #[Computed]
    public function topCourtsThisMonth()
    {
        $c = $this->courtClient;
        if (! $c) {
            return collect();
        }
        $w = $this->monthWindow;

        return BookingReporting::topCourts(
            fn ($q) => $q->where('court_client_id', $c->id),
            $w['start'],
            $w['end'],
            10,
        );
    }

    #[Computed]
    public function recentBookings()
    {
        $c = $this->courtClient;
        if (! $c) {
            return collect();
        }

        return Booking::query()
            ->where('court_client_id', $c->id)
            ->with(['court:id,name', 'user:id,name,email'])
            ->orderByDesc('starts_at')
            ->limit(30)
            ->get();
    }
};
?>

@php
    $vc = $this->courtClient;
    $mw = $this->monthWindow;
    $maxMonth = max(collect($this->lastSixMonths)->max('count') ?? 0, 1);
    $tz = config('app.timezone', 'UTC');
@endphp

<div class="space-y-10">
    @if (! $vc)
        <p class="text-sm text-red-600 dark:text-red-400">No venue is assigned to your account.</p>
    @else
        <div class="flex flex-wrap items-end justify-between gap-4">
            <p class="max-w-3xl text-sm text-zinc-600 dark:text-zinc-400">
                Reporting for <strong>{{ $vc->name }}</strong>. Timezone: <strong>{{ $tz }}</strong>. Revenue is
                court-side amounts for <strong>confirmed</strong> and <strong>completed</strong> bookings;
                <strong>convenience fee</strong> is the platform fee when shown. Missing amounts count as zero.
            </p>
            <div class="flex flex-wrap gap-2">
                <a
                    href="{{ route('venue.bookings.history') }}"
                    wire:navigate
                    class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-900 hover:bg-emerald-100 dark:border-emerald-900/40 dark:bg-emerald-950/40 dark:text-emerald-200 dark:hover:bg-emerald-950/60"
                >
                    Booking history
                </a>
                <a
                    href="{{ route('venue.reports.export.bookings', ['from' => $mw['start']->toDateString(), 'to' => $mw['end']->toDateString()]) }}"
                    class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-xs font-semibold text-zinc-800 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
                >
                    Export CSV — this month
                </a>
                <a
                    href="{{ route('venue.reports.export.bookings', ['from' => now($tz)->subDays(90)->toDateString(), 'to' => now($tz)->toDateString()]) }}"
                    class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-xs font-semibold text-zinc-800 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
                >
                    Export CSV — last 90 days
                </a>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Bookings (all time)</p>
                <p class="mt-2 font-display text-2xl font-bold text-zinc-900 dark:text-white">
                    {{ number_format($this->totals['all_time']) }}
                </p>
                <p class="mt-1 text-xs text-zinc-500">Confirmed + completed</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Revenue (all time)</p>
                <p class="mt-2 font-display text-2xl font-bold text-zinc-900 dark:text-white">
                    {{ Money::formatMinor($this->totals['all_time_revenue_cents'], $vc->currency) }}
                </p>
                <p class="mt-1 text-xs text-zinc-500">
                    Conv. fees
                    {{ Money::formatMinor($this->totals['all_time_convenience_fee_cents'], $vc->currency) }}
                </p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500">This calendar month</p>
                <p class="mt-2 font-display text-2xl font-bold text-zinc-900 dark:text-white">
                    {{ number_format($this->totals['this_month']) }}
                    <span class="text-sm font-normal text-zinc-500">bookings</span>
                </p>
                <p class="mt-1 text-xs text-zinc-500">
                    {{ Money::formatMinor($this->totals['this_month_revenue_cents'], $vc->currency) }} revenue ·
                    {{ Money::formatMinor($this->totals['this_month_convenience_fee_cents'], $vc->currency) }} conv. fees
                </p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Desk / churn</p>
                <p class="mt-2 text-sm text-zinc-700 dark:text-zinc-300">
                    <span class="font-semibold">{{ number_format($this->totals['pending_approval']) }}</span> pending
                </p>
                <p class="mt-1 text-sm text-zinc-700 dark:text-zinc-300">
                    <span class="font-semibold">{{ number_format($this->totals['cancelled']) }}</span> cancelled ·
                    <span class="font-semibold">{{ number_format($this->totals['denied']) }}</span> denied
                </p>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Convenience fees (this month)</p>
                <p class="mt-2 font-display text-2xl font-bold text-zinc-900 dark:text-white">
                    {{ Money::formatMinor($this->totals['this_month_convenience_fee_cents'], $vc->currency) }}
                </p>
                <p class="mt-1 text-xs text-zinc-500">Platform fee portion on automated bookings</p>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">By status (all time)</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                            <th class="py-2 pr-4 font-semibold text-zinc-700 dark:text-zinc-300">Status</th>
                            <th class="py-2 font-semibold text-zinc-700 dark:text-zinc-300">Bookings</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->statusBreakdown as $row)
                            <tr class="border-b border-zinc-100 dark:border-zinc-800">
                                <td class="py-2 pr-4 text-zinc-900 dark:text-zinc-100">
                                    {{ \App\Models\Booking::statusDisplayLabel($row->status) }}
                                </td>
                                <td class="py-2 text-zinc-600 dark:text-zinc-400">{{ number_format($row->c) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Top guests (this month)</h2>
                <p class="mt-1 text-xs text-zinc-500">
                    Court revenue · convenience fee (confirmed + completed by booking start).
                </p>
                <ul class="mt-4 space-y-2 text-sm">
                    @forelse ($this->topBookersThisMonth as $row)
                        <li class="flex justify-between gap-2 border-b border-zinc-100 pb-2 dark:border-zinc-800">
                            <span class="truncate font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $row->user->name }}
                                <span class="block truncate text-xs font-normal text-zinc-500">{{ $row->user->email }}</span>
                            </span>
                            <span class="shrink-0 text-right text-zinc-600 dark:text-zinc-400">
                                {{ number_format($row->booking_count) }} ·
                                {{ Money::formatMinor($row->revenue_cents, $vc->currency) }}
                                · fee {{ Money::formatMinor($row->convenience_fee_cents ?? 0, $vc->currency) }}
                            </span>
                        </li>
                    @empty
                        <li class="text-sm text-zinc-500">No data this month.</li>
                    @endforelse
                </ul>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Top courts (this month)</h2>
                <ul class="mt-4 space-y-2 text-sm">
                    @forelse ($this->topCourtsThisMonth as $row)
                        <li class="flex justify-between gap-2 border-b border-zinc-100 pb-2 dark:border-zinc-800">
                            <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $row->court->name }}</span>
                            <span class="text-zinc-600 dark:text-zinc-400">{{ number_format($row->booking_count) }}</span>
                        </li>
                    @empty
                        <li class="text-sm text-zinc-500">No data this month.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Last 6 calendar months</h2>
            <p class="mt-1 text-xs text-zinc-500">
                Volume (confirmed + completed) by start time. Revenue = court-side; conv. fee = platform fee.
            </p>
            <div class="mt-6 flex h-40 items-end gap-2">
                @forelse ($this->lastSixMonths as $m)
                    <div class="flex flex-1 flex-col items-center gap-2">
                        <div
                            class="w-full max-w-[3rem] rounded-t bg-emerald-500/90 dark:bg-emerald-600"
                            style="height: {{ max(8, ($m['count'] / $maxMonth) * 100) }}%"
                            title="{{ $m['count'] }} bookings"
                        ></div>
                        <span class="text-[10px] text-zinc-500">{{ $m['label'] }}</span>
                    </div>
                @empty
                    <p class="text-sm text-zinc-500">No booking history yet.</p>
                @endforelse
            </div>
            <div class="mt-6 overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead>
                        <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                            <th class="py-2 pr-4 font-semibold text-zinc-700 dark:text-zinc-300">Month</th>
                            <th class="py-2 pr-4 font-semibold text-zinc-700 dark:text-zinc-300">Bookings</th>
                            <th class="py-2 pr-4 font-semibold text-zinc-700 dark:text-zinc-300">Revenue</th>
                            <th class="py-2 font-semibold text-zinc-700 dark:text-zinc-300">Conv. fee</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->lastSixMonths as $i => $m)
                            @php
                                $rev = $this->lastSixMonthsRevenue[$i]['revenue_cents'] ?? 0;
                                $fee = $this->lastSixMonthsRevenue[$i]['convenience_fee_cents'] ?? 0;
                            @endphp
                            <tr class="border-b border-zinc-100 dark:border-zinc-800">
                                <td class="py-2 pr-4 text-zinc-800 dark:text-zinc-200">{{ $m['label'] }}</td>
                                <td class="py-2 pr-4 text-zinc-600 dark:text-zinc-400">{{ number_format($m['count']) }}</td>
                                <td class="py-2 pr-4 text-zinc-600 dark:text-zinc-400">
                                    {{ Money::formatMinor($rev, $vc->currency) }}
                                </td>
                                <td class="py-2 text-zinc-600 dark:text-zinc-400">
                                    {{ Money::formatMinor($fee, $vc->currency) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Recent bookings</h2>
            <p class="mt-1 text-xs text-zinc-500">Latest 30 by scheduled start (all statuses).</p>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                            <th class="py-2 pr-3 font-semibold text-zinc-700 dark:text-zinc-300">When</th>
                            <th class="py-2 pr-3 font-semibold text-zinc-700 dark:text-zinc-300">Court</th>
                            <th class="py-2 pr-3 font-semibold text-zinc-700 dark:text-zinc-300">Guest</th>
                            <th class="py-2 pr-3 font-semibold text-zinc-700 dark:text-zinc-300">Status</th>
                            <th class="py-2 pr-3 font-semibold text-zinc-700 dark:text-zinc-300">Amount</th>
                            <th class="py-2 pr-3 font-semibold text-zinc-700 dark:text-zinc-300">Conv. fee</th>
                            <th class="py-2 font-semibold text-zinc-700 dark:text-zinc-300"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->recentBookings as $b)
                            <tr class="border-b border-zinc-100 dark:border-zinc-800" wire:key="vrb-{{ $b->id }}">
                                <td class="whitespace-nowrap py-2 pr-3 text-zinc-600 dark:text-zinc-400">
                                    {{ $b->starts_at?->timezone($tz)->isoFormat('MMM D, h:mm a') }}
                                </td>
                                <td class="py-2 pr-3 text-zinc-800 dark:text-zinc-200">{{ $b->court?->name ?? '—' }}</td>
                                <td class="py-2 pr-3 text-zinc-800 dark:text-zinc-200">
                                    <span class="block truncate max-w-[10rem]">{{ $b->user?->name ?? '—' }}</span>
                                </td>
                                <td class="py-2 pr-3 text-zinc-600 dark:text-zinc-400">
                                    {{ \App\Models\Booking::statusDisplayLabel($b->status) }}
                                </td>
                                <td class="whitespace-nowrap py-2 pr-3 text-zinc-600 dark:text-zinc-400">
                                    {{ Money::formatMinor($b->amount_cents, $b->currency ?? $vc->currency) }}
                                </td>
                                <td class="whitespace-nowrap py-2 pr-3 text-zinc-600 dark:text-zinc-400">
                                    {{ Money::formatMinor((int) ($b->platform_booking_fee_cents ?? 0), $b->currency ?? $vc->currency) }}
                                </td>
                                <td class="whitespace-nowrap py-2 text-right">
                                    <a
                                        href="{{ route('venue.bookings.show', $b) }}"
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
        </div>
    @endif
</div>
