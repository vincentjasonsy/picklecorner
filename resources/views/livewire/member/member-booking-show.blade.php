@php
    use App\Models\Booking;
    use App\Models\Court;
    use App\Support\Money;

    $tz = config('app.timezone', 'UTC');
    $b = $booking;
    $client = $b->courtClient;
    $court = $b->court;
@endphp

<div class="space-y-6">
    <a
        href="{{ route('account.bookings') }}"
        wire:navigate
        class="inline-flex text-sm font-semibold text-emerald-700 hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-300"
    >
        ← Back to My games
    </a>

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="font-display text-2xl font-extrabold tracking-tight text-zinc-900 dark:text-white">
                {{ $client?->name ?? 'Booking' }}
            </h1>
            @if ($court)
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                    {{ $court->name }}
                    <span class="text-zinc-400">·</span>
                    {{ $court->environment === Court::ENV_INDOOR ? 'Indoor' : 'Outdoor' }}
                </p>
            @endif
        </div>
        <span
            class="inline-flex shrink-0 rounded-full px-3 py-1 text-xs font-bold {{ match ($b->status) {
                Booking::STATUS_CONFIRMED, Booking::STATUS_COMPLETED => 'bg-emerald-100 text-emerald-900 dark:bg-emerald-950/60 dark:text-emerald-200',
                Booking::STATUS_PENDING_APPROVAL => 'bg-amber-100 text-amber-900 dark:bg-amber-950/50 dark:text-amber-200',
                Booking::STATUS_CANCELLED => 'bg-zinc-200 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200',
                Booking::STATUS_DENIED => 'bg-red-100 text-red-800 dark:bg-red-950/50 dark:text-red-200',
                default => 'bg-zinc-200 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200',
            } }}"
        >
            {{ Booking::statusDisplayLabel($b->status) }}
        </span>
    </div>

    <div class="flex flex-wrap gap-3">
        @if ($client && $court)
            <a
                href="{{ route('account.book.venue', $client) }}"
                wire:navigate
                class="inline-flex items-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600"
            >
                Book again
            </a>
            <a
                href="{{ route('book-now.court', $court) }}"
                wire:navigate
                class="inline-flex items-center rounded-xl border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-zinc-800 transition hover:border-emerald-300 hover:text-emerald-800 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:border-emerald-700"
            >
                Venue &amp; court page
            </a>
        @endif
        @if ($b->is_open_play && $b->starts_at && $b->starts_at->isFuture())
            <a
                href="{{ route('account.court-open-plays.host', $b) }}"
                wire:navigate
                class="inline-flex items-center rounded-xl border border-violet-200 bg-violet-50 px-4 py-2 text-sm font-semibold text-violet-900 transition hover:bg-violet-100 dark:border-violet-800 dark:bg-violet-950/40 dark:text-violet-100 dark:hover:bg-violet-950/70"
            >
                Open play host tools
            </a>
        @endif
    </div>

    <dl class="grid gap-5 rounded-2xl border border-zinc-200 bg-white p-6 text-sm shadow-sm dark:border-zinc-800 dark:bg-zinc-900 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <dt class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">When</dt>
            <dd class="mt-2 text-base font-medium text-zinc-900 dark:text-zinc-100">
                {{ $b->starts_at?->timezone($tz)->isoFormat('dddd, MMM D, YYYY') ?? '—' }}
            </dd>
            <dd class="mt-1 text-zinc-600 dark:text-zinc-400">
                {{ $b->starts_at?->timezone($tz)->format('g:i A') ?? '—' }}
                <span class="text-zinc-400">–</span>
                {{ $b->ends_at?->timezone($tz)->format('g:i A') ?? '—' }}
                <span class="text-zinc-400">({{ $tz }})</span>
            </dd>
        </div>

        @if ($client?->city)
            <div>
                <dt class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">City</dt>
                <dd class="mt-2 font-medium text-zinc-900 dark:text-zinc-100">{{ $client->city }}</dd>
            </div>
        @endif

        @if ($b->coach)
            <div class="sm:col-span-2">
                <dt class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Coach</dt>
                <dd class="mt-2 font-medium text-zinc-900 dark:text-zinc-100">{{ $b->coach->name }}</dd>
            </div>
        @endif

        @if ($b->amount_cents !== null)
            <div>
                <dt class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Amount</dt>
                <dd class="mt-2 font-semibold text-zinc-900 dark:text-zinc-100">
                    {{ Money::formatMinor($b->amount_cents, $b->currency ?? 'PHP') }}
                </dd>
            </div>
        @endif

        @if ($b->payment_method)
            <div>
                <dt class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Payment</dt>
                <dd class="mt-2 text-zinc-900 dark:text-zinc-100">
                    {{ Booking::paymentMethodLabel($b->payment_method) }}
                    @if ($b->payment_reference)
                        <span class="mt-1 block text-xs text-zinc-500">Ref: {{ $b->payment_reference }}</span>
                    @endif
                </dd>
            </div>
        @endif

        @if ($b->giftCard)
            <div class="sm:col-span-2">
                <dt class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Gift card</dt>
                <dd class="mt-2 font-mono text-sm text-zinc-900 dark:text-zinc-100">{{ $b->giftCard->code }}</dd>
                @if ($b->gift_card_redeemed_cents !== null && $b->gift_card_redeemed_cents > 0)
                    <dd class="mt-1 text-xs text-zinc-500">
                        Applied: {{ Money::formatMinor($b->gift_card_redeemed_cents, $b->currency ?? 'PHP') }}
                    </dd>
                @endif
            </div>
        @endif

        @if ($b->notes)
            <div class="sm:col-span-2">
                <dt class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Notes</dt>
                <dd class="mt-2 whitespace-pre-wrap text-zinc-800 dark:text-zinc-200">{{ $b->notes }}</dd>
            </div>
        @endif

        @if ($b->is_open_play && $b->open_play_public_notes)
            <div class="sm:col-span-2">
                <dt class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Open play (public)</dt>
                <dd class="mt-2 whitespace-pre-wrap text-zinc-800 dark:text-zinc-200">{{ $b->open_play_public_notes }}</dd>
            </div>
        @endif
    </dl>
</div>
