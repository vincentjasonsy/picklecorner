@php
    use App\Models\Booking;
    use App\Models\OpenPlayParticipant;
    use App\Support\Money;

    $tz = config('app.timezone', 'UTC');
@endphp

<div class="space-y-8">
    <x-member.guide title="First time here?">
        <p>
            Pick <strong class="font-semibold text-sky-950 dark:text-white">Book Now</strong> in the sidebar to book.
            Everything you’ve reserved shows up below — no spreadsheets required.
        </p>
    </x-member.guide>

    <section
        class="relative overflow-hidden rounded-3xl border border-emerald-200/70 bg-gradient-to-br from-emerald-400/95 via-teal-500 to-cyan-600 p-6 text-white shadow-lg shadow-emerald-900/15 dark:border-emerald-800/40 dark:from-emerald-700/90 dark:via-teal-800 dark:to-cyan-900 sm:p-8"
    >
        <div
            class="pointer-events-none absolute -right-8 -top-8 size-40 rounded-full bg-white/10 blur-2xl"
            aria-hidden="true"
        ></div>
        <div
            class="pointer-events-none absolute -bottom-10 -left-10 size-48 rounded-full bg-black/10 blur-2xl"
            aria-hidden="true"
        ></div>
        <p class="relative text-sm font-semibold text-emerald-50/95">Nice to see you</p>
        <h1 class="relative mt-1 font-display text-3xl font-extrabold tracking-tight sm:text-4xl">
            Hi, {{ $this->firstName }}!
        </h1>
        <p class="relative mt-3 max-w-xl text-base leading-relaxed text-white/95">
            Here’s the simple version: what’s coming up, what you just played, and a shortcut to book again when the
            paddle itch hits.
        </p>
        <div class="relative mt-6 flex flex-wrap gap-2.5">
            <a
                href="{{ route('account.book') }}"
                wire:navigate
                class="inline-flex items-center rounded-xl bg-amber-300 px-4 py-2.5 text-sm font-bold text-amber-950 shadow-md transition hover:bg-amber-200 dark:bg-amber-400 dark:text-amber-950 dark:hover:bg-amber-300"
            >
                Book Now
            </a>
            <a
                href="{{ route('account.bookings') }}"
                wire:navigate
                class="inline-flex items-center rounded-xl bg-white px-4 py-2.5 text-sm font-bold text-emerald-800 shadow-md transition hover:bg-emerald-50"
            >
                All bookings
            </a>
            <a
                href="{{ route('account.settings') }}"
                wire:navigate
                class="inline-flex items-center rounded-xl border-2 border-white/45 bg-white/10 px-4 py-2.5 text-sm font-bold text-white backdrop-blur-sm transition hover:bg-white/20"
            >
                Your profile
            </a>
        </div>
    </section>

    <section class="grid gap-4 sm:grid-cols-3">
        <div
            class="rounded-2xl border border-emerald-200/60 bg-white/95 p-5 shadow-sm dark:border-emerald-900/40 dark:bg-zinc-900/80"
        >
            <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-400">Coming up</p>
            <p class="mt-2 font-display text-3xl font-extrabold text-zinc-900 dark:text-white">
                {{ $this->stats['upcoming'] }}
            </p>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">On your calendar</p>
        </div>
        <div
            class="rounded-2xl border border-teal-200/60 bg-white/95 p-5 shadow-sm dark:border-teal-900/40 dark:bg-zinc-900/80"
        >
            <p class="text-sm font-semibold text-teal-700 dark:text-teal-400">Played</p>
            <p class="mt-2 font-display text-3xl font-extrabold text-zinc-900 dark:text-white">
                {{ $this->stats['played'] }}
            </p>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Past sessions</p>
        </div>
        <div
            class="rounded-2xl border border-cyan-200/60 bg-white/95 p-5 shadow-sm dark:border-cyan-900/40 dark:bg-zinc-900/80"
        >
            <p class="text-sm font-semibold text-cyan-700 dark:text-cyan-400">Done &amp; dusted</p>
            <p class="mt-2 font-display text-3xl font-extrabold text-zinc-900 dark:text-white">
                {{ $this->stats['wins_on_the_board'] }}
            </p>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Completed</p>
        </div>
    </section>

    @if ($this->upcomingOpenPlayJoins->isNotEmpty())
        <section
            class="rounded-2xl border border-violet-200/80 bg-white p-6 shadow-sm dark:border-violet-900/40 dark:bg-zinc-900/80"
        >
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Open plays you’re in</h2>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">They booked the court — you’re on the list.</p>
                </div>
                <a
                    href="{{ route('account.court-open-plays.index') }}"
                    wire:navigate
                    class="text-sm font-bold text-violet-600 hover:text-violet-700 dark:text-violet-400"
                >
                    Manage →
                </a>
            </div>
            <ul class="mt-4 space-y-3">
                @foreach ($this->upcomingOpenPlayJoins as $row)
                    @php
                        $b = $row->booking;
                    @endphp
                    @if ($b)
                        <li
                            class="flex flex-col gap-2 rounded-xl border border-violet-100 bg-violet-50/50 px-4 py-3 dark:border-violet-900/40 dark:bg-violet-950/20 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div>
                                <p class="font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ $b->courtClient?->name ?? 'Venue' }}
                                    · {{ $b->court?->name ?? 'Court' }}
                                </p>
                                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ $b->starts_at?->timezone($tz)->format('D, M j') }}
                                    ·
                                    {{ $b->starts_at?->timezone($tz)->format('g:i A') }}
                                </p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    class="rounded-full bg-white px-2.5 py-0.5 text-xs font-bold text-violet-900 shadow-sm dark:bg-violet-950 dark:text-violet-200"
                                >
                                    @if ($row->status === OpenPlayParticipant::STATUS_ACCEPTED)
                                        In
                                    @elseif ($row->status === OpenPlayParticipant::STATUS_WAITING_LIST)
                                        Waitlist
                                    @else
                                        Pending
                                    @endif
                                </span>
                                <a
                                    href="{{ route('account.court-open-plays.join', $b) }}"
                                    wire:navigate
                                    class="text-xs font-bold text-violet-600 hover:text-violet-700 dark:text-violet-400"
                                >
                                    Details
                                </a>
                            </div>
                        </li>
                    @endif
                @endforeach
            </ul>
        </section>
    @endif

    <div class="grid gap-8 lg:grid-cols-2">
        <section
            class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/80"
        >
            <div class="flex items-center justify-between gap-2">
                <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Next on court</h2>
                <x-app-icon name="calendar" class="size-7 text-emerald-600 dark:text-emerald-400" />
            </div>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Your upcoming reservations</p>
            <ul class="mt-5 space-y-3">
                @forelse ($this->upcomingBookings as $b)
                    <li
                        class="flex flex-col gap-1 rounded-xl border border-zinc-100 bg-zinc-50/80 px-4 py-3 dark:border-zinc-800 dark:bg-zinc-950/50 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ $b->courtClient?->name ?? 'Venue' }}
                            </p>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $b->court?->name ?? 'Court' }}
                                ·
                                {{ $b->starts_at?->timezone($tz)->format('D, M j') }}
                                ·
                                {{ $b->starts_at?->timezone($tz)->format('g:i A') }}
                            </p>
                        </div>
                        <div class="flex shrink-0 flex-col items-end gap-2 sm:flex-row sm:items-center">
                            <span
                                class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-bold text-emerald-900 dark:bg-emerald-950/60 dark:text-emerald-200"
                            >
                                {{ Booking::statusDisplayLabel($b->status) }}
                            </span>
                            <a
                                href="{{ route('account.bookings.show', $b) }}"
                                wire:navigate
                                class="text-xs font-bold text-emerald-700 hover:text-emerald-800 dark:text-emerald-400"
                            >
                                View details
                            </a>
                        </div>
                    </li>
                @empty
                    <li class="rounded-xl border border-dashed border-zinc-200 py-10 text-center dark:border-zinc-700">
                        <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">No upcoming games yet.</p>
                        <p class="mt-1 text-xs text-zinc-500">Grab a slot from Book Now — it’ll pop in here.</p>
                    </li>
                @endforelse
            </ul>
        </section>

        <section
            class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/80"
        >
            <div class="flex items-center justify-between gap-2">
                <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Recently</h2>
                <x-app-icon name="clock" class="size-7 text-emerald-600 dark:text-emerald-400" />
            </div>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">A peek at where you’ve been</p>
            <ul class="mt-5 space-y-3">
                @forelse ($this->recentBookings as $b)
                    <li
                        class="rounded-xl border border-zinc-100 bg-zinc-50/80 px-4 py-3 dark:border-zinc-800 dark:bg-zinc-950/50"
                    >
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <p class="font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ $b->courtClient?->name ?? 'Venue' }}
                            </p>
                            @if ($b->amount_cents !== null)
                                <p class="text-sm font-bold text-emerald-700 dark:text-emerald-400">
                                    {{ Money::formatMinor($b->amount_cents, $b->currency) }}
                                </p>
                            @endif
                        </div>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                            {{ $b->starts_at?->timezone($tz)->format('M j, Y') }}
                            ·
                            {{ Booking::statusDisplayLabel($b->status) }}
                        </p>
                    </li>
                @empty
                    <li class="rounded-xl border border-dashed border-zinc-200 py-10 text-center dark:border-zinc-700">
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">Your match history will land here.</p>
                    </li>
                @endforelse
            </ul>
            <a
                href="{{ route('account.bookings') }}"
                wire:navigate
                class="mt-5 inline-flex text-sm font-bold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300"
            >
                Open full list →
            </a>
        </section>
    </div>
</div>
