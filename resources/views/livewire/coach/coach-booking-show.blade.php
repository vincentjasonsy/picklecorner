@php
    use App\Models\Booking;
    use App\Support\Money;

    $tz = config('app.timezone', 'UTC');
    $b = $booking;
@endphp

<div class="mx-auto max-w-2xl space-y-6">
    <a
        href="{{ $this->calendarUrl() }}"
        wire:navigate
        class="inline-flex text-sm font-semibold text-violet-700 hover:text-violet-800 dark:text-violet-300 dark:hover:text-violet-200"
    >
        ← Back to calendar
    </a>

    <div>
        <h1 class="font-display text-2xl font-bold text-zinc-900 dark:text-white">Coached session</h1>
        <span
            class="mt-2 inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ match ($b->status) {
                Booking::STATUS_CONFIRMED, Booking::STATUS_COMPLETED => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200',
                Booking::STATUS_PENDING_APPROVAL => 'bg-amber-100 text-amber-900 dark:bg-amber-950/50 dark:text-amber-200',
                Booking::STATUS_CANCELLED => 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200',
                Booking::STATUS_DENIED => 'bg-red-100 text-red-800 dark:bg-red-950/50 dark:text-red-200',
                default => 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200',
            } }}"
        >
            {{ Booking::statusDisplayLabel($b->status) }}
        </span>
    </div>

    <dl class="grid gap-4 rounded-2xl border border-zinc-200 bg-white p-6 text-sm dark:border-zinc-800 dark:bg-zinc-900 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <dt class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">When</dt>
            <dd class="mt-1 text-zinc-900 dark:text-zinc-100">
                {{ $b->starts_at?->timezone($tz)->isoFormat('dddd, MMM D, YYYY · h:mm a') ?? '—' }}
                <span class="text-zinc-500">→</span>
                {{ $b->ends_at?->timezone($tz)->isoFormat('h:mm a') ?? '—' }}
            </dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Venue</dt>
            <dd class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">{{ $b->courtClient?->name ?? '—' }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Court</dt>
            <dd class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">{{ $b->court?->name ?? '—' }}</dd>
        </div>
        <div class="sm:col-span-2">
            <dt class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Player</dt>
            <dd class="mt-1 text-zinc-900 dark:text-zinc-100">
                @if ($b->user)
                    <span class="font-medium">{{ $b->user->name }}</span>
                    <span class="mt-0.5 block text-xs text-zinc-500">{{ $b->user->email }}</span>
                @else
                    —
                @endif
            </dd>
        </div>
        @if ($b->coach_fee_cents > 0)
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                    Coaching fee
                </dt>
                <dd class="mt-1 text-zinc-900 dark:text-zinc-100">
                    {{ Money::formatMinor($b->coach_fee_cents, $b->currency ?? 'PHP') }}
                </dd>
            </div>
        @endif
    </dl>
</div>
