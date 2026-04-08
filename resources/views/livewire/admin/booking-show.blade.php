@php
    use App\Models\Booking;
    use App\Support\Money;

    $tz = config('app.timezone');
    $b = $booking;
@endphp

<div class="mx-auto max-w-3xl space-y-8">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a
            href="{{ $this->historyUrl() }}"
            wire:navigate
            class="text-sm font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400"
        >
            ← Back to history
        </a>
        <a
            href="{{ route('admin.reports') }}"
            wire:navigate
            class="text-sm text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-200"
        >
            Reports
        </a>
    </div>

    <div>
        <div class="flex flex-wrap items-center gap-3">
            <h1 class="font-display text-2xl font-bold text-zinc-900 dark:text-white">Booking summary</h1>
            <span
                class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $this->statusBadgeClasses($b->status) }}"
            >
                {{ Booking::statusDisplayLabel($b->status) }}
            </span>
        </div>
        <p class="mt-1 font-mono text-xs text-zinc-500 dark:text-zinc-400">{{ $b->id }}</p>
    </div>

    <dl class="grid gap-4 rounded-xl border border-zinc-200 bg-white p-6 text-sm dark:border-zinc-800 dark:bg-zinc-900 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <dt class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                Schedule
            </dt>
            <dd class="mt-1 text-zinc-900 dark:text-zinc-100">
                {{ $b->starts_at?->timezone($tz)->isoFormat('dddd, MMM D, YYYY · h:mm a') }}
                <span class="text-zinc-500">→</span>
                {{ $b->ends_at?->timezone($tz)->isoFormat('h:mm a') }}
            </dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Venue</dt>
            <dd class="mt-1">
                @if ($b->courtClient)
                    <a
                        href="{{ route('admin.court-clients.edit', $b->courtClient) }}"
                        wire:navigate
                        class="font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400"
                    >
                        {{ $b->courtClient->name }}
                    </a>
                @else
                    —
                @endif
            </dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Court</dt>
            <dd class="mt-1 text-zinc-800 dark:text-zinc-200">{{ $b->court?->name ?? '—' }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Guest</dt>
            <dd class="mt-1 text-zinc-800 dark:text-zinc-200">
                @if ($b->user)
                    <span class="font-medium">{{ $b->user->name }}</span>
                    <span class="mt-0.5 block text-xs text-zinc-500">{{ $b->user->email }}</span>
                    <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1">
                        <a
                            href="{{ route('admin.users.summary', $b->user) }}"
                            wire:navigate
                            class="text-xs font-semibold text-emerald-600 dark:text-emerald-400"
                        >
                            View summary
                        </a>
                        <a
                            href="{{ route('admin.users.edit', $b->user) }}"
                            wire:navigate
                            class="text-xs font-semibold text-emerald-600 dark:text-emerald-400"
                        >
                            Edit user
                        </a>
                    </div>
                @else
                    —
                @endif
            </dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                Desk submitted by
            </dt>
            <dd class="mt-1 text-zinc-800 dark:text-zinc-200">{{ $b->deskSubmitter?->name ?? '—' }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Amount</dt>
            <dd class="mt-1 text-zinc-800 dark:text-zinc-200">
                {{ Money::formatMinor($b->amount_cents, $b->currency) }}
            </dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                Payment method
            </dt>
            <dd class="mt-1 text-zinc-800 dark:text-zinc-200">
                {{ $b->payment_method ? Booking::paymentMethodLabel($b->payment_method) : '—' }}
            </dd>
        </div>
        @if ($b->payment_reference)
            <div class="sm:col-span-2">
                <dt class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                    Payment reference
                </dt>
                <dd class="mt-1 break-all font-mono text-xs text-zinc-800 dark:text-zinc-200">
                    {{ $b->payment_reference }}
                </dd>
            </div>
        @endif
        @if ($b->giftCard)
            <div class="sm:col-span-2">
                <dt class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                    Gift card
                </dt>
                <dd class="mt-1">
                    <a
                        href="{{ route('admin.gift-cards.show', $b->giftCard) }}"
                        wire:navigate
                        class="font-mono font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400"
                    >
                        {{ $b->giftCard->code }}
                    </a>
                    @if ($b->gift_card_redeemed_cents !== null)
                        <span class="ml-2 text-zinc-600 dark:text-zinc-400">
                            Redeemed: {{ Money::formatMinor($b->gift_card_redeemed_cents, $b->currency) }}
                        </span>
                    @endif
                </dd>
            </div>
        @endif
        @if ($b->notes)
            <div class="sm:col-span-2">
                <dt class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Notes</dt>
                <dd class="mt-1 whitespace-pre-wrap text-zinc-700 dark:text-zinc-300">{{ $b->notes }}</dd>
            </div>
        @endif
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Created</dt>
            <dd class="mt-1 text-zinc-600 dark:text-zinc-400">
                {{ $b->created_at?->timezone($tz)->isoFormat('MMM D, YYYY h:mm a') }}
            </dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Updated</dt>
            <dd class="mt-1 text-zinc-600 dark:text-zinc-400">
                {{ $b->updated_at?->timezone($tz)->isoFormat('MMM D, YYYY h:mm a') }}
            </dd>
        </div>
    </dl>

    @if ($b->paymentProofUrl())
        @php
            $proofPath = $b->payment_proof_path ?? '';
            $proofIsImage = $proofPath !== '' && preg_match('/\.(jpe?g|png|gif|webp)$/i', $proofPath) === 1;
        @endphp
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="font-display text-base font-bold text-zinc-900 dark:text-white">Payment proof</h2>
            <a
                href="{{ $b->paymentProofUrl() }}"
                target="_blank"
                rel="noopener noreferrer"
                class="mt-3 inline-block text-sm font-semibold text-emerald-600 dark:text-emerald-400"
            >
                Open file
            </a>
            @if ($proofIsImage)
                <div class="mt-4 overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                    <img
                        src="{{ $b->paymentProofUrl() }}"
                        alt="Payment proof"
                        class="max-h-96 w-full object-contain bg-zinc-50 dark:bg-zinc-950"
                    />
                </div>
            @endif
        </div>
    @endif
</div>
