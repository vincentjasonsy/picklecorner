@php
    use App\GameQ\Engine;

    $eq = new Engine($game);
    $player = $playerFound ? $eq->playerById($playerId) : null;
    $h2hByPlayer = $playerFound ? $eq->perOpponentPlayerBreakdown($playerId) : [];
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
                    One row per opponent with win / loss / tie counts. Open <span class="font-medium text-slate-600 dark:text-slate-300">Game-by-game scores</span> to see each finished game. In doubles, each rival gets their own row.
                </p>

                @if (count($h2hByPlayer) > 0)
                    <div class="mt-5 overflow-x-auto rounded-xl border border-slate-200/90 dark:border-slate-700">
                        <table class="w-full min-w-[22rem] border-collapse text-left text-sm">
                            <thead>
                                <tr class="border-b border-slate-200 bg-slate-50/90 dark:border-slate-700 dark:bg-slate-900/80">
                                    <th scope="col" class="px-4 py-3 font-display text-xs font-bold uppercase tracking-wide text-slate-600 dark:text-slate-300">Opponent</th>
                                    <th scope="col" class="px-3 py-3 text-right font-display text-xs font-bold uppercase tracking-wide text-slate-600 dark:text-slate-300">W</th>
                                    <th scope="col" class="px-3 py-3 text-right font-display text-xs font-bold uppercase tracking-wide text-slate-600 dark:text-slate-300">L</th>
                                    <th scope="col" class="px-3 py-3 text-right font-display text-xs font-bold uppercase tracking-wide text-slate-600 dark:text-slate-300">T</th>
                                    <th scope="col" class="px-4 py-3 text-right font-display text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Games</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($h2hByPlayer as $oppIdx => $block)
                                    <tr
                                        class="border-b border-slate-100 odd:bg-white/60 even:bg-slate-50/40 dark:border-slate-800 dark:odd:bg-slate-950/40 dark:even:bg-slate-900/25"
                                        wire:key="gq-watch-h2h-wlt-{{ $block['opponentId'] }}"
                                    >
                                        <td class="max-w-[14rem] px-4 py-2.5 font-medium text-slate-900 dark:text-slate-100">
                                            {{ $block['displayName'] }}
                                        </td>
                                        <td class="px-3 py-2.5 text-right font-mono text-sm font-semibold tabular-nums text-emerald-700 dark:text-emerald-400">
                                            {{ (int) ($block['winsSelf'] ?? 0) }}
                                        </td>
                                        <td class="px-3 py-2.5 text-right font-mono text-sm font-semibold tabular-nums text-slate-700 dark:text-slate-300">
                                            {{ (int) ($block['winsOpp'] ?? 0) }}
                                        </td>
                                        <td class="px-3 py-2.5 text-right font-mono text-sm tabular-nums text-amber-800 dark:text-amber-200/90">
                                            {{ (int) ($block['ties'] ?? 0) }}
                                        </td>
                                        <td class="px-4 py-2.5 text-right font-mono text-xs tabular-nums text-slate-500 dark:text-slate-400">
                                            {{ (int) ($block['games'] ?? 0) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <details
                        class="group mt-5 rounded-xl border border-slate-200/90 bg-slate-50/50 dark:border-slate-700 dark:bg-slate-950/30"
                        @if ($gameLogExpanded) open @endif
                        x-on:toggle="$wire.set('gameLogExpanded', $event.target.open)"
                    >
                        <summary
                            class="cursor-pointer list-none px-4 py-3 text-sm font-semibold text-slate-800 marker:hidden dark:text-slate-200 [&::-webkit-details-marker]:hidden"
                        >
                            <span class="inline-flex items-center gap-2">
                                Game-by-game scores
                                <span class="text-xs font-normal text-slate-500 dark:text-slate-400">(optional detail)</span>
                            </span>
                        </summary>
                        <div class="border-t border-slate-200/80 px-2 pb-3 pt-1 dark:border-slate-700/80 sm:px-3">
                            <div class="overflow-x-auto rounded-lg border border-slate-200/80 dark:border-slate-700">
                                <table class="w-full min-w-[20rem] border-collapse text-left text-sm">
                                    <thead>
                                        <tr class="border-b border-slate-200 bg-white/80 dark:border-slate-700 dark:bg-slate-900/60">
                                            <th scope="col" class="px-3 py-2 font-display text-[10px] font-bold uppercase tracking-wide text-slate-600 dark:text-slate-400">Opponent</th>
                                            <th scope="col" class="px-3 py-2 font-display text-[10px] font-bold uppercase tracking-wide text-slate-600 dark:text-slate-400">Score</th>
                                            <th scope="col" class="px-3 py-2 text-right font-display text-[10px] font-bold uppercase tracking-wide text-slate-600 dark:text-slate-400">Result</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($h2hByPlayer as $oppIdx => $block)
                                            @foreach ($block['lines'] as $lineIdx => $line)
                                                <tr
                                                    class="border-b border-slate-100 odd:bg-white/80 even:bg-slate-50/50 dark:border-slate-800 dark:odd:bg-slate-950/50 dark:even:bg-slate-900/20"
                                                    wire:key="gq-watch-h2h-detail-{{ $oppIdx }}-{{ $lineIdx }}-{{ $line['at'] }}"
                                                >
                                                    <td class="max-w-[10rem] px-3 py-2 text-slate-900 dark:text-slate-100">
                                                        {{ $block['displayName'] }}
                                                    </td>
                                                    <td class="whitespace-nowrap px-3 py-2 font-mono text-xs tabular-nums text-slate-800 dark:text-slate-100">
                                                        {{ $eq->formatMatchScoreDisplay($line['scoreSelf']) }}
                                                        <span class="mx-0.5 text-slate-400">–</span>
                                                        {{ $eq->formatMatchScoreDisplay($line['scoreOpp']) }}
                                                    </td>
                                                    <td class="whitespace-nowrap px-3 py-2 text-right">
                                                        @if ($line['won'] === true)
                                                            <span class="text-[11px] font-bold uppercase text-emerald-700 dark:text-emerald-400">W</span>
                                                        @elseif ($line['won'] === false)
                                                            <span class="text-[11px] font-bold uppercase text-slate-600 dark:text-slate-400">L</span>
                                                        @else
                                                            <span class="text-[11px] font-bold uppercase text-amber-800 dark:text-amber-300/90">T</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </details>
                @elseif (count($h2hRows) > 0)
                    <div class="mt-5 overflow-x-auto rounded-xl border border-slate-200/90 dark:border-slate-700">
                        <table class="w-full min-w-[22rem] border-collapse text-left text-sm">
                            <thead>
                                <tr class="border-b border-slate-200 bg-slate-50/90 dark:border-slate-700 dark:bg-slate-900/80">
                                    <th scope="col" class="px-4 py-3 font-display text-xs font-bold uppercase tracking-wide text-slate-600 dark:text-slate-300">Opponent</th>
                                    <th scope="col" class="px-3 py-3 text-right font-display text-xs font-bold uppercase tracking-wide text-slate-600 dark:text-slate-300">W</th>
                                    <th scope="col" class="px-3 py-3 text-right font-display text-xs font-bold uppercase tracking-wide text-slate-600 dark:text-slate-300">L</th>
                                    <th scope="col" class="px-4 py-3 text-right font-display text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Games</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($h2hRows as $row)
                                    <tr class="border-b border-slate-100 odd:bg-white/60 even:bg-slate-50/40 dark:border-slate-800 dark:odd:bg-slate-950/40 dark:even:bg-slate-900/25" wire:key="gq-watch-h2h-fallback-{{ $loop->index }}">
                                        <td class="px-4 py-2.5 font-medium text-slate-900 dark:text-slate-100">{{ $row['opponentLabel'] }}</td>
                                        <td class="px-3 py-2.5 text-right font-mono text-sm font-semibold tabular-nums text-emerald-700 dark:text-emerald-400">{{ (int) ($row['winsSelf'] ?? 0) }}</td>
                                        <td class="px-3 py-2.5 text-right font-mono text-sm font-semibold tabular-nums text-slate-700 dark:text-slate-300">{{ (int) ($row['winsOpp'] ?? 0) }}</td>
                                        <td class="px-4 py-2.5 text-right font-mono text-xs tabular-nums text-slate-500 dark:text-slate-400">{{ (int) ($row['games'] ?? 0) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">No head-to-head games recorded yet.</p>
                @endif
            </section>
        @endif
    </div>
</div>
