@php
    use App\Support\Money;

    $tz = config('app.timezone');
@endphp

<div class="mx-auto max-w-5xl space-y-8">
    <div>
        <a
            href="{{ route('admin.invoices.index') }}"
            wire:navigate
            class="text-sm font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400"
        >
            ← Back to invoices
        </a>
        <h1 class="mt-4 font-display text-2xl font-bold text-zinc-900 dark:text-white">New client invoice</h1>
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
            Includes only <strong>desk or admin manual</strong> bookings <strong>paid manually</strong> (cash, transfer,
            GCash, etc.) — not Book now / PayMongo checkouts. Only <strong>confirmed</strong> and
            <strong>completed</strong> rows whose scheduled start falls in the range. Bookings already on another invoice
            are skipped. Amounts use each booking’s stored total (missing amounts count as zero).
        </p>
    </div>

    <form wire:submit="createInvoice" class="space-y-6">
        <div
            class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900"
        >
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        Court client
                    </label>
                    <select
                        wire:model.live="courtClientId"
                        class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                    >
                        <option value="">Select venue</option>
                        @foreach ($clients as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                    @error('courtClientId')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        Period from
                    </label>
                    <input
                        type="date"
                        wire:model.live="periodFrom"
                        class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                    />
                    @error('periodFrom')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        Period to
                    </label>
                    <input
                        type="date"
                        wire:model.live="periodTo"
                        class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                    />
                    @error('periodTo')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        Notes (optional)
                    </label>
                    <textarea
                        wire:model="notes"
                        rows="2"
                        class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                        placeholder="e.g. Payment terms, PO reference"
                    ></textarea>
                    @error('notes')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div
            class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900"
        >
            <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Preview</h2>
            @if ($courtClientId === '')
                <p class="mt-4 text-sm text-zinc-500">Select a court client to preview line items.</p>
            @elseif ($previewBookings->isEmpty())
                <p class="mt-4 text-sm text-amber-800 dark:text-amber-200">
                    No eligible bookings in this range — need manual desk/admin bookings paid outside PayMongo (or all
                    matching rows are already invoiced).
                </p>
            @else
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                    <strong>{{ $previewBookings->count() }}</strong> booking(s) · Total
                    <strong>{{ Money::formatMinor($previewTotalCents, $previewCurrency) }}</strong>
                </p>
                <div class="mt-6 space-y-6">
                    @foreach ($previewByDay as $dayKey => $dayRows)
                        @php
                            $dayLabel = \Carbon\Carbon::parse($dayKey, $tz)->isoFormat('dddd, MMM D, YYYY');
                        @endphp
                        <div>
                            <h3 class="border-b border-zinc-200 pb-2 text-sm font-bold text-zinc-800 dark:border-zinc-700 dark:text-zinc-200">
                                {{ $dayLabel }}
                                <span class="ml-2 font-normal text-zinc-500">({{ $dayRows->count() }})</span>
                            </h3>
                            <ul class="mt-2 space-y-1 text-sm text-zinc-700 dark:text-zinc-300">
                                @foreach ($dayRows as $b)
                                    <li class="flex flex-wrap justify-between gap-2 border-b border-zinc-100 py-2 dark:border-zinc-800">
                                        <span>
                                            {{ $b->starts_at?->timezone($tz)->format('g:i A') }}
                                            · {{ $b->court?->name ?? 'Court' }}
                                            · {{ $b->user?->name ?? 'Guest' }}
                                        </span>
                                        <span class="font-medium tabular-nums">
                                            {{ Money::formatMinor($b->amount_cents, $b->currency) }}
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="flex justify-end">
            <button
                type="submit"
                @disabled($previewBookings->isEmpty())
                class="rounded-lg bg-zinc-900 px-6 py-2.5 text-sm font-bold uppercase tracking-wide text-white hover:bg-zinc-800 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-zinc-200 dark:text-zinc-900 dark:hover:bg-white"
            >
                Create invoice
            </button>
        </div>
    </form>
</div>
