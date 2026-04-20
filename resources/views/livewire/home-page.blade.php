@php
    $contactEmail = config('mail.from.address');
    if (! is_string($contactEmail) || $contactEmail === '') {
        $contactEmail = 'support@picklecorner.ph';
    }
@endphp

<div>
    {{-- Hero --}}
    <section id="top" class="relative isolate overflow-hidden">
        <div
            class="absolute inset-0 bg-gradient-to-br from-emerald-950 via-teal-950 to-zinc-950"
            aria-hidden="true"
        ></div>
        <div
            class="absolute inset-0 opacity-[0.12]"
            style="background-image: repeating-linear-gradient(
                -35deg,
                transparent,
                transparent 14px,
                rgba(255, 255, 255, 0.08) 14px,
                rgba(255, 255, 255, 0.08) 15px
            )"
            aria-hidden="true"
        ></div>
        <div
            class="pointer-events-none absolute -right-24 top-1/4 size-[28rem] rounded-full bg-emerald-500/20 blur-3xl"
            aria-hidden="true"
        ></div>
        <div
            class="pointer-events-none absolute -left-32 bottom-0 size-[22rem] rounded-full bg-amber-400/15 blur-3xl"
            aria-hidden="true"
        ></div>

        <div class="relative mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
            <div
                class="w-full rounded-2xl border border-white/10 bg-zinc-950/40 p-6 shadow-2xl shadow-black/40 backdrop-blur-sm sm:p-8 lg:p-10"
            >
                <p
                    class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.2em] text-emerald-200/90"
                >
                    <span class="size-1.5 rounded-full bg-amber-400 shadow-[0_0_10px_rgba(251,191,36,0.7)]" aria-hidden="true"></span>
                    Pickleball · Clubs · Court time
                </p>
                <h1
                    class="font-display mt-5 text-4xl font-bold uppercase leading-[1.05] text-white sm:text-5xl lg:text-6xl"
                >
                    Book courts.
                    <span class="block text-transparent bg-gradient-to-r from-emerald-300 via-teal-200 to-amber-200 bg-clip-text">
                        Run the club.
                    </span>
                </h1>
                <p class="mt-5 max-w-none text-sm leading-relaxed text-emerald-100/85 sm:text-base lg:max-w-4xl">
                    {{ config('app.name') }} is your home base for finding venues, locking in hours, and keeping members
                    on the same page—without the spreadsheet chaos. We keep convenience fees lean so
                    <span class="font-semibold text-white/95">more of your budget stays on the court</span>, not buried
                    in booking surcharges.
                </p>
                <div class="mt-8 flex flex-wrap items-center gap-3">
                    <a
                        href="{{ route('book-now') }}"
                        wire:navigate
                        class="font-display inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-amber-400 to-orange-500 px-6 py-3 text-sm font-bold uppercase tracking-wide text-zinc-950 shadow-lg shadow-orange-950/30 transition hover:from-amber-300 hover:to-orange-400"
                    >
                        Book a court
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                        </svg>
                    </a>
                    <a
                        href="{{ url('/#about') }}"
                        class="font-display inline-flex items-center rounded-xl border border-white/25 bg-white/5 px-5 py-3 text-sm font-bold uppercase tracking-wide text-white backdrop-blur-sm transition hover:bg-white/10"
                    >
                        Why we built this
                    </a>
                    <a
                        href="{{ route('contact') }}"
                        wire:navigate
                        class="font-display inline-flex items-center rounded-xl border border-emerald-400/40 bg-emerald-500/15 px-5 py-3 text-sm font-bold uppercase tracking-wide text-emerald-50 backdrop-blur-sm transition hover:bg-emerald-500/25"
                    >
                        Venues · Book a demo
                    </a>
                </div>
                <dl class="mt-10 grid grid-cols-3 gap-4 border-t border-white/10 pt-8 sm:gap-8">
                    <div>
                        <dt class="text-[10px] font-bold uppercase tracking-wider text-emerald-300/80">Courts listed</dt>
                        <dd class="font-display mt-1 text-2xl font-bold tabular-nums text-white">{{ number_format($listedCourtsCount) }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] font-bold uppercase tracking-wider text-emerald-300/80">Happy players</dt>
                        <dd class="font-display mt-1 text-2xl font-bold tabular-nums text-white">{{ number_format($happyPlayersCount) }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] font-bold uppercase tracking-wider text-emerald-300/80">Avg. session</dt>
                        <dd class="font-display mt-1 text-2xl font-bold tabular-nums text-white">{{ $avgSessionLabel }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </section>

    {{-- Feature strip --}}
    <section class="relative border-y border-emerald-900/40 bg-emerald-600 dark:bg-emerald-800" aria-label="Highlights">
        <div
            class="absolute inset-0 opacity-30"
            style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.08\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')"
            aria-hidden="true"
        ></div>
        <div class="relative mx-auto flex max-w-7xl flex-wrap justify-center gap-x-10 gap-y-6 px-4 py-8 sm:px-6 lg:px-8">
            <div class="flex max-w-xs items-start gap-3 text-white">
                <span class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-lg bg-white/15 font-display text-sm font-bold">01</span>
                <div>
                    <p class="font-display text-sm font-bold uppercase tracking-wide">Live grid</p>
                    <p class="mt-1 text-xs text-emerald-50/90">Tap open hours—no guessing if the slot is free.</p>
                </div>
            </div>
            <div class="flex max-w-xs items-start gap-3 text-white">
                <span class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-lg bg-white/15 font-display text-sm font-bold">02</span>
                <div>
                    <p class="font-display text-sm font-bold uppercase tracking-wide">Fair, lower fees</p>
                    <p class="mt-1 text-xs text-emerald-50/90">
                        Competitive platform pricing plus venue peak/off-peak court rates—always visible before you pay.
                    </p>
                </div>
            </div>
            <div class="flex max-w-xs items-start gap-3 text-white">
                <span class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-lg bg-white/15 font-display text-sm font-bold">03</span>
                <div>
                    <p class="font-display text-sm font-bold uppercase tracking-wide">Club-ready</p>
                    <p class="mt-1 text-xs text-emerald-50/90">Desk, venue, and member flows stay in sync.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Pricing & value --}}
    <section id="pricing" class="scroll-mt-16 border-b border-zinc-200 bg-white py-16 dark:border-zinc-800 dark:bg-zinc-900 sm:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <p class="font-display text-xs font-bold uppercase tracking-[0.25em] text-emerald-600 dark:text-emerald-400">
                    Straightforward rates
                </p>
                <h2 class="font-display mt-3 text-3xl font-bold uppercase tracking-tight text-zinc-900 dark:text-white sm:text-4xl">
                    More court time, less platform overhead
                </h2>
                <p class="mt-4 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                    Venues set their own court prices—you always see hourly rates up front. On top of that, we charge a
                    small, capped <strong class="font-semibold text-zinc-800 dark:text-zinc-200">convenience fee</strong>
                    (a modest base plus a low percentage on court subtotals) so checkout stays predictable. We
                    deliberately keep that fee lower than many legacy sports and facility apps, so
                    <strong class="font-semibold text-zinc-800 dark:text-zinc-200">your money goes to play time</strong>, not
                    hidden platform markup.
                </p>
            </div>
            <ul class="mx-auto mt-12 grid max-w-5xl gap-5 sm:grid-cols-3" role="list">
                <li
                    class="rounded-2xl border border-emerald-200/90 bg-gradient-to-b from-emerald-50/90 to-white px-5 py-6 text-center shadow-sm dark:border-emerald-800/60 dark:from-emerald-950/50 dark:to-zinc-900"
                >
                    <p class="font-display text-lg font-bold uppercase tracking-wide text-emerald-800 dark:text-emerald-200">
                        Competitive fees
                    </p>
                    <p class="mt-2 text-xs leading-relaxed text-emerald-900/85 dark:text-emerald-100/85">
                        A lean convenience fee structure designed to undercut bloated all-in-one platforms—see the line item
                        at checkout.
                    </p>
                </li>
                <li
                    class="rounded-2xl border border-teal-200/90 bg-gradient-to-b from-teal-50/90 to-white px-5 py-6 text-center shadow-sm dark:border-teal-800/60 dark:from-teal-950/40 dark:to-zinc-900"
                >
                    <p class="font-display text-lg font-bold uppercase tracking-wide text-teal-800 dark:text-teal-200">
                        No sticker shock
                    </p>
                    <p class="mt-2 text-xs leading-relaxed text-teal-900/85 dark:text-teal-100/85">
                        Court subtotals, coach add-ons where offered, then convenience fee—totals are clear before you confirm.
                    </p>
                </li>
                <li
                    class="rounded-2xl border border-amber-200/90 bg-gradient-to-b from-amber-50/90 to-white px-5 py-6 text-center shadow-sm dark:border-amber-800/60 dark:from-amber-950/40 dark:to-zinc-900"
                >
                    <p class="font-display text-lg font-bold uppercase tracking-wide text-amber-900 dark:text-amber-100">
                        Venues stay in control
                    </p>
                    <p class="mt-2 text-xs leading-relaxed text-amber-950/90 dark:text-amber-100/85">
                        Peak and off-peak court pricing stays with each club—we’re not inflating their rack rates.
                    </p>
                </li>
            </ul>
            <p class="mx-auto mt-10 max-w-xl text-center text-xs text-zinc-500 dark:text-zinc-400">
                Rates and fee rules can change; we’ll always show the current breakdown at checkout.
            </p>
        </div>
    </section>

    {{-- Product tools (who each experience is for) --}}
    <section id="tools" class="scroll-mt-16 border-b border-zinc-200 bg-zinc-50 py-16 dark:border-zinc-800 dark:bg-zinc-950 sm:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <p class="font-display text-xs font-bold uppercase tracking-[0.25em] text-emerald-600 dark:text-emerald-400">
                    What’s in the app
                </p>
                <h2 class="font-display mt-3 text-3xl font-bold uppercase tracking-tight text-zinc-900 dark:text-white sm:text-4xl">
                    Tools for players, venues &amp; staff
                </h2>
                <p class="mt-4 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                    {{ config('app.name') }} isn’t only a booking grid — it’s a set of connected workspaces. Here’s what
                    each role gets after signing in (public browsing works without an account).
                </p>
            </div>
            <ul class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3" role="list">
                <li
                    class="flex flex-col rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
                >
                    <x-app-icon name="calendar" class="size-9 text-emerald-600 dark:text-emerald-400" />
                    <h3 class="font-display mt-4 text-lg font-bold uppercase text-zinc-900 dark:text-white">
                        Book now
                    </h3>
                    <p class="mt-2 flex-1 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                        Browse partner venues, filter indoor/outdoor and city, see ratings and photos, then book on a
                        live availability grid — hour by hour, court by court.
                    </p>
                    <a
                        href="{{ route('book-now') }}"
                        wire:navigate
                        class="font-display mt-5 inline-flex text-sm font-bold uppercase tracking-wide text-emerald-700 hover:underline dark:text-emerald-400"
                    >
                        Open Book now →
                    </a>
                </li>
                <li
                    class="flex flex-col rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
                >
                    <x-app-icon name="user-circle" class="size-9 text-teal-600 dark:text-teal-400" />
                    <h3 class="font-display mt-4 text-lg font-bold uppercase text-zinc-900 dark:text-white">
                        Member account
                    </h3>
                    <p class="mt-2 flex-1 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                        Your locker room: booking history, profile, venue booking flows, optional coach add-ons where
                        venues enable them, and open-play tools when your club runs sessions.
                    </p>
                    <a
                        href="{{ route('register') }}"
                        wire:navigate
                        class="font-display mt-5 inline-flex text-sm font-bold uppercase tracking-wide text-teal-700 hover:underline dark:text-teal-400"
                    >
                        Create free account →
                    </a>
                </li>
                <li
                    class="flex flex-col rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
                >
                    <span class="flex items-center gap-2" aria-hidden="true">
                        <x-gameq-mark compact />
                    </span>
                    <h3 class="font-display mt-4 text-lg font-bold uppercase text-zinc-900 dark:text-white">
                        GameQ · open play
                    </h3>
                    <p class="mt-2 flex-1 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                        Casual sessions at the club: who’s on court, who’s waiting, and scores — without the group-chat
                        chaos (member sign-in).
                    </p>
                    <a
                        href="{{ route('open-play.about') }}"
                        wire:navigate
                        class="font-display mt-5 inline-flex text-sm font-bold uppercase tracking-wide text-sky-700 hover:underline dark:text-sky-400"
                    >
                        Learn about GameQ →
                    </a>
                </li>
                <li
                    class="flex flex-col rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
                >
                    <x-app-icon name="building-office-2" class="size-9 text-amber-600 dark:text-amber-400" />
                    <h3 class="font-display mt-4 text-lg font-bold uppercase text-zinc-900 dark:text-white">
                        Venue portal
                    </h3>
                    <p class="mt-2 flex-1 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                        For club admins: pending booking approvals, weekly hours, courts and slot pricing, venue
                        settings, customer CRM, reports, gift cards and more on supported plans.
                    </p>
                    <a
                        href="{{ route('login') }}"
                        wire:navigate
                        class="font-display mt-5 inline-flex text-sm font-bold uppercase tracking-wide text-amber-800 hover:underline dark:text-amber-300"
                    >
                        Venue sign-in →
                    </a>
                </li>
                <li
                    class="flex flex-col rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
                >
                    <x-app-icon name="squares-2x2" class="size-9 text-violet-600 dark:text-violet-400" />
                    <h3 class="font-display mt-4 text-lg font-bold uppercase text-zinc-900 dark:text-white">
                        Front desk
                    </h3>
                    <p class="mt-2 flex-1 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                        For reception: a live courts board, walk-in booking requests, and a queue so the floor and the
                        counter stay aligned.
                    </p>
                    <a
                        href="{{ route('login') }}"
                        wire:navigate
                        class="font-display mt-5 inline-flex text-sm font-bold uppercase tracking-wide text-violet-700 hover:underline dark:text-violet-400"
                    >
                        Desk sign-in →
                    </a>
                </li>
                <li
                    class="flex flex-col rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
                >
                    <x-app-icon name="document-text" class="size-9 text-emerald-600 dark:text-emerald-400" />
                    <h3 class="font-display mt-4 text-lg font-bold uppercase text-zinc-900 dark:text-white">
                        Coaches
                    </h3>
                    <p class="mt-2 flex-1 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                        Coaches on the network can manage which courts they cover, set availability, and (where enabled)
                        sell sessions and gift cards from a dedicated coach workspace.
                    </p>
                    <a
                        href="{{ route('login') }}"
                        wire:navigate
                        class="font-display mt-5 inline-flex text-sm font-bold uppercase tracking-wide text-emerald-700 hover:underline dark:text-emerald-400"
                    >
                        Coach sign-in →
                    </a>
                </li>
            </ul>
            <p class="mx-auto mt-10 max-w-2xl text-center text-xs text-zinc-500 dark:text-zinc-400">
                Platform operators use a separate super-admin console for venues, users, billing, and approvals — not
                shown here. Questions?
                <a href="{{ route('contact') }}" wire:navigate class="font-semibold text-emerald-700 hover:underline dark:text-emerald-400">
                    Contact us
                </a>
                .
            </p>
        </div>
    </section>

    {{-- About --}}
    <section id="about" class="scroll-mt-16 bg-white py-16 dark:bg-zinc-900 sm:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <p class="font-display text-xs font-bold uppercase tracking-[0.25em] text-emerald-600 dark:text-emerald-400">
                    About us
                </p>
                <h2 class="font-display mt-3 text-3xl font-bold uppercase tracking-tight text-zinc-900 dark:text-white sm:text-4xl">
                    Built for the pace of the game
                </h2>
                <p class="mt-4 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                    We started with a simple frustration: great courts, messy coordination. {{ config('app.name') }} brings
                    booking, venue tools, and member access into one flow—so staff spend less time on DMs and more time on
                    the floor.
                </p>
            </div>
            <div class="mt-14 grid gap-6 md:grid-cols-3">
                <article
                    class="group relative overflow-hidden rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm transition hover:border-emerald-300/60 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-emerald-600/40"
                >
                    <div
                        class="absolute -right-6 -top-6 size-24 rounded-full bg-emerald-500/10 transition group-hover:bg-emerald-500/20"
                        aria-hidden="true"
                    ></div>
                    <div class="relative">
                        <x-app-icon name="sparkles" class="size-8 text-emerald-600 dark:text-emerald-400" />
                        <h3 class="font-display mt-4 text-lg font-bold uppercase text-zinc-900 dark:text-white">Mission</h3>
                        <p class="mt-2 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                            Make court time as easy to reserve as it is to play—transparent, fast, and fair for everyone
                            on the schedule.
                        </p>
                    </div>
                </article>
                <article
                    class="group relative overflow-hidden rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm transition hover:border-teal-300/60 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-teal-600/40"
                >
                    <div
                        class="absolute -right-6 -top-6 size-24 rounded-full bg-teal-500/10 transition group-hover:bg-teal-500/20"
                        aria-hidden="true"
                    ></div>
                    <div class="relative">
                        <x-app-icon name="bolt" class="size-8 text-teal-600 dark:text-teal-400" />
                        <h3 class="font-display mt-4 text-lg font-bold uppercase text-zinc-900 dark:text-white">Stack</h3>
                        <p class="mt-2 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                            Crafted with Laravel {{ app()->version() }} and Livewire—modern, maintainable, and ready to
                            grow with your league.
                        </p>
                    </div>
                </article>
                <article
                    class="group relative overflow-hidden rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm transition hover:border-amber-300/60 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-amber-600/40"
                >
                    <div
                        class="absolute -right-6 -top-6 size-24 rounded-full bg-amber-500/10 transition group-hover:bg-amber-500/20"
                        aria-hidden="true"
                    ></div>
                    <div class="relative">
                        <x-app-icon name="building-office-2" class="size-8 text-amber-600 dark:text-amber-400" />
                        <h3 class="font-display mt-4 text-lg font-bold uppercase text-zinc-900 dark:text-white">Roadmap</h3>
                        <p class="mt-2 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                            Booking is chapter one. We’re shaping leagues, payments, and deeper venue analytics next—tell
                            us what your club needs.
                        </p>
                    </div>
                </article>
            </div>
        </div>
    </section>

    @if (public_reviews_enabled())
        @php
            $reviews = [
                [
                    'name' => 'Mia R.',
                    'meta' => 'League player · Quezon City',
                    'stars' => 5,
                    'body' => 'Booking used to be a group chat nightmare. Now I grab court time in two taps before work.',
                ],
                [
                    'name' => 'Coach Javier D.',
                    'meta' => 'Skills clinic · Makati',
                    'stars' => 5,
                    'body' => 'Clients see the schedule, I see fewer no-shows. The flow actually matches how venues operate.',
                ],
                [
                    'name' => 'Aira & Ben T.',
                    'meta' => 'Weekend doubles · BGC',
                    'stars' => 5,
                    'body' => 'Love the venue photos and clear hourly rates. Feels built by people who actually play.',
                ],
            ];
        @endphp
        {{-- Reviews --}}
        <section id="reviews" class="scroll-mt-16 border-y border-zinc-200 bg-white py-16 dark:border-zinc-800 dark:bg-zinc-900 sm:py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col items-start justify-between gap-6 sm:flex-row sm:items-end">
                    <div>
                        <p class="font-display text-xs font-bold uppercase tracking-[0.25em] text-emerald-600 dark:text-emerald-400">
                            Player voices
                        </p>
                        <h2 class="font-display mt-2 text-3xl font-bold uppercase tracking-tight text-zinc-900 dark:text-white sm:text-4xl">
                            From the court
                        </h2>
                        <p class="mt-2 max-w-xl text-sm text-zinc-600 dark:text-zinc-400">
                            Real feedback from players and coaches (sample quotes for the landing page).
                        </p>
                    </div>
                    <div class="hidden items-center gap-1 sm:flex" aria-hidden="true">
                        @for ($i = 0; $i < 5; $i++)
                            <span class="size-2 rotate-45 bg-emerald-500/80"></span>
                        @endfor
                    </div>
                </div>
                <ul class="mt-12 grid gap-6 md:grid-cols-3">
                    @foreach ($reviews as $idx => $r)
                        <li
                            wire:key="landing-review-{{ $idx }}"
                            class="flex flex-col rounded-2xl border border-zinc-200 bg-zinc-50/80 p-6 dark:border-zinc-700 dark:bg-zinc-950/50"
                        >
                            <div class="flex gap-0.5 text-amber-500" aria-label="{{ $r['stars'] }} out of 5 stars">
                                @for ($s = 0; $s < $r['stars']; $s++)
                                    <svg class="size-5 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path
                                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                                        />
                                    </svg>
                                @endfor
                            </div>
                            <p class="mt-4 flex-1 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">“{{ $r['body'] }}”</p>
                            <footer class="mt-6 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                                <p class="font-display text-sm font-bold uppercase text-zinc-900 dark:text-white">{{ $r['name'] }}</p>
                                <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $r['meta'] }}</p>
                            </footer>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    {{-- Contact --}}
    <section id="contact" class="scroll-mt-16 bg-gradient-to-b from-zinc-100 to-zinc-50 py-16 dark:from-zinc-950 dark:to-zinc-900 sm:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-12 lg:grid-cols-2 lg:gap-16">
                <div>
                    <p class="font-display text-xs font-bold uppercase tracking-[0.25em] text-emerald-600 dark:text-emerald-400">
                        Contact us
                    </p>
                    <h2 class="font-display mt-3 text-3xl font-bold uppercase tracking-tight text-zinc-900 dark:text-white sm:text-4xl">
                        Let’s talk courts
                    </h2>
                    <p class="mt-4 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                        Questions about partnering, a product walkthrough, or bringing {{ config('app.name') }} to your
                        venue? Email us — we read every message.
                    </p>
                    <div class="mt-6">
                        <a
                            href="{{ route('contact') }}"
                            wire:navigate
                            class="font-display inline-flex items-center rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 px-5 py-3 text-sm font-bold uppercase tracking-wide text-white shadow-md transition hover:from-emerald-500 hover:to-teal-500"
                        >
                            Contact us
                        </a>
                    </div>
                    <ul class="mt-8 space-y-4 text-sm text-zinc-700 dark:text-zinc-300">
                        <li class="flex items-start gap-3">
                            <span
                                class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-lg bg-emerald-600/15 text-emerald-700 dark:text-emerald-400"
                                aria-hidden="true"
                            >
                                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                                </svg>
                            </span>
                            <div>
                                <p class="font-display text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Email</p>
                                <a href="mailto:{{ $contactEmail }}" class="mt-0.5 font-medium text-emerald-700 hover:underline dark:text-emerald-400">{{ $contactEmail }}</a>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span
                                class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-lg bg-teal-600/15 text-teal-800 dark:text-teal-400"
                                aria-hidden="true"
                            >
                                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                            </span>
                            <div>
                                <p class="font-display text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Hours</p>
                                <p class="mt-0.5">Mon–Sat · 9:00–18:00 (GMT+8)</p>
                            </div>
                        </li>
                    </ul>
                </div>
                <div
                    class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/80 sm:p-8"
                >
                    <p class="font-display text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        Send a note
                    </p>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                        Tell us about your club, ask for a live demo, or say hello — we’ll reply by email.
                    </p>
                    <div class="mt-6 flex flex-col gap-3">
                        <a
                            href="mailto:{{ $contactEmail }}?subject={{ rawurlencode(config('app.name').' — Contact') }}"
                            class="font-display inline-flex w-full items-center justify-center rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 px-4 py-3 text-center text-sm font-bold uppercase tracking-wide text-white shadow-md transition hover:from-emerald-500 hover:to-teal-500"
                        >
                            Email {{ $contactEmail }}
                        </a>
                        <a
                            href="{{ route('contact') }}"
                            wire:navigate
                            class="inline-flex w-full items-center justify-center rounded-xl border border-zinc-200 px-4 py-3 text-sm font-semibold text-zinc-800 transition hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800"
                        >
                            Contact page
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Account CTA --}}
    <section class="border-t border-zinc-200 bg-white py-14 dark:border-zinc-800 dark:bg-zinc-950">
        <div class="mx-auto max-w-3xl px-4 text-center sm:px-6 lg:px-8">
            @auth
                @php($homeUser = auth()->user())
                <h2 class="font-display text-2xl font-bold uppercase tracking-tight text-zinc-900 dark:text-white">
                    You’re on the roster
                </h2>
                <p class="mx-auto mt-3 max-w-md text-sm text-zinc-600 dark:text-zinc-400">
                    @if ($homeUser->usesStaffAppNav())
                        Jump back into operations anytime.
                    @else
                        Your locker room has bookings, history, and profile settings.
                    @endif
                </p>
                <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                    @if ($homeUser->usesStaffAppNav())
                        <a
                            href="{{ $homeUser->staffAppHomeUrl() }}"
                            wire:navigate
                            class="font-display inline-flex items-center rounded-xl bg-emerald-600 px-6 py-3 text-sm font-bold uppercase tracking-wide text-white shadow-md transition hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600"
                        >
                            Open app
                        </a>
                    @else
                        <a
                            href="{{ $homeUser->memberHomeUrl() }}"
                            wire:navigate
                            class="font-display inline-flex items-center rounded-xl bg-emerald-600 px-6 py-3 text-sm font-bold uppercase tracking-wide text-white shadow-md transition hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600"
                        >
                            My Corner
                        </a>
                    @endif
                    <a
                        href="{{ route('book-now') }}"
                        wire:navigate
                        class="text-sm font-semibold text-emerald-800 underline-offset-4 hover:underline dark:text-emerald-300"
                    >
                        Book a court
                    </a>
                </div>
            @else
                <h2 class="font-display text-2xl font-bold uppercase tracking-tight text-zinc-900 dark:text-white">
                    Ready when you are
                </h2>
                <p class="mx-auto mt-3 max-w-md text-sm text-zinc-600 dark:text-zinc-400">
                    Create a free account to save venues, track bookings, and move faster next time.
                </p>
                <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                    <a
                        href="{{ route('register') }}"
                        wire:navigate
                        class="font-display inline-flex items-center rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 px-6 py-3 text-sm font-bold uppercase tracking-wide text-white shadow-lg transition hover:from-emerald-500 hover:to-teal-500"
                    >
                        Register
                    </a>
                    <a
                        href="{{ route('login') }}"
                        wire:navigate
                        class="inline-flex items-center rounded-xl border border-zinc-300 px-5 py-3 text-sm font-semibold text-zinc-800 transition hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-100 dark:hover:bg-zinc-800"
                    >
                        Log in
                    </a>
                </div>
            @endauth
        </div>
    </section>
</div>
