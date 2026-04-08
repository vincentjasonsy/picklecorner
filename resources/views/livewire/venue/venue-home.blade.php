@php
    $cc = $this->courtClient;
@endphp

<div class="space-y-8">
    @if (! $cc)
        <p class="text-sm text-red-600 dark:text-red-400">No venue is linked to your account.</p>
    @else
        <div>
            <h2 class="font-display text-xl font-bold text-zinc-900 dark:text-white">{{ $cc->name }}</h2>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                {{ $cc->city ?? '—' }}
                ·
                {{ $cc->courts_count }}
                {{ $cc->courts_count === 1 ? 'court' : 'courts' }}
            </p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-4">
            <a
                href="{{ route('venue.bookings.pending') }}"
                wire:navigate
                class="rounded-xl border border-zinc-200 bg-white p-5 transition-colors hover:border-emerald-300 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-emerald-800"
            >
                <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Manual booking requests</p>
                <p class="mt-2 font-display text-2xl font-bold text-zinc-900 dark:text-white">
                    {{ $this->pendingDeskBookings }}
                </p>
                <p class="mt-1 text-xs text-zinc-500">Pending from desk · {{ $cc->deskBookingPolicyShortLabel() }}</p>
            </a>
            <a
                href="{{ route('venue.courts') }}"
                wire:navigate
                class="rounded-xl border border-zinc-200 bg-white p-5 transition-colors hover:border-emerald-300 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-emerald-800"
            >
                <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Court changes</p>
                <p class="mt-2 font-display text-2xl font-bold text-zinc-900 dark:text-white">
                    {{ $this->pendingCourtRequests }}
                </p>
                <p class="mt-1 text-xs text-zinc-500">Awaiting super admin</p>
            </a>
            <a
                href="{{ route('venue.manual-booking') }}"
                wire:navigate
                class="rounded-xl border border-zinc-200 bg-white p-5 transition-colors hover:border-emerald-300 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-emerald-800"
            >
                <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Manual booking</p>
                <p class="mt-2 text-sm font-medium text-emerald-600 dark:text-emerald-400">Open grid →</p>
                <p class="mt-1 text-xs text-zinc-500">Create confirmed bookings</p>
            </a>
            <a
                href="{{ route('venue.crm.index') }}"
                wire:navigate
                class="rounded-xl border border-zinc-200 bg-white p-5 transition-colors hover:border-emerald-300 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-emerald-800"
            >
                <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Customers</p>
                <p class="mt-2 text-sm font-medium text-emerald-600 dark:text-emerald-400">Open list →</p>
                <p class="mt-1 text-xs text-zinc-500">
                    @if ($cc->hasPremiumSubscription())
                        Everyone who booked here · notes &amp; summaries
                    @else
                        Everyone who booked here · summaries ·
                        <span class="text-amber-800 dark:text-amber-200">notes with Premium</span>
                    @endif
                </p>
            </a>
        </div>
    @endif
</div>
