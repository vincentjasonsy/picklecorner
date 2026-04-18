@php
    use App\Models\Booking;
    use App\Models\OpenPlayParticipant;
    use App\Support\Money;

    $tz = config('app.timezone', 'UTC');
    $initial = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(trim(auth()->user()->name ?? '?'), 0, 1));
    $firstName = strtok(auth()->user()->name ?? '', ' ') ?: 'there';
@endphp

{{-- Shared card shell: one visual system across the page --}}
<div class="space-y-8">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:flex-wrap sm:items-end sm:justify-between">
        <div>
            <h1 class="font-display text-2xl font-extrabold tracking-tight text-zinc-900 dark:text-white">Dashboard</h1>
            <p class="mt-1.5 text-sm text-zinc-600 dark:text-zinc-400">Bookings, stats, and GameQ — organized in sections below.</p>
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

    {{-- 1. Overview: identity + booking snapshot --}}
    <section
        aria-labelledby="dash-overview-heading"
        class="rounded-2xl border border-zinc-200/90 bg-white p-6 shadow-sm ring-1 ring-black/[0.03] dark:border-zinc-800 dark:bg-zinc-900/85 dark:ring-white/[0.04] sm:p-8"
    >
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
            <div class="flex gap-4">
                <div
                    class="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-emerald-600 font-display text-xl font-bold text-white shadow-inner shadow-emerald-900/25 dark:bg-emerald-500 sm:size-16 sm:text-2xl"
                    aria-hidden="true"
                >
                    {{ $initial }}
                </div>
                <div class="min-w-0">
                    <h2 id="dash-overview-heading" class="font-display text-lg font-bold text-zinc-900 dark:text-white">
                        Hey {{ $firstName }}
                    </h2>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Quick snapshot of bookings and counts.</p>
                </div>
            </div>
        </div>

        <div class="mt-8 grid gap-3 sm:grid-cols-3">
            <div class="rounded-xl border border-emerald-200/70 bg-emerald-50/40 px-4 py-4 dark:border-emerald-900/40 dark:bg-emerald-950/25">
                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-800 dark:text-emerald-400">Coming up</p>
                <p class="mt-1 font-display text-3xl font-extrabold tabular-nums text-zinc-900 dark:text-white">{{ $this->stats['upcoming'] }}</p>
                <p class="mt-0.5 text-xs text-zinc-600 dark:text-zinc-400">Future bookings</p>
            </div>
            <div class="rounded-xl border border-teal-200/70 bg-teal-50/40 px-4 py-4 dark:border-teal-900/40 dark:bg-teal-950/25">
                <p class="text-xs font-semibold uppercase tracking-wide text-teal-800 dark:text-teal-400">Played</p>
                <p class="mt-1 font-display text-3xl font-extrabold tabular-nums text-zinc-900 dark:text-white">{{ $this->stats['played'] }}</p>
                <p class="mt-0.5 text-xs text-zinc-600 dark:text-zinc-400">Past sessions</p>
            </div>
            <div class="rounded-xl border border-cyan-200/70 bg-cyan-50/40 px-4 py-4 dark:border-cyan-900/40 dark:bg-cyan-950/25">
                <p class="text-xs font-semibold uppercase tracking-wide text-cyan-800 dark:text-cyan-400">Completed</p>
                <p class="mt-1 font-display text-3xl font-extrabold tabular-nums text-zinc-900 dark:text-white">{{ $this->stats['wins_on_the_board'] }}</p>
                <p class="mt-0.5 text-xs text-zinc-600 dark:text-zinc-400">Marked complete</p>
            </div>
        </div>

        @if ($lastBookingForRepeat !== null)
            <div
                class="mt-6 flex flex-col gap-3 rounded-xl border border-amber-200/80 bg-amber-50/60 px-4 py-4 dark:border-amber-900/45 dark:bg-amber-950/30 sm:flex-row sm:items-center sm:justify-between sm:px-5"
            >
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-zinc-900 dark:text-white">Repeat your last booking</p>
                    <p class="mt-0.5 text-sm text-zinc-600 dark:text-zinc-400">
                        @if ($lastBookingForRepeat->courtClient)
                            {{ $lastBookingForRepeat->courtClient->name }}
                            · same duration &amp; time band, next matching day.
                        @else
                            Same venue, duration &amp; time — next matching day.
                        @endif
                    </p>
                </div>
                <a
                    href="{{ route('account.book.again') }}"
                    class="inline-flex shrink-0 items-center justify-center rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 px-5 py-2.5 text-sm font-bold text-amber-950 shadow-sm transition hover:from-amber-400 hover:to-orange-400 dark:text-amber-950"
                >
                    Book again
                </a>
            </div>
        @endif
    </section>

    {{-- 2. Activity: streak + rolling stats (single card, nested tiles) --}}
    <section
        aria-labelledby="dash-activity-heading"
        class="rounded-2xl border border-zinc-200/90 bg-white p-6 shadow-sm ring-1 ring-black/[0.03] dark:border-zinc-800 dark:bg-zinc-900/85 dark:ring-white/[0.04] sm:p-8"
    >
        <div class="flex flex-col gap-4 border-b border-zinc-100 pb-6 dark:border-zinc-800 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 id="dash-activity-heading" class="font-display text-lg font-bold text-zinc-900 dark:text-white">Activity</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">How often you’re on court and your weekly streak.</p>
            </div>
            <a
                href="{{ route('account.book') }}"
                wire:navigate
                class="inline-flex shrink-0 items-center justify-center rounded-xl bg-orange-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-orange-700 dark:bg-orange-500 dark:hover:bg-orange-400"
            >
                Keep it going — Book Now
            </a>
        </div>

        <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-orange-200/80 bg-orange-50/70 px-4 py-4 dark:border-orange-900/45 dark:bg-orange-950/35">
                <p class="text-xs font-semibold uppercase tracking-wide text-orange-900 dark:text-orange-300">Streak</p>
                <p class="mt-1 flex items-baseline gap-2 font-display text-2xl font-extrabold text-zinc-900 dark:text-white">
                    @if ($bookingStats['streak_weeks'] > 0)
                        <span aria-hidden="true">🔥</span> {{ $bookingStats['streak_weeks'] }} wk
                    @else
                        <span class="text-lg font-bold">—</span>
                    @endif
                </p>
                <p class="mt-1 text-xs text-zinc-600 dark:text-zinc-400">At least one booking / week</p>
                @if ($bookingStats['streak_weeks'] > 0 && ! $bookingStats['this_week_has_booking'])
                    <p class="mt-2 text-xs font-medium text-orange-900 dark:text-orange-200">Book this week to continue.</p>
                @elseif ($bookingStats['streak_weeks'] === 0)
                    <p class="mt-2 text-xs font-medium text-orange-900 dark:text-orange-200">Book weekly to start a streak.</p>
                @endif
            </div>
            <div class="rounded-xl border border-zinc-200/80 bg-zinc-50/80 px-4 py-4 dark:border-zinc-700 dark:bg-zinc-950/50">
                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-400">Hours this week</p>
                <p class="mt-1 font-display text-2xl font-extrabold tabular-nums text-zinc-900 dark:text-white">{{ $bookingStats['hours_this_week'] }}</p>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-500">Court time booked</p>
            </div>
            <div class="rounded-xl border border-zinc-200/80 bg-zinc-50/80 px-4 py-4 dark:border-zinc-700 dark:bg-zinc-950/50">
                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-400">Bookings (month)</p>
                <p class="mt-1 font-display text-2xl font-extrabold tabular-nums text-zinc-900 dark:text-white">{{ $bookingStats['bookings_this_month'] }}</p>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-500">Confirmed &amp; completed</p>
            </div>
            <div class="rounded-xl border border-zinc-200/80 bg-zinc-50/80 px-4 py-4 dark:border-zinc-700 dark:bg-zinc-950/50">
                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-400">Total hours</p>
                <p class="mt-1 font-display text-2xl font-extrabold tabular-nums text-zinc-900 dark:text-white">{{ $bookingStats['total_hours'] }}</p>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-500">All time</p>
            </div>
        </div>

        <dl class="mt-6 grid gap-3 sm:grid-cols-2">
            <div class="rounded-xl border border-zinc-100 bg-white px-4 py-4 dark:border-zinc-700 dark:bg-zinc-950/40">
                <dt class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Favorite venue</dt>
                <dd class="mt-1 font-semibold leading-snug text-zinc-900 dark:text-white">{{ $bookingStats['favorite_venue'] ?? '—' }}</dd>
            </div>
            <div class="rounded-xl border border-zinc-100 bg-white px-4 py-4 dark:border-zinc-700 dark:bg-zinc-950/40">
                <dt class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Favorite day / time</dt>
                <dd class="mt-1 font-semibold leading-snug text-zinc-900 dark:text-white">{{ $bookingStats['favorite_day_time'] ?? 'Still learning your rhythm' }}</dd>
            </div>
        </dl>
    </section>

    {{-- 3. GameQ --}}
    <section
        aria-labelledby="dash-gameq-heading"
        class="rounded-2xl border border-zinc-200/90 bg-white p-6 shadow-sm ring-1 ring-black/[0.03] dark:border-zinc-800 dark:bg-zinc-900/85 dark:ring-white/[0.04] sm:p-8"
    >
        <div class="flex flex-wrap items-start gap-3">
            <x-app-icon name="squares-2x2" class="size-7 shrink-0 text-emerald-600 dark:text-emerald-400" />
            <div class="min-w-0 flex-1">
                <h2 id="dash-gameq-heading" class="font-display text-lg font-bold text-zinc-900 dark:text-white">GameQ</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    Rivals &amp; partners from saved sessions. Names match your profile —
                    <a href="{{ route('account.settings') }}" wire:navigate class="font-semibold text-emerald-700 underline decoration-emerald-600/30 underline-offset-2 hover:text-emerald-800 dark:text-emerald-400">edit in Settings</a>.
                </p>
            </div>
        </div>

        <div class="mt-6 border-t border-zinc-100 pt-6 dark:border-zinc-800">
            @if ($gameqSessionsTotal === 0)
                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                    No saved sessions yet.
                    <a href="{{ route('account.open-play') }}" wire:navigate class="font-semibold text-emerald-700 hover:text-emerald-800 dark:text-emerald-400">Open GameQ</a>
                    and save one to see history here.
                </p>
            @elseif ($gameqProfile['sessions_matched'] === 0)
                <div class="rounded-xl border border-amber-200/90 bg-amber-50/90 px-4 py-3 text-sm text-amber-950 dark:border-amber-900/50 dark:bg-amber-950/35 dark:text-amber-100">
                    None of your {{ $gameqSessionsTotal }} saved session{{ $gameqSessionsTotal === 1 ? '' : 's' }} match your profile name. Align your roster name with
                    <a href="{{ route('account.settings') }}" wire:navigate class="font-semibold underline">Settings</a>.
                </div>
            @elseif ($gameqProfile['matches_counted'] === 0 && count($gameqProfile['opponents']) === 0)
                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                    You’re on the roster in {{ $gameqProfile['sessions_matched'] }} session{{ $gameqProfile['sessions_matched'] === 1 ? '' : 's' }}, but no finished matches in the log yet.
                </p>
            @else
                <div class="space-y-6">
                    @if (count($gameqProfile['opponents']) > 0)
                        <div>
                            <p class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">Head-to-head</p>
                            <ul class="mt-3 divide-y divide-zinc-100 rounded-xl border border-zinc-100 dark:divide-zinc-800 dark:border-zinc-800">
                                @foreach ($gameqProfile['opponents'] as $row)
                                    <li class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 bg-zinc-50/50 px-4 py-3 first:rounded-t-xl last:rounded-b-xl dark:bg-zinc-950/30">
                                        <span class="min-w-0 font-medium text-zinc-900 dark:text-zinc-100">{{ $row['displayName'] }}</span>
                                        <span class="shrink-0 text-right font-mono text-sm font-bold tabular-nums text-emerald-700 dark:text-emerald-400">
                                            {{ $row['wins'] }}W · {{ $row['losses'] }}L
                                            @if (($row['ties'] ?? 0) > 0)
                                                <span class="ml-2 text-[11px] font-normal text-zinc-400">{{ $row['ties'] }}T</span>
                                            @endif
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if (count($gameqProfile['partners']) > 0)
                        <div>
                            <p class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">Doubles partners</p>
                            <p class="mt-1 text-xs text-zinc-500">Same-side games (not vs. record).</p>
                            <ul class="mt-3 divide-y divide-zinc-100 rounded-xl border border-zinc-100 dark:divide-zinc-800 dark:border-zinc-800">
                                @foreach ($gameqProfile['partners'] as $row)
                                    <li class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 bg-zinc-50/50 px-4 py-3 first:rounded-t-xl last:rounded-b-xl dark:bg-zinc-950/30">
                                        <span class="min-w-0 font-medium text-zinc-900 dark:text-zinc-100">{{ $row['displayName'] }}</span>
                                        <span class="shrink-0 font-mono text-sm tabular-nums text-zinc-600 dark:text-zinc-400">{{ $row['games'] }} {{ $row['games'] === 1 ? 'game' : 'games' }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <p class="text-[11px] leading-relaxed text-zinc-400 dark:text-zinc-500">
                        {{ $gameqProfile['matches_counted'] }} finished match{{ $gameqProfile['matches_counted'] === 1 ? '' : 'es' }} ·
                        {{ $gameqProfile['sessions_matched'] }} session{{ $gameqProfile['sessions_matched'] === 1 ? '' : 's' }}. Names merged by spelling.
                    </p>
                </div>
            @endif
        </div>
    </section>

    @if ($this->upcomingOpenPlayJoins->isNotEmpty())
        <section
            aria-labelledby="dash-openplay-heading"
            class="rounded-2xl border border-violet-200/90 bg-white p-6 shadow-sm ring-1 ring-black/[0.03] dark:border-violet-900/40 dark:bg-zinc-900/85 dark:ring-white/[0.04] sm:p-8"
        >
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 id="dash-openplay-heading" class="font-display text-lg font-bold text-zinc-900 dark:text-white">Open play</h2>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">You’re on the roster for these.</p>
                </div>
                <a
                    href="{{ route('account.court-open-plays.index') }}"
                    wire:navigate
                    class="text-sm font-bold text-violet-600 hover:text-violet-700 dark:text-violet-400"
                >
                    Manage →
                </a>
            </div>
            <ul class="mt-4 space-y-2">
                @foreach ($this->upcomingOpenPlayJoins as $row)
                    @php
                        $b = $row->booking;
                    @endphp
                    @if ($b)
                        <li
                            class="flex flex-col gap-2 rounded-xl border border-violet-100 bg-violet-50/50 px-4 py-3 dark:border-violet-900/40 dark:bg-violet-950/25 sm:flex-row sm:items-center sm:justify-between"
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

    {{-- 4. Schedule: upcoming + recent --}}
    <section aria-labelledby="dash-schedule-heading">
        <h2 id="dash-schedule-heading" class="sr-only">Schedule</h2>
        <div class="grid gap-6 lg:grid-cols-2">
            <div
                class="rounded-2xl border border-zinc-200/90 bg-white p-6 shadow-sm ring-1 ring-black/[0.03] dark:border-zinc-800 dark:bg-zinc-900/85 dark:ring-white/[0.04]"
            >
                <div class="flex items-center justify-between gap-2 border-b border-zinc-100 pb-4 dark:border-zinc-800">
                    <div>
                        <h3 class="font-display text-base font-bold text-zinc-900 dark:text-white">Next on court</h3>
                        <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">Upcoming reservations</p>
                    </div>
                    <x-app-icon name="calendar" class="size-6 text-emerald-600 dark:text-emerald-400" />
                </div>
                <ul class="mt-4 space-y-2">
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
                                    Details
                                </a>
                            </div>
                        </li>
                    @empty
                        <li class="rounded-xl border border-dashed border-zinc-200 py-10 text-center dark:border-zinc-700">
                            <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Nothing scheduled yet.</p>
                            <p class="mt-1 text-xs text-zinc-500">Use Book Now to add a slot.</p>
                        </li>
                    @endforelse
                </ul>
            </div>

            <div
                class="rounded-2xl border border-zinc-200/90 bg-white p-6 shadow-sm ring-1 ring-black/[0.03] dark:border-zinc-800 dark:bg-zinc-900/85 dark:ring-white/[0.04]"
            >
                <div class="flex items-center justify-between gap-2 border-b border-zinc-100 pb-4 dark:border-zinc-800">
                    <div>
                        <h3 class="font-display text-base font-bold text-zinc-900 dark:text-white">Recently</h3>
                        <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">Past visits</p>
                    </div>
                    <x-app-icon name="clock" class="size-6 text-emerald-600 dark:text-emerald-400" />
                </div>
                <ul class="mt-4 space-y-2">
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
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">No recent sessions yet.</p>
                        </li>
                    @endforelse
                </ul>
                <a
                    href="{{ route('account.bookings') }}"
                    wire:navigate
                    class="mt-4 inline-flex text-sm font-bold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300"
                >
                    Full booking history →
                </a>
            </div>
        </div>
    </section>
</div>
