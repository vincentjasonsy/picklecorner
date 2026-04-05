@php
    use App\Models\CourtClientInvoice;
    use App\Support\Money;

    $inv = $invoice;
@endphp

<div class="mx-auto max-w-4xl space-y-8">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <a
                href="{{ route('admin.invoices.index') }}"
                wire:navigate
                class="text-sm font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400"
            >
                ← Back to invoices
            </a>
            <h1 class="mt-4 font-display text-2xl font-bold text-zinc-900 dark:text-white">Invoice</h1>
            <p class="mt-1 font-mono text-lg text-zinc-800 dark:text-zinc-200">{{ $inv->reference }}</p>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                <strong>{{ $inv->courtClient?->name ?? '—' }}</strong>
                · Period {{ $inv->period_from?->toDateString() }} → {{ $inv->period_to?->toDateString() }}
            </p>
        </div>
        <div class="flex flex-col items-end gap-2">
            <a
                href="{{ route('admin.invoices.pdf', $inv) }}"
                class="rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-semibold text-zinc-800 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700"
            >
                Download PDF
            </a>
            @if ($inv->status === CourtClientInvoice::STATUS_PAID)
                <span
                    class="rounded-full bg-emerald-100 px-3 py-1 text-sm font-semibold text-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-200"
                >
                    Paid
                </span>
                @if ($inv->paid_at)
                    <span class="text-xs text-zinc-500">{{ $inv->paid_at->isoFormat('MMM D, YYYY h:mm a') }}</span>
                @endif
                <button
                    type="button"
                    wire:click="markUnpaid"
                    wire:confirm="Mark this invoice as unpaid?"
                    class="text-sm font-semibold text-amber-700 hover:text-amber-800 dark:text-amber-400"
                >
                    Mark unpaid
                </button>
            @else
                <span
                    class="rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold text-amber-900 dark:bg-amber-950/50 dark:text-amber-200"
                >
                    Unpaid
                </span>
                <button
                    type="button"
                    wire:click="markPaid"
                    class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-700 dark:bg-emerald-500"
                >
                    Mark paid
                </button>
            @endif
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex flex-wrap justify-between gap-4 border-b border-zinc-200 pb-4 dark:border-zinc-700">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Bill to</p>
                <p class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">{{ $inv->courtClient?->name ?? '—' }}</p>
                @if ($inv->courtClient?->city)
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $inv->courtClient->city }}</p>
                @endif
            </div>
            <div class="text-right">
                <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Total due</p>
                <p class="mt-1 font-display text-2xl font-bold text-zinc-900 dark:text-white">
                    {{ Money::formatMinor($inv->total_cents, $inv->currency) }}
                </p>
                <p class="mt-1 text-xs text-zinc-500">{{ $inv->bookings->count() }} booking(s)</p>
            </div>
        </div>

        @if ($inv->notes)
            <div class="mt-4 rounded-lg bg-zinc-50 p-3 text-sm text-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-300">
                <span class="font-semibold text-zinc-600 dark:text-zinc-400">Notes:</span>
                {{ $inv->notes }}
            </div>
        @endif

        <div class="mt-6 space-y-8">
            @foreach ($byDay as $dayKey => $dayBookings)
                @php
                    $dayLabel = \Carbon\Carbon::parse($dayKey, $tz)->isoFormat('dddd, MMM D, YYYY');
                    $daySubtotal = (int) $dayBookings->sum(fn ($b) => (int) ($b->pivot->amount_cents ?? 0));
                @endphp
                <section wire:key="invday-{{ $dayKey }}">
                    <div class="flex flex-wrap items-baseline justify-between gap-2 border-b border-zinc-200 pb-2 dark:border-zinc-700">
                        <h2 class="font-display text-base font-bold text-zinc-900 dark:text-white">{{ $dayLabel }}</h2>
                        <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400">
                            {{ Money::formatMinor($daySubtotal, $inv->currency) }}
                        </span>
                    </div>
                    <div class="mt-3 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                    <th class="py-2 pr-3">Time</th>
                                    <th class="py-2 pr-3">Court</th>
                                    <th class="py-2 pr-3">Guest</th>
                                    <th class="py-2 pr-3">Status</th>
                                    <th class="py-2 text-right">Amount</th>
                                    <th class="py-2 text-right"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                @foreach ($dayBookings as $b)
                                    <tr wire:key="invb-{{ $b->id }}">
                                        <td class="whitespace-nowrap py-2 pr-3 text-zinc-700 dark:text-zinc-300">
                                            {{ $b->starts_at?->timezone($tz)->format('g:i A') }}
                                            <span class="text-zinc-400">–</span>
                                            {{ $b->ends_at?->timezone($tz)->format('g:i A') }}
                                        </td>
                                        <td class="py-2 pr-3 text-zinc-600 dark:text-zinc-400">{{ $b->court?->name ?? '—' }}</td>
                                        <td class="py-2 pr-3 text-zinc-600 dark:text-zinc-400">
                                            <span class="block max-w-[12rem] truncate">{{ $b->user?->name ?? '—' }}</span>
                                        </td>
                                        <td class="py-2 pr-3 text-xs text-zinc-500">
                                            {{ \App\Models\Booking::statusDisplayLabel($b->status) }}
                                        </td>
                                        <td class="whitespace-nowrap py-2 text-right font-medium text-zinc-800 dark:text-zinc-200">
                                            {{ Money::formatMinor((int) ($b->pivot->amount_cents ?? 0), $inv->currency) }}
                                        </td>
                                        <td class="whitespace-nowrap py-2 text-right">
                                            <a
                                                href="{{ route('admin.bookings.show', $b) }}"
                                                wire:navigate
                                                class="text-xs font-semibold text-emerald-600 dark:text-emerald-400"
                                            >
                                                Booking
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endforeach
        </div>

        <div class="mt-8 flex justify-end border-t border-zinc-200 pt-4 dark:border-zinc-700">
            <div class="text-right">
                <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Invoice total</p>
                <p class="mt-1 font-display text-xl font-bold text-zinc-900 dark:text-white">
                    {{ Money::formatMinor($inv->total_cents, $inv->currency) }}
                </p>
            </div>
        </div>

        @if ($inv->creator)
            <p class="mt-6 text-xs text-zinc-500 dark:text-zinc-400">
                Created by {{ $inv->creator->name }} · {{ $inv->created_at?->isoFormat('MMM D, YYYY g:mma') }}
            </p>
        @endif
    </div>
</div>
