@php
    use App\Models\Booking;
    use App\Models\OpenPlayParticipant;
    use App\Support\Money;

    $tz = config('app.timezone', 'UTC');
@endphp

<div class="space-y-10">
    @php
        $firstName = strtok(auth()->user()->name ?? '', ' ') ?: 'there';
    @endphp

    <div class="flex flex-col gap-4 sm:flex-row sm:flex-wrap sm:items-end sm:justify-between">
        <div>
            <h1 class="font-display text-2xl font-extrabold tracking-tight text-zinc-900 dark:text-white">Dashboard</h1>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                Your profile pulse, streak, and what’s next on court.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a
                href="{{ route('account.book') }}"
                wire:navigate
                class="inline-flex items-center rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 px-4 py-2.5 text-sm font-bold text-white shadow-md shadow-emerald-900/20 transition hover:from-emerald-500 hover:to-teal-500"
            >
                Book Now
            </a>
            <a
                href="{{ route('account.bookings') }}"
                wire:navigate
                class="inline-flex items-center rounded-xl border border-zinc-200 bg-white px-4 py-2.5 text-sm font-bold text-zinc-800 shadow-sm transition hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
            >
                All bookings
            </a>
            <a
                href="{{ route('account.settings') }}"
                wire:navigate
                class="inline-flex items-center rounded-xl border border-zinc-200 bg-white px-4 py-2.5 text-sm font-bold text-zinc-800 shadow-sm transition hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
            >
                Settings
            </a>
        </div>
    </div>

    {{-- Profile hero --}}
    <section
        class="overflow-hidden rounded-2xl border border-emerald-200/80 bg-gradient-to-br from-emerald-50 via-white to-teal-50/90 p-6 shadow-sm dark:border-emerald-900/40 dark:from-emerald-950/40 dark:via-zinc-900 dark:to-teal-950/30 sm:p-8"
    >
        <div class="flex flex-col gap-8 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-4">
                <div
                    class="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-emerald-600 font-display text-xl font-bold uppercase text-white shadow-inner shadow-emerald-900/20 dark:bg-emerald-500 sm:size-16 sm:text-2xl"
                    aria-hidden="true"
                >
                    {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(trim(auth()->user()->name ?? '?'), 0, 1)) }}
                </div>
                <div>
                    <p class="font-display text-lg font-bold tracking-tight text-zinc-900 dark:text-white">
                        Hey {{ $firstName }}
                    </p>
                    <p class="mt-1 text-sm text-emerald-900/80 dark:text-emerald-200/90">
                        You’re an active player — keep the rallies coming.
                    </p>
                </div>
            </div>
            <div class="grid flex-1 grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-emerald-200/70 bg-white/80 px-4 py-4 dark:border-emerald-800/50 dark:bg-zinc-950/60">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-emerald-700/90 dark:text-emerald-400/90">
                        Hours this week
                    </p>
                    <p class="mt-1 font-display text-3xl font-extrabold tabular-nums text-zinc-900 dark:text-white">
                        {{ $bookingStats['hours_this_week'] }}
                    </p>
                    <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">Court time booked</p>
                </div>
                <div class="rounded-xl border border-emerald-200/70 bg-white/80 px-4 py-4 dark:border-emerald-800/50 dark:bg-zinc-950/60">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-emerald-700/90 dark:text-emerald-400/90">
                        Bookings this month
                    </p>
                    <p class="mt-1 font-display text-3xl font-extrabold tabular-nums text-zinc-900 dark:text-white">
                        {{ $bookingStats['bookings_this_month'] }}
                    </p>
                    <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">Confirmed &amp; completed</p>
                </div>
                <div class="rounded-xl border border-emerald-200/70 bg-white/80 px-4 py-4 dark:border-emerald-800/50 dark:bg-zinc-950/60">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-emerald-700/90 dark:text-emerald-400/90">
                        Favorite venue
                    </p>
                    <p class="mt-1 line-clamp-2 font-display text-lg font-bold leading-snug text-zinc-900 dark:text-white">
                        {{ $bookingStats['favorite_venue'] ?? 'Not yet — explore Book Now' }}
                    </p>
                    <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">Most visits</p>
                </div>
            </div>
        </div>
        @if ($lastBookingForRepeat !== null)
            <div
                class="mt-6 flex flex-col gap-4 rounded-xl border border-emerald-200/80 bg-white/70 px-4 py-4 dark:border-emerald-800/50 dark:bg-zinc-950/50 sm:flex-row sm:items-center sm:justify-between sm:px-5"
            >
                <div class="min-w-0">
                    <p class="font-display text-sm font-bold text-zinc-900 dark:text-white">Book again</p>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                        Same venue, duration, and time band — we’ll aim for the next matching day (weekends → next weekend
                        slot, weekdays → your next weekday).
                        @if ($lastBookingForRepeat->courtClient)
                            <span class="font-medium text-zinc-800 dark:text-zinc-200">
                                Last: {{ $lastBookingForRepeat->courtClient->name }}
                            </span>
                        @endif
                    </p>
                </div>
                <a
                    href="{{ route('account.book.again') }}"
                    class="inline-flex shrink-0 items-center justify-center rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 px-5 py-3 text-center text-sm font-bold text-amber-950 shadow-md shadow-amber-900/20 transition hover:from-amber-400 hover:to-orange-400 dark:text-amber-950"
                >
                    Book again
                </a>
            </div>
        @endif
    </section>

    {{-- Streak --}}
    <section class="rounded-2xl border border-orange-200/90 bg-orange-50/95 p-6 shadow-sm dark:border-orange-900/50 dark:bg-orange-950/35 sm:p-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex items-start gap-3">
                <span class="text-2xl leading-none" aria-hidden="true">🔥</span>
                <div>
                    <h2 class="font-display text-lg font-bold text-orange-950 dark:text-orange-100">
                        @if ($bookingStats['streak_weeks'] > 0)
                            {{ $bookingStats['streak_weeks'] }}-week streak
                        @else
                            Build your streak
                        @endif
                    </h2>
                    <p class="mt-1 text-sm leading-relaxed text-orange-950/85 dark:text-orange-100/85">
                        Each week counts when you book at least once (confirmed or completed).
                    </p>
                    @if ($bookingStats['streak_weeks'] > 0 && ! $bookingStats['this_week_has_booking'])
                        <p class="mt-3 text-sm font-semibold text-orange-950 dark:text-orange-50">
                            Book this week to keep your streak alive.
                        </p>
                    @elseif ($bookingStats['streak_weeks'] === 0)
                        <p class="mt-3 text-sm font-semibold text-orange-950 dark:text-orange-50">
                            Book at least once per week to grow a 🔥 streak you won’t want to lose.
                        </p>
                    @endif
                </div>
            </div>
            <a
                href="{{ route('account.book') }}"
                wire:navigate
                class="inline-flex shrink-0 items-center justify-center rounded-xl bg-orange-600 px-4 py-2.5 text-sm font-bold text-white shadow-md shadow-orange-900/25 transition hover:bg-orange-700 dark:bg-orange-500 dark:hover:bg-orange-400"
            >
                Book Now
            </a>
        </div>
    </section>

    {{-- Personal stats --}}
    <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/80 sm:p-8">
        <div class="flex flex-wrap items-center gap-2">
            <span aria-hidden="true">🧠</span>
            <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Personal stats</h2>
        </div>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
            Light reminders of how often you hit the courts.
        </p>
        <dl class="mt-6 grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-zinc-100 bg-zinc-50/80 px-4 py-4 dark:border-zinc-700 dark:bg-zinc-950/60">
                <dt class="text-[11px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Total hours played</dt>
                <dd class="mt-1 font-display text-2xl font-extrabold tabular-nums text-zinc-900 dark:text-white">{{ $bookingStats['total_hours'] }}</dd>
            </div>
            <div class="rounded-xl border border-zinc-100 bg-zinc-50/80 px-4 py-4 dark:border-zinc-700 dark:bg-zinc-950/60">
                <dt class="text-[11px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Games this month</dt>
                <dd class="mt-1 font-display text-2xl font-extrabold tabular-nums text-zinc-900 dark:text-white">{{ $bookingStats['bookings_this_month'] }}</dd>
                <dd class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">Booking sessions · this calendar month</dd>
            </div>
            <div class="rounded-xl border border-zinc-100 bg-zinc-50/80 px-4 py-4 dark:border-zinc-700 dark:bg-zinc-950/60">
                <dt class="text-[11px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Favorite day / time</dt>
                <dd class="mt-1 text-lg font-semibold leading-snug text-zinc-900 dark:text-white">
                    {{ $bookingStats['favorite_day_time'] ?? 'Still learning your rhythm' }}
                </dd>
                <dd class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">Most common slot you book</dd>
            </div>
        </dl>
    </section>

    {{-- GameQ --}}
    <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/80 sm:p-8">
        <div class="flex items-center gap-2">
            <x-app-icon name="squares-2x2" class="size-6 text-emerald-600 dark:text-emerald-400" />
            <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">GameQ — your rivals &amp; partners</h2>
        </div>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
            When you save a GameQ session, we match <span class="font-medium text-zinc-700 dark:text-zinc-300">this name</span> to the roster (case doesn’t matter) and tally who you’ve played. Update your display name in
            <a href="{{ route('account.settings') }}" wire:navigate class="font-semibold text-emerald-700 underline decoration-emerald-600/30 underline-offset-2 hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-300">Settings</a>
            if needed.
        </p>

        @if ($gameqSessionsTotal === 0)
            <p class="mt-4 text-sm text-zinc-600 dark:text-zinc-400">
                You don’t have any saved GameQ sessions yet. Host in
                <a href="{{ route('account.open-play') }}" wire:navigate class="font-semibold text-emerald-700 underline decoration-emerald-600/30 underline-offset-2 hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-300">GameQ</a>
                and save a session to build history here.
            </p>
        @elseif ($gameqProfile['sessions_matched'] === 0)
            <div class="mt-4 rounded-xl border border-amber-200/90 bg-amber-50/90 px-4 py-3 text-sm text-amber-950 dark:border-amber-900/50 dark:bg-amber-950/35 dark:text-amber-100">
                None of your {{ $gameqSessionsTotal }} saved session{{ $gameqSessionsTotal === 1 ? '' : 's' }} include a player whose name matches your profile name. Use the same spelling as your roster entry (or update your display name in Settings) so we can attribute matches to you.
            </div>
        @elseif ($gameqProfile['matches_counted'] === 0 && count($gameqProfile['opponents']) === 0)
            <p class="mt-4 text-sm text-zinc-600 dark:text-zinc-400">
                You’re on the roster in {{ $gameqProfile['sessions_matched'] }} saved session{{ $gameqProfile['sessions_matched'] === 1 ? '' : 's' }}, but there are no finished matches in the log yet.
            </p>
        @else
            <div class="mt-6 space-y-6">
                @if (count($gameqProfile['opponents']) > 0)
                    <div>
                        <p class="text-sm font-semibold text-zinc-600 dark:text-zinc-300">Head-to-head</p>
                        <ul class="mt-3 divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach ($gameqProfile['opponents'] as $row)
                                <li class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 py-3 first:pt-0">
                                    <span class="min-w-0 font-medium text-zinc-900 dark:text-zinc-100">{{ $row['displayName'] }}</span>
                                    <span class="shrink-0 text-right">
                                        <span class="font-mono text-sm font-bold tabular-nums text-emerald-700 dark:text-emerald-400">
                                            {{ $row['wins'] }}W · {{ $row['losses'] }}L
                                        </span>
                                        @if (($row['ties'] ?? 0) > 0)
                                            <span class="ml-2 text-[11px] text-zinc-400 dark:text-zinc-500">{{ $row['ties'] }} tie{{ $row['ties'] === 1 ? '' : 's' }}</span>
                                        @endif
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (count($gameqProfile['partners']) > 0)
                    <div>
                        <p class="text-sm font-semibold text-zinc-600 dark:text-zinc-300">Doubles partners</p>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-500">Same-side games — not wins/losses against them.</p>
                        <ul class="mt-3 divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach ($gameqProfile['partners'] as $row)
                                <li class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 py-3 first:pt-0">
                                    <span class="min-w-0 font-medium text-zinc-900 dark:text-zinc-100">{{ $row['displayName'] }}</span>
                                    <span class="shrink-0 font-mono text-sm tabular-nums text-zinc-600 dark:text-zinc-400">{{ $row['games'] }} {{ $row['games'] === 1 ? 'game' : 'games' }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <p class="text-[11px] leading-relaxed text-zinc-400 dark:text-zinc-500">
                    Based on {{ $gameqProfile['matches_counted'] }} finished match{{ $gameqProfile['matches_counted'] === 1 ? '' : 'es' }} across {{ $gameqProfile['sessions_matched'] }} session{{ $gameqProfile['sessions_matched'] === 1 ? '' : 's' }}. Duplicate names on different people are merged by name.
                </p>
            </div>
        @endif
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
