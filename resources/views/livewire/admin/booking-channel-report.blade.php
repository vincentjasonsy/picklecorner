@php
    use App\Services\BookingCheckoutSnapshot;
    use App\Support\Money;
@endphp

<div class="space-y-8">
    <div>
        <a
            href="{{ route('admin.reports') }}"
            wire:navigate
            class="text-sm font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400"
        >
            ← Back to reports
        </a>
        <h1 class="mt-4 font-display text-2xl font-bold text-zinc-900 dark:text-white">Manual &amp; automated bookings</h1>
        <p class="mt-1 max-w-3xl text-sm text-zinc-600 dark:text-zinc-400">
            Compare <strong>manual</strong> (desk / admin grid) and <strong>automated</strong> (Book now member checkout)
            bookings. <strong>Convenience fee</strong> (platform booking fee) is stored per row — it usually applies to
            automated checkouts; manual rows are typically zero.
        </p>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <p class="font-display text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                Manual (desk / admin)
            </p>
            <p class="mt-2 font-display text-2xl font-bold tabular-nums text-zinc-900 dark:text-white">
                {{ number_format($manualSummary['booking_count']) }}
                <span class="text-base font-semibold text-zinc-500">bookings</span>
            </p>
            <dl class="mt-4 grid gap-2 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-zinc-600 dark:text-zinc-400">Court total</dt>
                    <dd class="font-medium tabular-nums text-zinc-900 dark:text-zinc-100">
                        {{ Money::formatMinor($manualSummary['amount_cents'], $displayCurrency) }}
                    </dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-zinc-600 dark:text-zinc-400">Convenience fee</dt>
                    <dd class="font-medium tabular-nums text-zinc-900 dark:text-zinc-100">
                        {{ Money::formatMinor($manualSummary['platform_fee_cents'], $displayCurrency) }}
                    </dd>
                </div>
            </dl>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50/80 p-5 dark:border-emerald-900/50 dark:bg-emerald-950/30">
            <p class="font-display text-xs font-bold uppercase tracking-wider text-emerald-800 dark:text-emerald-300">
                Automated (Book now)
            </p>
            <p class="mt-2 font-display text-2xl font-bold tabular-nums text-emerald-950 dark:text-emerald-100">
                {{ number_format($automatedSummary['booking_count']) }}
                <span class="text-base font-semibold text-emerald-700 dark:text-emerald-400">bookings</span>
            </p>
            <dl class="mt-4 grid gap-2 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-emerald-900 dark:text-emerald-200">Court total</dt>
                    <dd class="font-medium tabular-nums text-emerald-950 dark:text-emerald-50">
                        {{ Money::formatMinor($automatedSummary['amount_cents'], $displayCurrency) }}
                    </dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-emerald-900 dark:text-emerald-200">Convenience fee</dt>
                    <dd class="font-medium tabular-nums text-emerald-950 dark:text-emerald-50">
                        {{ Money::formatMinor($automatedSummary['platform_fee_cents'], $displayCurrency) }}
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    <div class="flex flex-col gap-4 lg:flex-row lg:flex-wrap lg:items-end lg:justify-between">
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
        </div>
        <p class="text-xs text-zinc-500 dark:text-zinc-400">
            Range max {{ $maxHistoryRangeDays }} days · timezone {{ $tz }}
        </p>
    </div>

    <div
        class="flex flex-col gap-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900 lg:flex-row lg:flex-wrap lg:items-end"
    >
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
        <div class="min-w-[12rem]">
            <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                Venue
            </label>
            <select
                wire:model.live="venue"
                class="mt-1 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
            >
                <option value="">All venues</option>
                @foreach ($venues as $v)
                    <option value="{{ $v->id }}">{{ $v->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-[12rem]">
            <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                Channel
            </label>
            <select
                wire:model.live="channelFilter"
                class="mt-1 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
            >
                <option value="">All</option>
                <option value="manual">Manual only</option>
                <option value="automated">Automated only</option>
            </select>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-left text-sm dark:divide-zinc-800">
                <thead class="bg-zinc-50 dark:bg-zinc-800/80">
                    <tr>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Start
                        </th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Venue
                        </th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Court
                        </th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Guest
                        </th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Channel
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Amount
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Conv. fee
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($bookings as $b)
                        @php
                            $src = data_get($b->checkout_snapshot, 'source');
                            $currency = $b->currency ?? $b->courtClient?->currency ?? 'PHP';
                            $channelLabel =
                                $src === BookingCheckoutSnapshot::SOURCE_MANUAL_DESK
                                    ? 'Manual'
                                    : ($src === BookingCheckoutSnapshot::SOURCE_MEMBER_PUBLIC
                                        ? 'Automated'
                                        : 'Unknown');
                            $channelClass =
                                $src === BookingCheckoutSnapshot::SOURCE_MANUAL_DESK
                                    ? 'bg-zinc-200 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200'
                                    : ($src === BookingCheckoutSnapshot::SOURCE_MEMBER_PUBLIC
                                        ? 'bg-emerald-100 text-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-200'
                                        : 'bg-amber-100 text-amber-900 dark:bg-amber-950/50 dark:text-amber-200');
                        @endphp
                        <tr wire:key="booking-channel-{{ $b->id }}" class="hover:bg-zinc-50/80 dark:hover:bg-zinc-800/40">
                            <td class="whitespace-nowrap px-4 py-3 text-zinc-800 dark:text-zinc-200">
                                <a
                                    href="{{ route('admin.bookings.show', $b) }}"
                                    wire:navigate
                                    class="font-medium text-emerald-700 hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-300"
                                >
                                    {{ $b->starts_at?->timezone($tz)->isoFormat('MMM D, YYYY h:mm A') }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">
                                {{ $b->courtClient?->name ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">
                                {{ $b->court?->name ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">
                                {{ $b->user?->name ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $channelClass }}"
                                >
                                    {{ $channelLabel }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-medium tabular-nums text-zinc-900 dark:text-zinc-100">
                                {{ Money::formatMinor((int) ($b->amount_cents ?? 0), $currency) }}
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums text-zinc-700 dark:text-zinc-300">
                                {{ Money::formatMinor((int) ($b->platform_booking_fee_cents ?? 0), $currency) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                No bookings match these filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($bookings->isNotEmpty())
                    <tfoot class="border-t-2 border-zinc-300 bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800/60">
                        <tr>
                            <td
                                colspan="5"
                                class="px-4 py-3 text-right text-sm font-semibold text-zinc-800 dark:text-zinc-200"
                            >
                                Totals ({{ number_format($footerTotals['booking_count']) }} in range)
                            </td>
                            <td class="px-4 py-3 text-right font-display text-sm font-bold tabular-nums text-zinc-900 dark:text-white">
                                {{ Money::formatMinor($footerTotals['amount_cents'], $displayCurrency) }}
                            </td>
                            <td class="px-4 py-3 text-right font-display text-sm font-bold tabular-nums text-emerald-800 dark:text-emerald-200">
                                {{ Money::formatMinor($footerTotals['platform_fee_cents'], $displayCurrency) }}
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
        @if ($bookings->hasPages())
            <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-800">
                {{ $bookings->links() }}
            </div>
        @endif
    </div>
</div>
