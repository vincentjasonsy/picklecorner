@props(['method' => ''])

@php
    $gcashNumber = trim((string) config('payments.gcash_number', ''));
@endphp

@if ($gcashNumber !== '' && $method === \App\Models\Booking::PAYMENT_GCASH)
    <div
        {{ $attributes->merge(['class' => 'rounded-lg border border-teal-200 bg-teal-50/80 p-4 dark:border-teal-900/50 dark:bg-teal-950/30']) }}
        x-data="{
            num: @js($gcashNumber),
            copied: false,
            async copy() {
                try {
                    await navigator.clipboard.writeText(this.num);
                    this.copied = true;
                    window.setTimeout(() => (this.copied = false), 2000);
                } catch (e) {}
            },
        }"
    >
        <p class="text-xs font-semibold uppercase tracking-wider text-teal-800 dark:text-teal-200">
            Send GCash payment to
        </p>
        <div class="mt-2 flex flex-wrap items-center gap-2">
            <span class="font-mono text-base font-semibold tracking-wide text-zinc-900 dark:text-zinc-100" x-text="num"></span>
            <button
                type="button"
                @click="copy()"
                class="inline-flex items-center rounded-lg border border-teal-300 bg-white px-3 py-1.5 text-xs font-bold uppercase tracking-wide text-teal-800 hover:bg-teal-50 dark:border-teal-700 dark:bg-teal-900/50 dark:text-teal-100 dark:hover:bg-teal-900/70"
            >
                <span x-show="!copied">Copy</span>
                <span x-show="copied" x-cloak class="text-emerald-700 dark:text-emerald-300">Copied!</span>
            </button>
        </div>
        <p class="mt-2 text-xs text-zinc-600 dark:text-zinc-400">
            Send the amount due to this number, then enter your transaction reference below.
        </p>
    </div>
@endif
