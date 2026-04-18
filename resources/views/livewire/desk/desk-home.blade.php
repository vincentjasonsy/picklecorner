@php
    use App\Models\Booking;

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

        <section
            class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-900/80"
            aria-labelledby="desk-daily-heading"
        >
            <div
                class="flex flex-col gap-4 border-b border-stone-200 px-5 py-4 dark:border-stone-700 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between"
            >
                <div>
                    <h2
                        id="desk-daily-heading"
                        class="font-display text-lg font-bold text-stone-900 dark:text-white"
                    >
                        Daily schedule
                    </h2>
                    <p class="mt-0.5 text-sm text-stone-600 dark:text-stone-400">
                        {{ $this->dailyViewLabel() }}
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        wire:click="shiftDaily(-1)"
                        aria-label="Previous day"
                        class="inline-flex items-center justify-center rounded-lg border border-stone-200 px-2 py-2 text-stone-700 hover:bg-stone-50 dark:border-stone-600 dark:text-stone-200 dark:hover:bg-stone-800"
                    >
                        <span class="sr-only">Previous day</span>
                        <svg class="size-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                        </svg>
                    </button>
                    <button
                        type="button"
                        wire:click="goToToday"
                        class="rounded-lg border border-teal-200 bg-teal-50 px-3 py-2 text-xs font-semibold text-teal-900 hover:bg-teal-100 dark:border-teal-800 dark:bg-teal-950/50 dark:text-teal-100 dark:hover:bg-teal-900/40"
                    >
                        Today
                    </button>
                    <button
                        type="button"
                        wire:click="shiftDaily(1)"
                        aria-label="Next day"
                        class="inline-flex items-center justify-center rounded-lg border border-stone-200 px-2 py-2 text-stone-700 hover:bg-stone-50 dark:border-stone-600 dark:text-stone-200 dark:hover:bg-stone-800"
                    >
                        <span class="sr-only">Next day</span>
                        <svg class="size-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </button>
                    <label class="flex items-center gap-2 text-xs font-medium text-stone-600 dark:text-stone-400">
                        <span class="sr-only">Pick date</span>
                        <input
                            type="date"
                            wire:model.live="dailyViewDate"
                            class="rounded-lg border border-stone-200 bg-white px-2 py-2 text-sm text-stone-900 dark:border-stone-600 dark:bg-stone-950 dark:text-stone-100"
                        />
                    </label>
                </div>
            </div>

            <div class="px-5 py-4">
                @if ($this->dailyBookings->isEmpty())
                    <p class="text-sm text-stone-500 dark:text-stone-400">
                        No bookings starting on this day.
                    </p>
                @else
                    <ul class="divide-y divide-stone-100 dark:divide-stone-800" role="list">
                        @foreach ($this->dailyBookings as $row)
                            <li wire:key="desk-daily-{{ $row->id }}">
                                <a
                                    href="{{ route('desk.bookings.show', $row) }}?{{ http_build_query(['from' => 'daily', 'day' => $this->dailyViewDate]) }}"
                                    wire:navigate
                                    class="flex flex-col gap-2 py-4 transition hover:bg-stone-50/80 sm:flex-row sm:items-center sm:justify-between sm:gap-4 dark:hover:bg-stone-800/40"
                                >
                                    <div class="min-w-0">
                                        <p class="font-mono text-sm font-semibold tabular-nums text-stone-900 dark:text-stone-100">
                                            {{ $row->starts_at?->timezone(config('app.timezone'))->format('g:i A') }}
                                            <span class="font-sans font-normal text-stone-400">→</span>
                                            {{ $row->ends_at?->timezone(config('app.timezone'))->format('g:i A') }}
                                        </p>
                                        <p class="mt-0.5 text-sm font-medium text-stone-800 dark:text-stone-200">
                                            {{ $row->court?->name ?? 'Court' }}
                                            <span class="text-stone-400">·</span>
                                            {{ $row->user?->name ?? 'Guest' }}
                                        </p>
                                    </div>
                                    <span
                                        class="inline-flex w-fit shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold {{ match ($row->status) {
                                            Booking::STATUS_CONFIRMED => 'bg-teal-100 text-teal-950 dark:bg-teal-950/40 dark:text-teal-100',
                                            Booking::STATUS_PENDING_APPROVAL => 'bg-amber-100 text-amber-950 dark:bg-amber-950/40 dark:text-amber-100',
                                            Booking::STATUS_DENIED => 'bg-rose-100 text-rose-950 dark:bg-rose-950/40 dark:text-rose-100',
                                            Booking::STATUS_CANCELLED => 'bg-stone-200 text-stone-800 dark:bg-stone-700 dark:text-stone-200',
                                            Booking::STATUS_COMPLETED => 'bg-stone-200 text-stone-800 dark:bg-stone-600 dark:text-stone-100',
                                            default => 'bg-stone-200 text-stone-700 dark:bg-stone-700 dark:text-stone-200',
                                        } }}"
                                    >
                                        {{ Booking::statusDisplayLabel($row->status) }}
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>

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
