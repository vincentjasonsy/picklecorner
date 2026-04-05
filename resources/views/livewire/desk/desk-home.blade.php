@php
    $cc = $this->courtClient;
@endphp

<div class="space-y-8">
    @if (! $cc)
        <p class="text-sm text-red-600 dark:text-red-400">No venue is assigned to your desk account.</p>
    @else
        <div
            class="overflow-hidden rounded-2xl border border-stone-200 bg-gradient-to-br from-stone-50 to-teal-50/40 p-6 dark:border-stone-700 dark:from-stone-900 dark:to-teal-950/30 md:p-8"
        >
            <p class="font-display text-xs font-bold uppercase tracking-wider text-teal-700 dark:text-teal-400">
                At the counter
            </p>
            <h3 class="font-display mt-2 text-2xl font-bold text-stone-900 dark:text-white">
                {{ $cc->name }}
            </h3>
            <p class="mt-2 text-sm text-stone-600 dark:text-stone-400">
                {{ $cc->city ?? '—' }}
                ·
                {{ $cc->courts_count }}
                {{ $cc->courts_count === 1 ? 'court' : 'courts' }}
            </p>
        </div>

        <div
            class="overflow-hidden rounded-2xl border border-teal-200/80 bg-teal-50/90 p-5 text-sm text-teal-950 dark:border-teal-900/50 dark:bg-teal-950/40 dark:text-teal-100"
        >
            <p class="font-semibold text-teal-900 dark:text-teal-100">How requests work</p>
            <p class="mt-2 leading-relaxed text-teal-900/90 dark:text-teal-200/95">
                {{ $cc->deskBookingPolicyHelpText() }}
            </p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <a
                href="{{ route('desk.courts-live') }}"
                wire:navigate
                class="group overflow-hidden rounded-2xl border-2 border-stone-200 bg-white p-6 shadow-sm transition-all hover:border-teal-400 hover:shadow-md dark:border-stone-700 dark:bg-stone-900 dark:hover:border-teal-600"
            >
                <p class="text-xs font-bold uppercase tracking-wider text-stone-500 dark:text-stone-400">
                    Courts live
                </p>
                <p
                    class="mt-3 font-display text-lg font-bold text-teal-700 group-hover:text-teal-600 dark:text-teal-400 dark:group-hover:text-teal-300"
                >
                    Who is playing now →
                </p>
                <p class="mt-2 text-sm text-stone-600 dark:text-stone-400">
                    Current guest and next in line per court
                </p>
            </a>
            <a
                href="{{ route('desk.booking-request') }}"
                wire:navigate
                class="group overflow-hidden rounded-2xl border-2 border-stone-200 bg-white p-6 shadow-sm transition-all hover:border-teal-400 hover:shadow-md dark:border-stone-700 dark:bg-stone-900 dark:hover:border-teal-600"
            >
                <p class="text-xs font-bold uppercase tracking-wider text-stone-500 dark:text-stone-400">
                    New request
                </p>
                <p
                    class="mt-3 font-display text-lg font-bold text-teal-700 group-hover:text-teal-600 dark:text-teal-400 dark:group-hover:text-teal-300"
                >
                    Open booking grid →
                </p>
                <p class="mt-2 text-sm text-stone-600 dark:text-stone-400">Court, time slot, and player</p>
            </a>
            <a
                href="{{ route('desk.my-requests') }}"
                wire:navigate
                class="group overflow-hidden rounded-2xl border-2 border-stone-200 bg-white p-6 shadow-sm transition-all hover:border-teal-400 hover:shadow-md dark:border-stone-700 dark:bg-stone-900 dark:hover:border-teal-600"
            >
                <p class="text-xs font-bold uppercase tracking-wider text-stone-500 dark:text-stone-400">
                    My requests
                </p>
                <p class="font-display mt-3 text-4xl font-bold text-stone-900 dark:text-white">
                    {{ $this->pendingMySubmissions }}
                </p>
                <p class="mt-2 text-sm text-stone-600 dark:text-stone-400">Waiting on venue approval</p>
            </a>
        </div>
    @endif
</div>
