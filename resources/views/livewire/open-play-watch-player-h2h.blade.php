@php
    use App\GameQ\Engine;

    $eq = new Engine($game);
    $player = $playerFound ? $eq->playerById($playerId) : null;
    $h2hBreakdown = $playerFound ? $eq->playerOpponentGameBreakdown($playerId) : [];
    $h2hRows = $playerFound ? $eq->headToHeadRowsForPlayer($playerId) : [];
    $modeLabel = ($game['mode'] ?? '') === 'doubles' ? 'Doubles' : 'Singles';
@endphp

<div
    class="relative min-h-[70vh] overflow-hidden pb-[max(2rem,env(safe-area-inset-bottom))] pt-2"
    wire:poll.2s="refreshGame"
>
    <div class="pointer-events-none absolute inset-0 -z-10 bg-gradient-to-b from-emerald-100/40 via-zinc-50 to-sky-100/30 dark:from-emerald-950/30 dark:via-zinc-950 dark:to-slate-950" aria-hidden="true"></div>
    <div class="pointer-events-none absolute -left-24 top-20 h-72 w-72 rounded-full bg-emerald-400/20 blur-3xl dark:bg-emerald-500/10" aria-hidden="true"></div>
    <div class="pointer-events-none absolute -right-16 bottom-32 h-64 w-64 rounded-full bg-sky-400/15 blur-3xl dark:bg-sky-500/10" aria-hidden="true"></div>

    <div class="relative mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-center gap-3">
            <a
                href="{{ route('open-play.watch', $openPlayShare) }}"
                class="inline-flex min-h-[44px] items-center rounded-2xl border border-slate-200/90 bg-white/90 px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-slate-200/80 transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900/80 dark:text-slate-200 dark:ring-slate-700 dark:hover:bg-slate-800"
            >
                ← Back to live
            </a>
        </div>

        @if ($loadFailed)
            <div class="mt-6 rounded-2xl border border-red-200/80 bg-red-50/90 px-4 py-3 text-sm text-red-900 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-100">
                Could not load live data. Check your connection and refresh.
            </div>
        @elseif (! $playerFound)
            <div class="mt-6 rounded-2xl border border-slate-200/90 bg-white/90 px-4 py-3 text-sm text-slate-700 shadow-sm dark:border-slate-700 dark:bg-slate-900/80 dark:text-slate-200">
                This player isn’t in the session roster for this link.
            </div>
        @else
            <header class="mt-6 space-y-2">
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400/90">Live session</p>
                <h1 class="font-display text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                    {{ trim((string) ($player['name'] ?? 'Player')) }}
                </h1>
                @if (trim((string) ($game['sessionTitle'] ?? '')) !== '')
                    <p class="text-sm font-medium text-slate-600 dark:text-slate-300">{{ trim((string) $game['sessionTitle']) }}</p>
                @endif
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    {{ $modeLabel }}
                    <span class="mx-1.5 text-slate-300 dark:text-slate-600">·</span>
                    Overall {{ (int) ($player['wins'] ?? 0) }}W · {{ (int) ($player['losses'] ?? 0) }}L
                </p>
                @if ($updatedAtIso)
                    <p class="font-mono text-xs text-slate-400">Synced from host</p>
                @endif
            </header>

            <section
                class="mt-8 overflow-hidden rounded-2xl border border-emerald-200/60 bg-white/80 p-5 shadow-lg shadow-emerald-900/5 ring-1 ring-emerald-100/80 backdrop-blur-sm dark:border-emerald-900/40 dark:bg-emerald-950/20 dark:ring-emerald-900/30 sm:p-6"
                aria-labelledby="gq-watch-player-h2h-heading"
            >
                <h2 id="gq-watch-player-h2h-heading" class="font-display text-base font-extrabold text-slate-900 dark:text-white">
                    Head-to-head
                </h2>
                <p class="mt-1 text-xs leading-relaxed text-slate-500 dark:text-slate-400">
                    Record against each opponent or pair, with scores from the session log (same data as the host).
                </p>

                @if (count($h2hBreakdown) > 0)
                    <div class="mt-5 space-y-5">
                        @foreach ($h2hBreakdown as $block)
                            <div
                                class="rounded-xl border border-slate-200/90 bg-slate-50/80 p-4 dark:border-slate-700 dark:bg-slate-950/50"
                                wire:key="gq-watch-h2h-block-{{ $block['pairingKey'] }}"
                            >
                                <div class="flex flex-wrap items-baseline justify-between gap-2">
                                    <p class="font-semibold leading-snug text-slate-900 dark:text-slate-100">
                                        vs {{ $block['opponentLabel'] }}
                                    </p>
                                    <p class="shrink-0 text-right text-sm tabular-nums text-slate-600 dark:text-slate-300">
                                        <span class="font-mono font-bold text-emerald-700 dark:text-emerald-400">{{ $block['winsSelf'] }} – {{ $block['winsOpp'] }}</span>
                                        <span class="ml-2 text-[11px] text-slate-400 dark:text-slate-500">
                                            ({{ $block['games'] }} {{ $block['games'] === 1 ? 'game' : 'games' }})
                                        </span>
                                    </p>
                                </div>
                                <ul class="mt-3 space-y-2 border-t border-slate-200/80 pt-3 dark:border-slate-700/80">
                                    @foreach ($block['lines'] as $line)
                                        <li class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 text-sm" wire:key="gq-watch-h2h-line-{{ $block['pairingKey'] }}-{{ $line['at'] }}-{{ $loop->index }}">
                                            <span class="font-mono tabular-nums text-slate-800 dark:text-slate-100">
                                                {{ $eq->formatMatchScoreDisplay($line['scoreSelf']) }}
                                                <span class="mx-1 text-slate-400">–</span>
                                                {{ $eq->formatMatchScoreDisplay($line['scoreOpp']) }}
                                            </span>
                                            @if ($line['won'] === true)
                                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide text-emerald-900 dark:bg-emerald-950/60 dark:text-emerald-200">Win</span>
                                            @elseif ($line['won'] === false)
                                                <span class="rounded-full bg-slate-200/90 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide text-slate-700 dark:bg-slate-600 dark:text-slate-100">Loss</span>
                                            @else
                                                <span class="rounded-full bg-amber-100/90 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide text-amber-950 dark:bg-amber-950/50 dark:text-amber-100">Tie</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>
                @elseif (count($h2hRows) > 0)
                    <ul class="mt-4 divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($h2hRows as $row)
                            <li class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 py-3 first:pt-0">
                                <span class="min-w-0 flex-1 font-medium leading-snug text-slate-800 dark:text-slate-100">
                                    vs {{ $row['opponentLabel'] }}
                                </span>
                                <span class="shrink-0 text-right">
                                    <span class="font-mono text-sm font-bold tabular-nums text-emerald-700 dark:text-emerald-400">
                                        {{ $row['winsSelf'] }} – {{ $row['winsOpp'] }}
                                    </span>
                                    <span class="ml-2 text-[11px] text-slate-400 dark:text-slate-500">
                                        ({{ $row['games'] }} {{ $row['games'] === 1 ? 'game' : 'games' }})
                                    </span>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">No head-to-head games recorded yet.</p>
                @endif
            </section>
        @endif
    </div>
</div>
