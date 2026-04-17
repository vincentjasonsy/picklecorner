@php
    /** @var \App\GameQ\Engine $eq */
    /** @var int $nowMs */
@endphp

<div
    class="relative min-h-[70vh] overflow-hidden pb-[max(2rem,env(safe-area-inset-bottom))] pt-2"
    wire:poll.1s="refreshWatch"
>
    {{-- Ambient background --}}
    <div class="pointer-events-none absolute inset-0 -z-10 bg-gradient-to-b from-emerald-100/40 via-zinc-50 to-sky-100/30 dark:from-emerald-950/30 dark:via-zinc-950 dark:to-slate-950" aria-hidden="true"></div>
    <div class="pointer-events-none absolute -left-24 top-20 h-72 w-72 rounded-full bg-emerald-400/20 blur-3xl dark:bg-emerald-500/10" aria-hidden="true"></div>
    <div class="pointer-events-none absolute -right-16 bottom-32 h-64 w-64 rounded-full bg-sky-400/15 blur-3xl dark:bg-sky-500/10" aria-hidden="true"></div>

    <div class="relative mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        {{-- Hero --}}
        <header class="relative overflow-hidden rounded-[1.75rem] bg-gradient-to-br from-slate-900 via-slate-800 to-emerald-950 p-6 shadow-2xl shadow-emerald-950/20 ring-1 ring-white/10 sm:p-8">
            <div class="absolute right-0 top-0 h-40 w-40 translate-x-1/3 -translate-y-1/3 rounded-full bg-emerald-500/20 blur-3xl" aria-hidden="true"></div>
            <div class="relative">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="inline-flex items-center gap-2 rounded-full bg-red-500/20 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.2em] text-red-200 ring-1 ring-red-400/40">
                        <span class="relative flex h-2 w-2">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-75"></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-red-500"></span>
                        </span>
                        Live
                    </span>
                    <span class="text-[10px] font-medium uppercase tracking-wider text-emerald-200/80">GameQ</span>
                </div>
                <h1 class="font-display mt-4 text-3xl font-bold tracking-tight text-white sm:text-4xl">
                    <x-gameq-mark class="text-white [&>span:first-child]:text-white" />
                </h1>
                @if (! $loadFailed && trim((string) ($game['sessionTitle'] ?? '')) !== '')
                    <p class="mt-3 text-lg font-semibold leading-snug text-white sm:text-xl">
                        {{ trim((string) $game['sessionTitle']) }}
                    </p>
                @endif
                @if (! $loadFailed)
                    <p class="mt-3 text-sm leading-relaxed text-slate-300">
                        {{ ($game['mode'] ?? '') === 'doubles' ? 'Doubles' : 'Singles' }}
                        @if ((int) ($game['timeLimitMinutes'] ?? 0) > 0)
                            <span class="mx-2 text-slate-500">·</span>
                            {{ (int) $game['timeLimitMinutes'] }} min clock (host)
                        @endif
                    </p>
                    @if ($updatedAtIso)
                        <p class="mt-2 font-mono text-xs text-slate-400">
                            Synced {{ $this->syncedRelativeLabel() }}
                        </p>
                    @endif
                @endif
                @if ($loadFailed)
                    <p class="mt-4 rounded-xl bg-red-950/40 px-3 py-2 text-sm text-red-100 ring-1 ring-red-500/30">
                        Could not load live data. Check your connection and refresh.
                    </p>
                @endif
            </div>
        </header>

        @if (! $loadFailed)
            <div class="mt-8 space-y-10 sm:mt-10">
                {{-- Standings (same order as host modal) --}}
                <section class="rounded-2xl border border-slate-200/90 bg-white/90 p-5 shadow-lg shadow-slate-900/5 ring-1 ring-slate-100 backdrop-blur dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700/50">
                    <h2 class="font-display text-lg font-bold uppercase tracking-wide text-slate-800 dark:text-slate-100">Standings</h2>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Win–loss from the host session (updates as matches finish)</p>
                    @php $rankRows = $eq->rankings(); @endphp
                    @if (count($rankRows) === 0)
                        <p class="mt-4 text-center text-sm text-slate-500 dark:text-slate-400">No active players yet</p>
                    @else
                        <ol class="mt-5 space-y-2">
                            @foreach ($rankRows as $ri => $r)
                                <li
                                    class="flex items-center justify-between gap-3 rounded-2xl border px-3 py-3 text-sm transition dark:border-slate-800 {{ $ri === 0 ? 'border-amber-200/90 bg-gradient-to-r from-amber-50/90 to-white dark:border-amber-900/40 dark:from-amber-950/30 dark:to-slate-900/50' : 'border-slate-100 bg-slate-50/80 dark:border-slate-800 dark:bg-slate-950/50' }}"
                                    wire:key="standings-{{ $openPlayShare->uuid }}-{{ $r['id'] }}"
                                >
                                    <span class="flex min-w-0 items-center gap-3">
                                        <span
                                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-xs font-bold tabular-nums {{ $ri === 0 ? 'bg-amber-400/25 text-amber-950 dark:bg-amber-500/20 dark:text-amber-100' : ($ri === 1 ? 'bg-slate-200/90 text-slate-700 dark:bg-slate-600 dark:text-slate-100' : ($ri === 2 ? 'bg-orange-200/50 text-orange-950 dark:bg-orange-500/15 dark:text-orange-100' : 'bg-slate-200/60 text-slate-500 dark:bg-slate-800 dark:text-slate-400')) }}"
                                        >
                                            {{ $ri + 1 }}
                                        </span>
                                        <a
                                            href="{{ route('open-play.watch.player', ['openPlayShare' => $openPlayShare, 'playerId' => $r['id']]) }}"
                                            class="truncate font-semibold text-emerald-800 underline decoration-emerald-300/80 underline-offset-2 transition hover:text-emerald-950 dark:text-emerald-200 dark:decoration-emerald-600/60 dark:hover:text-emerald-100"
                                        >
                                            {{ $r['name'] }}
                                        </a>
                                    </span>
                                    <span class="shrink-0 text-right tabular-nums">
                                        <span class="block text-xs text-slate-500 dark:text-slate-400">{{ (int) ($r['wins'] ?? 0) }}W · {{ (int) ($r['losses'] ?? 0) }}L</span>
                                        <span class="text-sm font-bold text-emerald-700 dark:text-emerald-400">{{ ! empty($r['played']) ? ($r['pct'] ?? 0).'%' : '—' }}</span>
                                    </span>
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </section>

                {{-- Courts --}}
                <section class="space-y-4">
                    <div class="flex items-end justify-between gap-4">
                        <h2 class="font-display text-lg font-bold uppercase tracking-wide text-slate-800 dark:text-slate-100">Courts</h2>
                    </div>
                    <div class="space-y-4">
                        @foreach (($game['courts'] ?? []) as $i => $court)
                            <div wire:key="court-{{ $openPlayShare->uuid }}-{{ $i }}">
                                @if ($court)
                                    <div class="overflow-hidden rounded-2xl border border-white/60 bg-white shadow-xl shadow-slate-900/5 ring-1 ring-slate-200/80 dark:border-slate-700/80 dark:bg-slate-900/90 dark:ring-slate-700/60">
                                        <div class="flex flex-col gap-4 bg-gradient-to-r from-emerald-600 to-teal-700 px-4 py-4 text-white sm:flex-row sm:items-center sm:justify-between sm:gap-6 sm:px-6 sm:py-5">
                                            <span class="font-display text-base font-bold tracking-wide sm:text-lg">{{ $eq->courtDisplayLabel((int) $i) }}</span>
                                            <div class="flex w-full flex-wrap items-stretch justify-end gap-4 sm:w-auto sm:gap-8">
                                                @if ((int) ($game['timeLimitMinutes'] ?? 0) > 0)
                                                    @php
                                                        $rs = $eq->remainingSeconds(is_array($court) ? $court : null, $nowMs);
                                                    @endphp
                                                    <div class="min-w-0 flex-1 text-center sm:flex-none sm:text-right">
                                                        <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-emerald-100/90">Remaining</p>
                                                        <p
                                                            class="mt-1 font-mono text-3xl font-bold tabular-nums leading-none tracking-tight drop-shadow-sm sm:text-4xl {{ $rs === 0 ? 'text-amber-200' : 'text-white' }}"
                                                        >
                                                            {{ $eq->formatCountdown($rs) }}
                                                        </p>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="grid gap-px bg-slate-200/80 dark:bg-slate-700/80 sm:grid-cols-[1fr_auto_1fr]">
                                            <div class="bg-gradient-to-br from-emerald-50/90 to-white p-4 sm:p-5 dark:from-emerald-950/40 dark:to-slate-900">
                                                <p class="text-[10px] font-bold uppercase tracking-widest text-emerald-700 dark:text-emerald-400/90">Side A</p>
                                                <p class="mt-2 font-display text-xl font-bold leading-tight tracking-tight text-slate-900 sm:text-2xl dark:text-slate-50">
                                                    {{ $eq->sideLabelsWithStandings($court['sideA'] ?? []) }}
                                                </p>
                                            </div>
                                            <div class="flex items-center justify-center bg-slate-50 px-2 py-6 dark:bg-slate-900/80">
                                                <span class="rounded-full bg-slate-200/90 px-3 py-1.5 text-[11px] font-bold uppercase tracking-widest text-slate-600 dark:bg-slate-800 dark:text-slate-300">vs</span>
                                            </div>
                                            <div class="bg-gradient-to-br from-violet-50/90 to-white p-4 sm:p-5 dark:from-violet-950/35 dark:to-slate-900">
                                                <p class="text-[10px] font-bold uppercase tracking-widest text-violet-700 dark:text-violet-400/90">Side B</p>
                                                <p class="mt-2 font-display text-xl font-bold leading-tight tracking-tight text-slate-900 sm:text-2xl dark:text-slate-50">
                                                    {{ $eq->sideLabelsWithStandings($court['sideB'] ?? []) }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="rounded-2xl border-2 border-dashed border-slate-300/80 bg-slate-50/50 px-5 py-8 text-center dark:border-slate-600 dark:bg-slate-900/30">
                                        <p class="font-display text-sm font-semibold text-slate-500 dark:text-slate-400">{{ $eq->courtDisplayLabel((int) $i) }} — open</p>
                                        <p class="mt-1 text-xs text-slate-400">Waiting for the host</p>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    @if (empty($game['courts']) || count($game['courts']) === 0)
                        <p class="text-center text-sm text-slate-500 dark:text-slate-400">No court data from the host yet.</p>
                    @endif
                </section>

                {{-- Queue --}}
                <section class="rounded-2xl border border-slate-200/90 bg-white/90 p-5 shadow-lg shadow-slate-900/5 ring-1 ring-slate-100 backdrop-blur dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700/50">
                    <h2 class="font-display text-lg font-bold uppercase tracking-wide text-slate-800 dark:text-slate-100">Up next</h2>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Queue order from the host session</p>
                    <ul class="mt-5 flex flex-col gap-2">
                        @foreach (($game['queue'] ?? []) as $qi => $qid)
                            <li
                                class="flex items-center gap-3 rounded-xl border border-slate-100 bg-slate-50/80 px-4 py-3 dark:border-slate-700 dark:bg-slate-950/50"
                                wire:key="queue-{{ $openPlayShare->uuid }}-{{ $qi }}-{{ $qid }}"
                            >
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 font-display text-sm font-bold text-white shadow-md shadow-emerald-900/20">{{ $qi + 1 }}</span>
                                <a
                                    href="{{ route('open-play.watch.player', ['openPlayShare' => $openPlayShare, 'playerId' => $qid]) }}"
                                    class="min-w-0 flex-1 truncate text-base font-semibold text-emerald-800 underline decoration-emerald-300/80 underline-offset-2 transition hover:text-emerald-950 dark:text-emerald-200 dark:decoration-emerald-600/60 dark:hover:text-emerald-100"
                                >
                                    {{ $eq->playerStandingsLabel($qid) }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                    @if (empty($game['queue']))
                        <p class="mt-4 text-center text-sm text-slate-500 dark:text-slate-400">Queue is empty</p>
                    @endif
                </section>

                {{-- Roster --}}
                <section>
                    <h2 class="font-display text-lg font-bold uppercase tracking-wide text-slate-800 dark:text-slate-100">Playing today</h2>
                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                        Take a break updates the host list after you confirm it’s you.
                    </p>
                    @if ($toggleBreakError !== '')
                        <p class="mt-2 rounded-xl border border-red-200/80 bg-red-50/90 px-3 py-2 text-sm text-red-900 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-100">{{ $toggleBreakError }}</p>
                    @endif
                    @if ($toggleBreakSuccess !== '')
                        <p class="mt-2 rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-3 py-2 text-sm text-emerald-950 dark:border-emerald-900/50 dark:bg-emerald-950/40 dark:text-emerald-100" role="status">{{ $toggleBreakSuccess }}</p>
                    @endif
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        @foreach ($this->rosterPlayers() as $player)
                            <div
                                class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-md shadow-slate-900/5 dark:border-slate-700 dark:bg-slate-900/70"
                                wire:key="roster-{{ $openPlayShare->uuid }}-{{ $player['id'] }}"
                            >
                                <div class="flex items-start justify-between gap-2">
                                    <a
                                        href="{{ route('open-play.watch.player', ['openPlayShare' => $openPlayShare, 'playerId' => $player['id']]) }}"
                                        class="truncate text-base font-bold text-emerald-800 underline decoration-emerald-300/80 underline-offset-2 transition hover:text-emerald-950 dark:text-emerald-200 dark:decoration-emerald-600/60 dark:hover:text-emerald-100"
                                    >
                                        {{ $player['name'] ?? '—' }}
                                    </a>
                                    @if (! empty($player['skipShuffle']))
                                        <span class="shrink-0 rounded-full bg-amber-100 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wide text-amber-900 dark:bg-amber-950/80 dark:text-amber-200">
                                            Sitting out
                                        </span>
                                    @endif
                                </div>
                                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                                    Lvl {{ (int) ($player['level'] ?? 0) }}
                                    @if (! empty(trim((string) ($player['teamId'] ?? ''))))
                                        · {{ $player['teamId'] }}
                                    @endif
                                </p>
                                <p class="mt-3 font-mono text-sm tabular-nums text-slate-700 dark:text-slate-300">
                                    <span class="font-semibold text-emerald-700 dark:text-emerald-400">{{ (int) ($player['wins'] ?? 0) }}</span><span class="text-slate-400">W</span>
                                    <span class="mx-1 text-slate-300 dark:text-slate-600">·</span>
                                    <span class="font-semibold text-slate-600 dark:text-slate-400">{{ (int) ($player['losses'] ?? 0) }}</span><span class="text-slate-400">L</span>
                                </p>
                                <div
                                    @class([
                                        'mt-4 flex items-center justify-between gap-3 border-t border-slate-100 pt-3 dark:border-slate-700/80',
                                        'opacity-60' => $toggleBreakBusyId === (string) ($player['id'] ?? ''),
                                    ])
                                >
                                    <label class="flex w-full cursor-pointer items-center justify-between gap-3 touch-manipulation">
                                        <span class="text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-400">Take a break</span>
                                        <input
                                            type="checkbox"
                                            class="h-6 w-6 rounded border-slate-300 text-amber-600 focus:ring-amber-500 dark:border-slate-500 dark:bg-slate-800 dark:ring-offset-slate-900"
                                            @checked(! empty($player['skipShuffle']))
                                            @disabled($toggleBreakBusyId === (string) ($player['id'] ?? ''))
                                            wire:click.prevent="requestTogglePlayerBreak('{{ $player['id'] }}')"
                                            wire:confirm="This updates the host queue. Continue?"
                                        />
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if (count($this->rosterPlayers()) === 0)
                        <p class="py-8 text-center text-sm text-slate-500 dark:text-slate-400">No players listed yet</p>
                    @endif
                </section>
            </div>
        @endif
    </div>
</div>
