@php
    use App\Models\CourtClientInvoice;
    use App\Support\Money;
@endphp

<div class="space-y-8">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="font-display text-2xl font-bold text-zinc-900 dark:text-white">Client invoices</h1>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                Request settlements from court clients from confirmed &amp; completed bookings. Each booking can only appear on
                one invoice.
            </p>
        </div>
        <a
            href="{{ route('admin.invoices.create') }}"
            wire:navigate
            class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold uppercase tracking-wide text-white hover:bg-emerald-700 dark:bg-emerald-500"
        >
            New invoice
        </a>
    </div>

    <div class="flex flex-wrap gap-4">
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Status</label>
            <select
                wire:model.live="statusFilter"
                class="mt-1 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900"
            >
                <option value="">All</option>
                <option value="{{ CourtClientInvoice::STATUS_UNPAID }}">Unpaid</option>
                <option value="{{ CourtClientInvoice::STATUS_PAID }}">Paid</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Venue</label>
            <select
                wire:model.live="clientFilter"
                class="mt-1 min-w-[12rem] rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900"
            >
                <option value="">All venues</option>
                @foreach ($clients as $c)
                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div
        class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900"
    >
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-zinc-700 dark:text-zinc-300">Reference</th>
                        <th class="px-4 py-3 text-left font-semibold text-zinc-700 dark:text-zinc-300">Venue</th>
                        <th class="px-4 py-3 text-left font-semibold text-zinc-700 dark:text-zinc-300">Period</th>
                        <th class="px-4 py-3 text-left font-semibold text-zinc-700 dark:text-zinc-300">Status</th>
                        <th class="px-4 py-3 text-right font-semibold text-zinc-700 dark:text-zinc-300">Total</th>
                        <th class="px-4 py-3 text-left font-semibold text-zinc-700 dark:text-zinc-300">Created</th>
                        <th class="px-4 py-3 text-right font-semibold text-zinc-700 dark:text-zinc-300"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($invoices as $inv)
                        <tr wire:key="inv-{{ $inv->id }}">
                            <td class="px-4 py-3 font-mono text-xs font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ $inv->reference }}
                            </td>
                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">{{ $inv->courtClient?->name ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-xs text-zinc-600 dark:text-zinc-400">
                                {{ $inv->period_from?->toDateString() }} → {{ $inv->period_to?->toDateString() }}
                            </td>
                            <td class="px-4 py-3">
                                @if ($inv->status === CourtClientInvoice::STATUS_PAID)
                                    <span
                                        class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200"
                                    >
                                        Paid
                                    </span>
                                @else
                                    <span
                                        class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-900 dark:bg-amber-950/50 dark:text-amber-200"
                                    >
                                        Unpaid
                                    </span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-zinc-800 dark:text-zinc-200">
                                {{ Money::formatMinor($inv->total_cents, $inv->currency) }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-xs text-zinc-500 dark:text-zinc-400">
                                {{ $inv->created_at?->isoFormat('MMM D, YYYY') }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a
                                    href="{{ route('admin.invoices.show', $inv) }}"
                                    wire:navigate
                                    class="text-sm font-semibold text-emerald-600 dark:text-emerald-400"
                                >
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-zinc-500">No invoices yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="text-sm text-zinc-500 dark:text-zinc-400">
        {{ $invoices->links() }}
    </div>
</div>
