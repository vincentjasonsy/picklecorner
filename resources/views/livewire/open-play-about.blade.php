<div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-10 lg:py-16 xl:px-12">
    <div class="xl:grid xl:grid-cols-12 xl:gap-12 2xl:gap-16">
        <div class="min-w-0 xl:col-span-8">
            <header class="text-center xl:text-left">
                <p class="text-sm font-medium text-sky-700 dark:text-sky-400">
                    Free for members
                </p>
                <h1
                    class="font-display mt-3 flex flex-wrap items-center justify-center gap-2 text-3xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50 sm:text-4xl lg:justify-start lg:text-5xl"
                >
                    <x-gameq-mark />
                </h1>
                <p class="mx-auto mt-5 max-w-2xl text-lg text-zinc-600 dark:text-zinc-400 xl:mx-0 xl:max-w-none xl:text-xl">
                    Run casual sessions at the club: who’s on court, who’s waiting, and scores — without spreadsheets or group chats.
                </p>
            </header>

            <div class="mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row sm:flex-wrap xl:justify-start xl:gap-4">
                @auth
                    <a
                        href="{{ route('account.open-play') }}"
                        wire:navigate
                        class="inline-flex w-full items-center justify-center rounded-xl bg-sky-600 px-8 py-3.5 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-500 sm:w-auto"
                    >
                        Open GameQ
                    </a>
                    <a
                        href="{{ route('account.dashboard') }}"
                        wire:navigate
                        class="inline-flex w-full items-center justify-center rounded-xl border border-zinc-300 px-8 py-3.5 text-sm font-semibold text-zinc-800 transition hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800/50 sm:w-auto"
                    >
                        Back to My Corner
                    </a>
                @else
                    <a
                        href="{{ route('login') }}"
                        wire:navigate
                        class="inline-flex w-full items-center justify-center rounded-xl bg-sky-600 px-8 py-3.5 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-500 sm:w-auto"
                    >
                        Log in to use GameQ
                    </a>
                    <a
                        href="{{ route('register') }}"
                        wire:navigate
                        class="inline-flex w-full items-center justify-center rounded-xl border border-zinc-300 px-8 py-3.5 text-sm font-semibold text-zinc-800 transition hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800/50 sm:w-auto"
                    >
                        Create an account
                    </a>
                @endauth
            </div>

            <p class="mt-8 text-center text-sm text-zinc-500 dark:text-zinc-400 xl:text-left">
                GameQ is one workspace inside {{ config('app.name') }} —
                <a href="{{ url('/#pricing') }}" wire:navigate class="font-semibold text-sky-700 hover:underline dark:text-sky-400">
                    competitive booking fees
                </a>
                ·
                <a href="{{ url('/#tools') }}" wire:navigate class="font-semibold text-sky-700 hover:underline dark:text-sky-400">
                    booking, venue, desk &amp; coach tools
                </a>
                .
            </p>

            <div class="mt-14 grid gap-8 lg:grid-cols-2 lg:gap-10">
                <section class="rounded-2xl border border-zinc-200 bg-white p-8 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/50 lg:p-10">
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">
                        What you can do
                    </h2>
                    <ul class="mt-5 space-y-4 text-base leading-relaxed text-zinc-600 dark:text-zinc-400">
                        <li class="flex gap-3">
                            <span class="mt-0.5 shrink-0 font-semibold text-sky-600 dark:text-sky-400">·</span>
                            <span><span class="font-medium text-zinc-800 dark:text-zinc-200">Singles or doubles</span> — set how many courts you’re using and how the next match is picked (random, by wins, by level, or fixed pairs).</span>
                        </li>
                        <li class="flex gap-3">
                            <span class="mt-0.5 shrink-0 font-semibold text-sky-600 dark:text-sky-400">·</span>
                            <span><span class="font-medium text-zinc-800 dark:text-zinc-200">Queue &amp; courts</span> — see who’s waiting, move people up or down, fill empty courts in one tap.</span>
                        </li>
                        <li class="flex gap-3">
                            <span class="mt-0.5 shrink-0 font-semibold text-sky-600 dark:text-sky-400">·</span>
                            <span><span class="font-medium text-zinc-800 dark:text-zinc-200">Scores &amp; standings</span> — record results, track head-to-head, and a simple session leaderboard.</span>
                        </li>
                        <li class="flex gap-3">
                            <span class="mt-0.5 shrink-0 font-semibold text-sky-600 dark:text-sky-400">·</span>
                            <span><span class="font-medium text-zinc-800 dark:text-zinc-200">Share a live view</span> — send guests a read-only link so they can see courts and the queue on their phones (no login needed for that page).</span>
                        </li>
                    </ul>
                </section>

                <div class="flex flex-col gap-8">
                    <section class="rounded-2xl border border-zinc-200 bg-white p-8 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/50 lg:p-10">
                        <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">
                            Where your data lives
                        </h2>
                        <p class="mt-4 text-base leading-relaxed text-zinc-600 dark:text-zinc-400">
                            While you host, queues, matchups, and timers run on the server in your logged-in session (Livewire), so refresh or switching devices won’t lose your GameQ state. When you’re logged in, GameQ also <span class="font-medium text-zinc-800 dark:text-zinc-200">saves your hosted snapshot to your account</span> as you go (creating up to {{ \App\Models\OpenPlaySession::MONTHLY_SAVE_LIMIT }} new saved sessions per calendar month; reopening or continuing one updates that row). They appear under <span class="font-medium text-zinc-800 dark:text-zinc-200">Your sessions</span> in GameQ and on this page, where you can search, filter, and reopen any session.
                        </p>
                    </section>

                    <section class="rounded-2xl border border-dashed border-zinc-300 bg-zinc-50/80 p-8 dark:border-zinc-700 dark:bg-zinc-900/30 lg:p-10">
                        <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">
                            Booking a court is separate
                        </h2>
                        <p class="mt-4 text-base leading-relaxed text-zinc-600 dark:text-zinc-400">
                            GameQ helps you organize people and games on the day. To reserve court time through {{ config('app.name') }}, use
                            <a href="{{ route('book-now') }}" wire:navigate class="font-medium text-sky-700 underline decoration-sky-600/30 hover:decoration-sky-600 dark:text-sky-400">Book now</a>.
                        </p>
                    </section>
                </div>
            </div>
        </div>

        <aside class="mt-14 min-w-0 xl:col-span-4 xl:mt-0">
            <div class="sticky top-24 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/50 sm:p-8">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                    Your hosted sessions
                </h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    Search by title or player name. Filter by the month you hosted.
                </p>

                @auth
                    @if ($monthlyQuota !== null)
                        <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">
                            <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $monthlyQuota['used'] }} of {{ $monthlyQuota['limit'] }}</span>
                            GameQ uses this month (saved to your account).
                            <span class="mt-1 block text-xs text-zinc-500 dark:text-zinc-500">
                                Resets {{ \Illuminate\Support\Carbon::parse($monthlyQuota['resets_at'])->timezone(config('app.timezone'))->format('M j, Y') }}.
                            </span>
                        </p>
                    @endif

                    <div class="mt-4 space-y-3">
                        <label class="block text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                            Search
                            <input
                                type="search"
                                wire:model.live.debounce.300ms="historySearch"
                                placeholder="Title or player…"
                                class="mt-1.5 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100"
                                autocomplete="off"
                            />
                        </label>
                        <div class="flex flex-wrap items-end gap-2">
                            <label class="min-w-0 flex-1 text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                Month hosted
                                <input
                                    type="month"
                                    wire:model.live="historyMonth"
                                    class="mt-1.5 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100"
                                />
                            </label>
                            @if ($historySearch !== '' || $historyMonth !== '')
                                <button
                                    type="button"
                                    wire:click="$set('historySearch', ''); $set('historyMonth', '')"
                                    class="rounded-lg border border-zinc-200 px-3 py-2 text-xs font-semibold text-zinc-600 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-800"
                                >
                                    Clear
                                </button>
                            @endif
                        </div>
                    </div>

                    @if ($historySessions->isEmpty())
                        <p class="mt-6 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                            @if ($historySearch !== '' || $historyMonth !== '')
                                No sessions match your filters. Try another search or month.
                            @else
                                No sessions yet. Saved sessions in your account show up here and under <span class="font-medium text-zinc-800 dark:text-zinc-200">Your sessions</span> when you start GameQ.
                            @endif
                        </p>
                    @else
                        <ul class="mt-6 max-h-[min(70vh,32rem)] space-y-3 overflow-y-auto pr-1">
                            @foreach ($historySessions as $session)
                                <li class="rounded-xl border border-zinc-100 bg-zinc-50/90 p-4 dark:border-zinc-800 dark:bg-zinc-950/60">
                                    <p class="font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ $session->title }}
                                    </p>
                                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                        Hosted {{ $session->created_at->timezone(config('app.timezone'))->format('M j, Y g:i a') }}
                                        @if ($session->updated_at->ne($session->created_at))
                                            · updated {{ $session->updated_at->timezone(config('app.timezone'))->format('M j, g:i a') }}
                                        @endif
                                    </p>
                                    <a
                                        href="{{ route('account.open-play') }}?load={{ $session->id }}"
                                        wire:navigate
                                        class="mt-3 inline-flex w-full items-center justify-center rounded-lg bg-sky-600 px-3 py-2 text-sm font-medium text-white transition hover:bg-sky-500"
                                    >
                                        Open in GameQ
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                @else
                    <p class="mt-4 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                        <a href="{{ route('login') }}" wire:navigate class="font-medium text-sky-700 underline decoration-sky-600/30 hover:decoration-sky-600 dark:text-sky-400">Log in</a>
                        to see sessions you’ve hosted, search your history, and reopen one in GameQ.
                    </p>
                    <p class="mt-3 text-xs text-zinc-500 dark:text-zinc-500">
                        Up to {{ \App\Models\OpenPlaySession::MONTHLY_SAVE_LIMIT }} new session records per calendar month (each new record counts as one use).
                    </p>
                @endauth
            </div>
        </aside>
    </div>
</div>
