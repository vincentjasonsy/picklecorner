<?php

use App\Models\Booking;
use App\Models\CourtClient;
use App\Services\BookingReporting;
use App\Support\Money;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::admin'), Title('Reports')] class extends Component
{
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
        $base = Booking::query();

        return [
            'all_time' => (clone $base)->countingTowardRevenue()->count(),
            'all_time_revenue_cents' => BookingReporting::coalescedRevenueSum($base),
            'all_time_convenience_fee_cents' => BookingReporting::coalescedPlatformBookingFeeSum($base),
            'this_month' => (clone $base)
                ->countingTowardRevenue()
                ->where('starts_at', '>=', $this->monthWindow['start'])
                ->where('starts_at', '<=', $this->monthWindow['end'])
                ->count(),
            'this_month_revenue_cents' => BookingReporting::coalescedRevenueSum(
                Booking::query()
                    ->where('starts_at', '>=', $this->monthWindow['start'])
                    ->where('starts_at', '<=', $this->monthWindow['end']),
            ),
            'this_month_convenience_fee_cents' => BookingReporting::coalescedPlatformBookingFeeSum(
                Booking::query()
                    ->where('starts_at', '>=', $this->monthWindow['start'])
                    ->where('starts_at', '<=', $this->monthWindow['end']),
            ),
            'cancelled' => (clone $base)->where('status', Booking::STATUS_CANCELLED)->count(),
            'pending_approval' => (clone $base)->where('status', Booking::STATUS_PENDING_APPROVAL)->count(),
            'denied' => (clone $base)->where('status', Booking::STATUS_DENIED)->count(),
        ];
    }

    #[Computed]
    public function byClient()
    {
        $monthStart = $this->monthWindow['start'];
        $monthEnd = $this->monthWindow['end'];

        $revenueMonth = Booking::query()
            ->countingTowardRevenue()
            ->where('starts_at', '>=', $monthStart)
            ->where('starts_at', '<=', $monthEnd)
            ->selectRaw('court_client_id, COALESCE(SUM(COALESCE(amount_cents, 0)), 0) as rev')
            ->groupBy('court_client_id')
            ->pluck('rev', 'court_client_id');

        $feeMonth = Booking::query()
            ->countingTowardRevenue()
            ->where('starts_at', '>=', $monthStart)
            ->where('starts_at', '<=', $monthEnd)
            ->selectRaw('court_client_id, COALESCE(SUM(COALESCE(platform_booking_fee_cents, 0)), 0) as fee')
            ->groupBy('court_client_id')
            ->pluck('fee', 'court_client_id');

        return CourtClient::query()
            ->withCount([
                'bookings as bookings_all' => fn ($q) => $q->countingTowardRevenue(),
                'bookings as bookings_month' => fn ($q) => $q
                    ->countingTowardRevenue()
                    ->where('starts_at', '>=', $monthStart)
                    ->where('starts_at', '<=', $monthEnd),
            ])
            ->orderBy('name')
            ->get()
            ->each(function (CourtClient $cc) use ($revenueMonth, $feeMonth): void {
                $cc->revenue_month_cents = (int) ($revenueMonth[$cc->id] ?? 0);
                $cc->convenience_fee_month_cents = (int) ($feeMonth[$cc->id] ?? 0);
            });
    }

    #[Computed]
    public function lastSixMonths(): array
    {
        return BookingReporting::lastNMonthsVolume(fn ($q) => null, 6);
    }

    #[Computed]
    public function lastSixMonthsRevenue(): array
    {
        return BookingReporting::lastNMonthsRevenue(fn ($q) => null, 6);
    }

    #[Computed]
    public function statusBreakdown()
    {
        return BookingReporting::statusCounts(fn ($q) => null);
    }

    #[Computed]
    public function topBookersThisMonth()
    {
        $w = $this->monthWindow;

        return BookingReporting::topBookers(fn ($q) => null, $w['start'], $w['end'], 10);
    }

    #[Computed]
    public function topCourtsThisMonth()
    {
        $w = $this->monthWindow;

        return BookingReporting::topCourts(fn ($q) => null, $w['start'], $w['end'], 10);
    }

    #[Computed]
    public function recentBookings()
    {
        return Booking::query()
            ->with(['courtClient:id,name', 'court:id,name', 'user:id,name,email'])
            ->orderByDesc('starts_at')
            ->limit(30)
            ->get();
    }
};
?>

