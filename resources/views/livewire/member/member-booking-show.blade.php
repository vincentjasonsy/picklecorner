@php
    use App\Models\Booking;
    use App\Models\Court;
    use App\Services\BookingCheckoutSnapshot;
    use App\Support\Money;

    $tz = config('app.timezone', 'UTC');
    $b = $booking;
    $client = $b->courtClient;
    $court = $b->court;
    $currency = $b->currency ?? 'PHP';
    $snap = $b->checkout_snapshot;
    $hasCheckoutSnapshot =
        is_array($snap) && (int) ($snap['schema_version'] ?? 0) === BookingCheckoutSnapshot::SCHEMA_VERSION;
    $platformFeeCents = (int) ($b->platform_booking_fee_cents ?? 0);
    $hasPlatformFee = $platformFeeCents > 0;
    $giftRedeemedCents = (int) ($b->gift_card_redeemed_cents ?? 0);
    $courtCoachGrossCents = (int) ($b->amount_cents ?? 0) + $giftRedeemedCents;
    $coachFeeCents = (int) ($b->coach_fee_cents ?? 0);
    $courtRentalCents = max(0, $courtCoachGrossCents - $coachFeeCents);
    $splitCourtCoach = $coachFeeCents > 0;
    $amountAfterGiftCents = (int) ($b->amount_cents ?? 0);
    $linePayableCents = $amountAfterGiftCents + $platformFeeCents;
    $useDetailedPaymentBreakdown = $splitCourtCoach || $giftRedeemedCents > 0 || $hasPlatformFee;
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
            <dt class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Reference</dt>
            <dd class="mt-2 break-all font-mono text-xs text-zinc-700 dark:text-zinc-300">
                {{ $b->transactionReference() }}
            </dd>
        </div>

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

        @if ($hasCheckoutSnapshot)
            @php
                $req = $snap['request'];
                $line = $snap['line'];
                $snapCurrency = $snap['currency'] ?? $currency;
                $feeLabel = $snap['fee_rule_label'] ?? null;
                $snapSource = $snap['source'] ?? '';
                $rqCourt = (int) ($req['court_subtotal_cents'] ?? 0);
                $rqCoach = (int) ($req['coach_fee_total_cents'] ?? 0);
                $rqBookingFee = (int) ($req['booking_fee_total_cents'] ?? 0);
                $rqCheckout = (int) ($req['checkout_total_before_gift_cents'] ?? 0);
                $rqGift = $req['gift_applied_total_cents'] ?? null;
                $rqGiftInt = $rqGift !== null ? (int) $rqGift : null;
                $rqBalance = (int) ($req['balance_after_gift_cents'] ?? 0);
                $lnCourt = (int) ($line['court_subtotal_cents'] ?? 0);
                $lnCoach = (int) ($line['coach_fee_cents'] ?? 0);
                $lnGross = (int) ($line['court_coach_gross_cents'] ?? 0);
                $lnGift = (int) ($line['gift_applied_cents'] ?? 0);
                $lnAfterGift = (int) ($line['court_coach_after_gift_cents'] ?? 0);
                $lnPlatform = (int) ($line['platform_booking_fee_cents'] ?? 0);
                $lnPayable = (int) ($line['line_total_payable_cents'] ?? 0);
            @endphp
            <div class="sm:col-span-2 overflow-hidden rounded-xl border border-zinc-200 bg-zinc-50/90 dark:border-zinc-700 dark:bg-zinc-950/50">
                <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        Payment breakdown
                    </h2>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        Saved from checkout — matches the totals you reviewed before submitting.
                    </p>
                    @if ($snapSource === BookingCheckoutSnapshot::SOURCE_MANUAL_DESK)
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            Recorded when this booking was created (desk / admin).
                        </p>
                    @endif
                </div>

                <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                    <p class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        Whole request
                    </p>
                    <dl class="mt-2 divide-y divide-zinc-200 dark:divide-zinc-700">
                        <div class="flex items-baseline justify-between gap-4 py-2 text-sm">
                            <dt class="text-zinc-600 dark:text-zinc-400">Courts subtotal</dt>
                            <dd class="shrink-0 tabular-nums font-medium text-zinc-900 dark:text-zinc-100">
                                {{ Money::formatMinor($rqCourt, $snapCurrency) }}
                            </dd>
                        </div>
                        @if ($rqCoach > 0)
                            <div class="flex items-baseline justify-between gap-4 py-2 text-sm">
                                <dt class="text-zinc-600 dark:text-zinc-400">Coach</dt>
                                <dd class="shrink-0 tabular-nums font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ Money::formatMinor($rqCoach, $snapCurrency) }}
                                </dd>
                            </div>
                        @endif
                        @if ($rqBookingFee > 0)
                            <div class="flex items-baseline justify-between gap-4 py-2 text-sm">
                                <dt class="text-zinc-600 dark:text-zinc-400">
                                    Convenience fee
                                    @if ($feeLabel)
                                        <span class="mt-0.5 block text-xs font-normal text-zinc-500 dark:text-zinc-400">
                                            {{ $feeLabel }}
                                        </span>
                                    @endif
                                </dt>
                                <dd class="shrink-0 tabular-nums font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ Money::formatMinor($rqBookingFee, $snapCurrency) }}
                                </dd>
                            </div>
                        @endif
                        <div class="flex items-baseline justify-between gap-4 bg-white/60 py-2 text-sm dark:bg-zinc-900/40">
                            <dt class="font-medium text-zinc-700 dark:text-zinc-300">Total</dt>
                            <dd class="shrink-0 tabular-nums font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ Money::formatMinor($rqCheckout, $snapCurrency) }}
                            </dd>
                        </div>
                        @if ($rqGiftInt !== null && $rqGiftInt > 0)
                            <div class="flex items-baseline justify-between gap-4 py-2 text-sm">
                                <dt class="text-zinc-600 dark:text-zinc-400">Gift card</dt>
                                <dd class="shrink-0 tabular-nums font-medium text-emerald-700 dark:text-emerald-400">
                                    −{{ Money::formatMinor($rqGiftInt, $snapCurrency) }}
                                </dd>
                            </div>
                            <div class="flex items-baseline justify-between gap-4 py-2 text-sm">
                                <dt class="font-medium text-zinc-700 dark:text-zinc-300">Balance after gift</dt>
                                <dd class="shrink-0 tabular-nums font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ Money::formatMinor($rqBalance, $snapCurrency) }}
                                </dd>
                            </div>
                        @endif
                    </dl>
                </div>

                <div class="px-4 py-3">
                    <p class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        This reservation line
                    </p>
                    <dl class="mt-2 divide-y divide-zinc-200 dark:divide-zinc-700">
                        @if ($lnCoach > 0)
                            <div class="flex items-baseline justify-between gap-4 py-2 text-sm">
                                <dt class="text-zinc-600 dark:text-zinc-400">Court rental</dt>
                                <dd class="shrink-0 tabular-nums font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ Money::formatMinor($lnCourt, $snapCurrency) }}
                                </dd>
                            </div>
                            <div class="flex items-baseline justify-between gap-4 py-2 text-sm">
                                <dt class="text-zinc-600 dark:text-zinc-400">Coach</dt>
                                <dd class="shrink-0 tabular-nums font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ Money::formatMinor($lnCoach, $snapCurrency) }}
                                </dd>
                            </div>
                            <div class="flex items-baseline justify-between gap-4 bg-white/60 py-2 text-sm dark:bg-zinc-900/40">
                                <dt class="font-medium text-zinc-700 dark:text-zinc-300">Court &amp; coach (before gift)</dt>
                                <dd class="shrink-0 tabular-nums font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ Money::formatMinor($lnGross, $snapCurrency) }}
                                </dd>
                            </div>
                        @else
                            <div class="flex items-baseline justify-between gap-4 py-2 text-sm">
                                <dt class="text-zinc-600 dark:text-zinc-400">Court &amp; coach</dt>
                                <dd class="shrink-0 tabular-nums font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ Money::formatMinor($lnGross, $snapCurrency) }}
                                </dd>
                            </div>
                        @endif
                        @if ($lnGift > 0)
                            <div class="flex items-baseline justify-between gap-4 py-2 text-sm">
                                <dt class="text-zinc-600 dark:text-zinc-400">Gift card (this line)</dt>
                                <dd class="shrink-0 tabular-nums font-medium text-emerald-700 dark:text-emerald-400">
                                    −{{ Money::formatMinor($lnGift, $snapCurrency) }}
                                </dd>
                            </div>
                            <div class="flex items-baseline justify-between gap-4 bg-white/60 py-2 text-sm dark:bg-zinc-900/40">
                                <dt class="font-medium text-zinc-700 dark:text-zinc-300">Court &amp; coach after gift</dt>
                                <dd class="shrink-0 tabular-nums font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ Money::formatMinor($lnAfterGift, $snapCurrency) }}
                                </dd>
                            </div>
                        @endif
                        @if ($lnPlatform > 0)
                            <div class="flex items-baseline justify-between gap-4 py-2 text-sm">
                                <dt class="text-zinc-600 dark:text-zinc-400">Convenience fee</dt>
                                <dd class="shrink-0 tabular-nums font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ Money::formatMinor($lnPlatform, $snapCurrency) }}
                                </dd>
                            </div>
                        @endif
                        <div class="flex items-baseline justify-between gap-4 bg-white py-3 dark:bg-zinc-900/70">
                            <dt class="text-sm font-bold text-zinc-900 dark:text-white">Line total</dt>
                            <dd class="shrink-0 text-lg font-bold tabular-nums text-zinc-900 dark:text-white">
                                {{ Money::formatMinor($lnPayable, $snapCurrency) }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        @elseif ($useDetailedPaymentBreakdown)
            <div class="sm:col-span-2 overflow-hidden rounded-xl border border-zinc-200 bg-zinc-50/90 dark:border-zinc-700 dark:bg-zinc-950/50">
                <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        Payment breakdown
                    </h2>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        Estimated from stored amounts (older booking — no checkout snapshot).
                    </p>
                </div>
                <dl class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @if ($splitCourtCoach)
                        <div class="flex items-baseline justify-between gap-4 px-4 py-3 text-sm">
                            <dt class="text-zinc-600 dark:text-zinc-400">Court rental</dt>
                            <dd class="shrink-0 tabular-nums font-medium text-zinc-900 dark:text-zinc-100">
                                {{ Money::formatMinor($courtRentalCents, $currency) }}
                            </dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-4 px-4 py-3 text-sm">
                            <dt class="text-zinc-600 dark:text-zinc-400">Coach</dt>
                            <dd class="shrink-0 tabular-nums font-medium text-zinc-900 dark:text-zinc-100">
                                {{ Money::formatMinor($coachFeeCents, $currency) }}
                            </dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-4 bg-white/60 px-4 py-3 text-sm dark:bg-zinc-900/40">
                            <dt class="font-medium text-zinc-700 dark:text-zinc-300">Court &amp; coach (before gift)</dt>
                            <dd class="shrink-0 tabular-nums font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ Money::formatMinor($courtCoachGrossCents, $currency) }}
                            </dd>
                        </div>
                    @elseif ($courtCoachGrossCents > 0 || $b->amount_cents !== null)
                        <div class="flex items-baseline justify-between gap-4 px-4 py-3 text-sm">
                            <dt class="text-zinc-600 dark:text-zinc-400">Court &amp; coach</dt>
                            <dd class="shrink-0 tabular-nums font-medium text-zinc-900 dark:text-zinc-100">
                                {{ Money::formatMinor($courtCoachGrossCents, $currency) }}
                            </dd>
                        </div>
                    @endif

                    @if ($giftRedeemedCents > 0)
                        <div class="flex items-baseline justify-between gap-4 px-4 py-3 text-sm">
                            <dt class="text-zinc-600 dark:text-zinc-400">Gift card</dt>
                            <dd class="shrink-0 tabular-nums font-medium text-emerald-700 dark:text-emerald-400">
                                −{{ Money::formatMinor($giftRedeemedCents, $currency) }}
                            </dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-4 bg-white/60 px-4 py-3 text-sm dark:bg-zinc-900/40">
                            <dt class="font-medium text-zinc-700 dark:text-zinc-300">Court &amp; coach after gift</dt>
                            <dd class="shrink-0 tabular-nums font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ Money::formatMinor($amountAfterGiftCents, $currency) }}
                            </dd>
                        </div>
                    @endif

                    @if ($hasPlatformFee)
                        <div class="flex items-baseline justify-between gap-4 px-4 py-3 text-sm">
                            <dt class="text-zinc-600 dark:text-zinc-400">Convenience fee</dt>
                            <dd class="shrink-0 tabular-nums font-medium text-zinc-900 dark:text-zinc-100">
                                {{ Money::formatMinor($platformFeeCents, $currency) }}
                            </dd>
                        </div>
                    @endif

                    <div class="flex items-baseline justify-between gap-4 bg-white px-4 py-4 dark:bg-zinc-900/70">
                        <dt class="text-sm font-bold text-zinc-900 dark:text-white">Total</dt>
                        <dd class="shrink-0 text-lg font-bold tabular-nums text-zinc-900 dark:text-white">
                            {{ Money::formatMinor($linePayableCents, $currency) }}
                        </dd>
                    </div>
                </dl>
            </div>
        @elseif ($b->amount_cents !== null)
            <div>
                <dt class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Amount</dt>
                <dd class="mt-2 font-semibold text-zinc-900 dark:text-zinc-100">
                    {{ Money::formatMinor($b->amount_cents, $currency) }}
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
                <dt class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Gift card code</dt>
                <dd class="mt-2 font-mono text-sm text-zinc-900 dark:text-zinc-100">{{ $b->giftCard->code }}</dd>
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
