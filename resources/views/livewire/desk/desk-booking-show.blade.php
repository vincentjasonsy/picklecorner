@php
    use App\Models\Booking;
    use App\Support\Money;

    $tz = config('app.timezone', 'UTC');
    $b = $booking;
@endphp

<div class="mx-auto max-w-2xl space-y-6">
    <a
        href="{{ $this->deskBackUrl() }}"
        wire:navigate
        class="inline-flex text-sm font-semibold text-teal-700 hover:text-teal-800 dark:text-teal-300 dark:hover:text-teal-200"
    >
        {{ $this->deskBackLabel() }}
    </a>

    <div>
        <h1 class="font-display text-2xl font-bold text-stone-900 dark:text-white">Booking</h1>
        <span
            class="mt-2 inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ match ($b->status) {
                Booking::STATUS_CONFIRMED => 'bg-teal-100 text-teal-900 dark:bg-teal-950/50 dark:text-teal-100',
                Booking::STATUS_PENDING_APPROVAL => 'bg-amber-100 text-amber-950 dark:bg-amber-950/40 dark:text-amber-100',
                Booking::STATUS_DENIED => 'bg-rose-100 text-rose-950 dark:bg-rose-950/40 dark:text-rose-100',
                Booking::STATUS_CANCELLED => 'bg-stone-200 text-stone-800 dark:bg-stone-700 dark:text-stone-200',
                Booking::STATUS_COMPLETED => 'bg-stone-200 text-stone-800 dark:bg-stone-600 dark:text-stone-100',
                default => 'bg-stone-200 text-stone-700 dark:bg-stone-700 dark:text-stone-200',
            } }}"
        >
            {{ Booking::statusDisplayLabel($b->status) }}
        </span>
    </div>

    <dl
        class="grid gap-4 rounded-2xl border border-stone-200 bg-white p-6 text-sm shadow-sm dark:border-stone-800 dark:bg-stone-900/80 sm:grid-cols-2"
    >
        <div class="sm:col-span-2">
            <dt class="text-xs font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400">Reference</dt>
            <dd class="mt-1 break-all font-mono text-xs text-stone-700 dark:text-stone-200">
                {{ $b->transactionReference() }}
            </dd>
        </div>
        <div class="sm:col-span-2">
            <dt class="text-xs font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400">When</dt>
            <dd class="mt-1 text-stone-900 dark:text-stone-100">
                {{ $b->starts_at?->timezone($tz)->isoFormat('dddd, MMM D, YYYY · h:mm a') ?? '—' }}
                <span class="text-stone-500">→</span>
                {{ $b->ends_at?->timezone($tz)->isoFormat('h:mm a') ?? '—' }}
            </dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400">Court</dt>
            <dd class="mt-1 font-medium text-stone-900 dark:text-stone-100">{{ $b->court?->name ?? '—' }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400">Guest</dt>
            <dd class="mt-1 text-stone-900 dark:text-stone-100">
                @if ($b->user)
                    <span class="font-medium">{{ $b->user->name }}</span>
                    <span class="mt-0.5 block text-xs text-stone-500">{{ $b->user->email }}</span>
                @else
                    —
                @endif
            </dd>
        </div>
        @if ($b->coach)
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400">Coach</dt>
                <dd class="mt-1 text-stone-900 dark:text-stone-100">{{ $b->coach->name }}</dd>
            </div>
        @endif
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400">
                Court amount
            </dt>
            <dd class="mt-1 text-stone-900 dark:text-stone-100">
                {{ Money::formatMinor($b->amount_cents, $b->currency ?? 'PHP') }}
            </dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400">
                Convenience fee
            </dt>
            <dd class="mt-1 text-stone-900 dark:text-stone-100">
                {{ Money::formatMinor((int) ($b->platform_booking_fee_cents ?? 0), $b->currency ?? 'PHP') }}
            </dd>
        </div>
        @php
            $courtCents = (int) ($b->amount_cents ?? 0);
            $convCents = (int) ($b->platform_booking_fee_cents ?? 0);
        @endphp
        @if ($convCents > 0)
            <div class="sm:col-span-2 border-t border-stone-100 pt-4 dark:border-stone-700">
                <dt class="text-xs font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400">
                    Total charged (court + convenience fee)
                </dt>
                <dd class="mt-1 font-display text-lg font-bold text-stone-900 dark:text-stone-50">
                    {{ Money::formatMinor($courtCents + $convCents, $b->currency ?? 'PHP') }}
                </dd>
            </div>
        @endif
    </dl>
</div>