@php
    $mw = $this->monthWindow;
    $maxMonth = max(collect($this->lastSixMonths)->max('count') ?? 0, 1);
    $tz = config('app.timezone', 'UTC');
@endphp

<div class="space-y-10">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <p class="max-w-3xl text-sm text-zinc-600 dark:text-zinc-400">
            All figures use the app timezone (<strong>{{ $tz }}</strong>). <strong>Revenue</strong> is court rental (and
            related line items) for <strong>confirmed</strong> and <strong>completed</strong> bookings;
            <strong>convenience fee</strong> is the platform fee stored per booking when applicable. Missing amounts
            count as zero. <strong>Booking counts</strong> in KPIs use the same revenue filter unless noted.
        </p>
        <div class="flex flex-wrap gap-2">
            <a
                href="{{ route('admin.bookings.index') }}"
                wire:navigate
                class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-900 hover:bg-emerald-100 dark:border-emerald-900/40 dark:bg-emerald-950/40 dark:text-emerald-200 dark:hover:bg-emerald-950/60"
            >
                Weekly booking history
            </a>
            <a
                href="{{ route('admin.reports.booking-channels') }}"
                wire:navigate
                class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-900 hover:bg-emerald-100 dark:border-emerald-900/40 dark:bg-emerald-950/40 dark:text-emerald-200 dark:hover:bg-emerald-950/60"
            >
                Manual &amp; automated report
            </a>
            <a
                href="{{ route('admin.reports.export.bookings', ['from' => $mw['start']->toDateString(), 'to' => $mw['end']->toDateString()]) }}"
                class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-xs font-semibold text-zinc-800 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
            >
                Export CSV — this month
            </a>
            <a
                href="{{ route('admin.reports.export.bookings', ['from' => now($tz)->subDays(90)->toDateString(), 'to' => now($tz)->toDateString()]) }}"
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
                {{ Money::formatMinor($this->totals['all_time_revenue_cents']) }}
            </p>
            <p class="mt-1 text-xs text-zinc-500">Court-side amounts · same scope as above</p>
            <p class="mt-1 text-xs text-zinc-500">
                Convenience fees:
                <span class="font-medium text-zinc-700 dark:text-zinc-300">{{
                    Money::formatMinor($this->totals['all_time_convenience_fee_cents'])
                }}</span>
            </p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500">This calendar month</p>
            <p class="mt-2 font-display text-2xl font-bold text-zinc-900 dark:text-white">
                {{ number_format($this->totals['this_month']) }}
                <span class="text-sm font-normal text-zinc-500">bookings</span>
            </p>
            <p class="mt-1 text-xs text-zinc-500">
                {{ Money::formatMinor($this->totals['this_month_revenue_cents']) }} revenue ·
                {{ Money::formatMinor($this->totals['this_month_convenience_fee_cents']) }} convenience fees
            </p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Pipeline / churn</p>
            <p class="mt-2 text-sm text-zinc-700 dark:text-zinc-300">
                <span class="font-semibold">{{ number_format($this->totals['pending_approval']) }}</span> pending
            </p>
            <p class="mt-1 text-sm text-zinc-700 dark:text-zinc-300">
                <span class="font-semibold">{{ number_format($this->totals['cancelled']) }}</span> cancelled ·
                <span class="font-semibold">{{ number_format($this->totals['denied']) }}</span> denied
            </p>
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

    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
        <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">By venue (this month)</h2>
        <p class="mt-1 text-xs text-zinc-500">
            {{ $mw['start']->isoFormat('MMM D') }} – {{ $mw['end']->isoFormat('MMM D, YYYY') }} · revenue = court-side
            confirmed + completed · convenience fee column = platform fee totals
        </p>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                        <th class="py-2 pr-4 font-semibold text-zinc-700 dark:text-zinc-300">Venue</th>
                        <th class="py-2 pr-4 font-semibold text-zinc-700 dark:text-zinc-300">Bookings</th>
                        <th class="py-2 pr-4 font-semibold text-zinc-700 dark:text-zinc-300">Revenue</th>
                        <th class="py-2 font-semibold text-zinc-700 dark:text-zinc-300">Conv. fee</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->byClient as $row)
                        <tr class="border-b border-zinc-100 dark:border-zinc-800">
                            <td class="py-2 pr-4 text-zinc-900 dark:text-zinc-100">{{ $row->name }}</td>
                            <td class="py-2 pr-4 text-zinc-600 dark:text-zinc-400">
                                {{ number_format($row->bookings_month) }}
                                <span class="text-xs text-zinc-400">/ {{ number_format($row->bookings_all) }} all-time</span>
                            </td>
                            <td class="py-2 text-zinc-600 dark:text-zinc-400">
                                {{ Money::formatMinor((int) ($row->revenue_month_cents ?? 0)) }}
                            </td>
                            <td class="py-2 text-zinc-600 dark:text-zinc-400">
                                {{ Money::formatMinor((int) ($row->convenience_fee_month_cents ?? 0)) }}
                            </td>
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
                By confirmed + completed bookings this month. Amounts: court revenue · convenience fee.
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
                            {{ Money::formatMinor($row->revenue_cents) }}
                            · fee {{ Money::formatMinor($row->convenience_fee_cents ?? 0) }}
                        </span>
                    </li>
                @empty
                    <li class="text-sm text-zinc-500">No data this month.</li>
                @endforelse
            </ul>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Top courts (this month)</h2>
            <p class="mt-1 text-xs text-zinc-500">Confirmed + completed by court.</p>
            <ul class="mt-4 space-y-2 text-sm">
                @forelse ($this->topCourtsThisMonth as $row)
                    <li class="flex justify-between gap-2 border-b border-zinc-100 pb-2 dark:border-zinc-800">
                        <span class="truncate font-medium text-zinc-900 dark:text-zinc-100">
                            {{ $row->court->name }}
                            <span class="block truncate text-xs font-normal text-zinc-500">
                                {{ $row->court->courtClient?->name ?? '—' }}
                            </span>
                        </span>
                        <span class="shrink-0 text-zinc-600 dark:text-zinc-400">
                            {{ number_format($row->booking_count) }}
                        </span>
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
            Volume and amounts (confirmed + completed, by booking start). Revenue = court-side; conv. fee = platform fee.
        </p>
        <div class="mt-6 flex h-40 items-end gap-2">
            @foreach ($this->lastSixMonths as $m)
                <div class="flex flex-1 flex-col items-center gap-2">
                    <div
                        class="w-full max-w-[3rem] rounded-t bg-emerald-500/90 dark:bg-emerald-600"
                        style="height: {{ max(8, ($m['count'] / $maxMonth) * 100) }}%"
                        title="{{ $m['count'] }} bookings"
                    ></div>
                    <span class="text-center text-[10px] text-zinc-500">{{ $m['label'] }}</span>
                </div>
            @endforeach
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
                            <td class="py-2 pr-4 text-zinc-600 dark:text-zinc-400">{{ Money::formatMinor($rev) }}</td>
                            <td class="py-2 text-zinc-600 dark:text-zinc-400">{{ Money::formatMinor($fee) }}</td>
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
                        <th class="py-2 pr-3 font-semibold text-zinc-700 dark:text-zinc-300">Venue</th>
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
                        <tr class="border-b border-zinc-100 dark:border-zinc-800" wire:key="rb-{{ $b->id }}">
                            <td class="whitespace-nowrap py-2 pr-3 text-zinc-600 dark:text-zinc-400">
                                {{ $b->starts_at?->timezone($tz)->isoFormat('MMM D, h:mm a') }}
                            </td>
                            <td class="py-2 pr-3 text-zinc-800 dark:text-zinc-200">{{ $b->courtClient?->name ?? '—' }}</td>
                            <td class="py-2 pr-3 text-zinc-800 dark:text-zinc-200">{{ $b->court?->name ?? '—' }}</td>
                            <td class="py-2 pr-3 text-zinc-800 dark:text-zinc-200">
                                <span class="block truncate max-w-[10rem]">{{ $b->user?->name ?? '—' }}</span>
                            </td>
                            <td class="py-2 pr-3 text-zinc-600 dark:text-zinc-400">
                                {{ \App\Models\Booking::statusDisplayLabel($b->status) }}
                            </td>
                            <td class="whitespace-nowrap py-2 text-zinc-600 dark:text-zinc-400">
                                {{ Money::formatMinor($b->amount_cents, $b->currency) }}
                            </td>
                            <td class="whitespace-nowrap py-2 text-zinc-600 dark:text-zinc-400">
                                {{ Money::formatMinor((int) ($b->platform_booking_fee_cents ?? 0), $b->currency) }}
                            </td>
                            <td class="whitespace-nowrap py-2 text-right">
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
    </div>
</div>
