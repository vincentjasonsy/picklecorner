@php
    use App\GameQ\Engine;

    $eq = new Engine($state);
    $player = $playerFound ? $eq->playerById($playerId) : null;
    $h2hByPlayer = $playerFound ? $eq->perOpponentPlayerBreakdown($playerId) : [];
    $h2hRows = $playerFound ? $eq->headToHeadRowsForPlayer($playerId) : [];
    $modeLabel = ($state['mode'] ?? '') === 'doubles' ? 'Doubles' : 'Singles';
@endphp

<div class="space-y-6 pb-[max(1rem,env(safe-area-inset-bottom))]">
    <div class="flex flex-wrap items-center gap-3">
        <a
            href="{{ route('account.open-play') }}"
            wire:navigate
            class="inline-flex min-h-[44px] items-center rounded-2xl border border-zinc-200/90 bg-white px-3 py-2 text-sm font-semibold text-zinc-700 shadow-sm transition hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800"
        >
            ← GameQ
        </a>
    </div>

    @if (! $sessionActive)
        <div class="rounded-2xl border border-amber-200/90 bg-amber-50/90 px-4 py-3 text-sm text-amber-950 dark:border-amber-900/50 dark:bg-amber-950/35 dark:text-amber-100">
            Start or resume hosting in GameQ to view a player’s head-to-head for this session.
        </div>
    @elseif (! $playerFound)
        <div class="rounded-2xl border border-zinc-200/90 bg-white px-4 py-3 text-sm text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900/80 dark:text-zinc-200">
            This player isn’t in your current session roster.
        </div>
    @else
        <header class="space-y-2">
            <p class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Hosted session</p>
            <h1 class="font-display text-2xl font-extrabold tracking-tight text-zinc-900 dark:text-white">
                {{ trim((string) ($player['name'] ?? 'Player')) }}
            </h1>
            @if (trim((string) ($state['sessionTitle'] ?? '')) !== '')
                <p class="text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ trim((string) $state['sessionTitle']) }}</p>
            @endif
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ $modeLabel }}
                <span class="mx-1.5 text-zinc-300 dark:text-zinc-600">·</span>
                Overall {{ (int) ($player['wins'] ?? 0) }}W · {{ (int) ($player['losses'] ?? 0) }}L
            </p>
        </header>

        <section class="rounded-2xl border border-zinc-200/90 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900/60 sm:p-5" aria-labelledby="gq-player-h2h-heading">
            <h2 id="gq-player-h2h-heading" class="font-display text-base font-extrabold text-zinc-900 dark:text-white">
                Head-to-head
            </h2>
            <p class="mt-1 text-xs leading-relaxed text-zinc-500 dark:text-zinc-400">
                One row per opponent with W / L / T. Expand <span class="font-medium text-zinc-600 dark:text-zinc-300">Game-by-game scores</span> for each finished game. In doubles, each rival has their own row.
            </p>

            @if (count($h2hByPlayer) > 0)
                <div class="mt-5 overflow-x-auto rounded-xl border border-zinc-200/90 dark:border-zinc-700">
                    <table class="w-full min-w-[22rem] border-collapse text-left text-sm">
                        <thead>
                            <tr class="border-b border-zinc-200 bg-zinc-50/90 dark:border-zinc-700 dark:bg-zinc-900/80">
                                <th scope="col" class="px-4 py-3 font-display text-xs font-bold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">Opponent</th>
                                <th scope="col" class="px-3 py-3 text-right font-display text-xs font-bold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">W</th>
                                <th scope="col" class="px-3 py-3 text-right font-display text-xs font-bold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">L</th>
                                <th scope="col" class="px-3 py-3 text-right font-display text-xs font-bold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">T</th>
                                <th scope="col" class="px-4 py-3 text-right font-display text-xs font-bold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Games</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($h2hByPlayer as $oppIdx => $block)
                                <tr
                                    class="border-b border-zinc-100 odd:bg-white/60 even:bg-zinc-50/40 dark:border-zinc-800 dark:odd:bg-zinc-950/40 dark:even:bg-zinc-900/25"
                                    wire:key="gq-h2h-wlt-{{ $block['opponentId'] }}"
                                >
                                    <td class="max-w-[14rem] px-4 py-2.5 font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ $block['displayName'] }}
                                    </td>
                                    <td class="px-3 py-2.5 text-right font-mono text-sm font-semibold tabular-nums text-emerald-700 dark:text-emerald-400">
                                        {{ (int) ($block['winsSelf'] ?? 0) }}
                                    </td>
                                    <td class="px-3 py-2.5 text-right font-mono text-sm font-semibold tabular-nums text-zinc-700 dark:text-zinc-300">
                                        {{ (int) ($block['winsOpp'] ?? 0) }}
                                    </td>
                                    <td class="px-3 py-2.5 text-right font-mono text-sm tabular-nums text-amber-900 dark:text-amber-200/90">
                                        {{ (int) ($block['ties'] ?? 0) }}
                                    </td>
                                    <td class="px-4 py-2.5 text-right font-mono text-xs tabular-nums text-zinc-500 dark:text-zinc-400">
                                        {{ (int) ($block['games'] ?? 0) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <details
                    class="group mt-5 rounded-xl border border-zinc-200/90 bg-zinc-50/50 dark:border-zinc-700 dark:bg-zinc-950/30"
                    @if ($gameLogExpanded) open @endif
                    x-on:toggle="$wire.set('gameLogExpanded', $event.target.open)"
                >
                    <summary
                        class="cursor-pointer list-none px-4 py-3 text-sm font-semibold text-zinc-800 marker:hidden dark:text-zinc-200 [&::-webkit-details-marker]:hidden"
                    >
                        <span class="inline-flex items-center gap-2">
                            Game-by-game scores
                            <span class="text-xs font-normal text-zinc-500 dark:text-zinc-400">(optional detail)</span>
                        </span>
                    </summary>
                    <div class="border-t border-zinc-200/80 px-2 pb-3 pt-1 dark:border-zinc-700/80 sm:px-3">
                        <div class="overflow-x-auto rounded-lg border border-zinc-200/80 dark:border-zinc-700">
                            <table class="w-full min-w-[20rem] border-collapse text-left text-sm">
                                <thead>
                                    <tr class="border-b border-zinc-200 bg-white/90 dark:border-zinc-700 dark:bg-zinc-900/60">
                                        <th scope="col" class="px-3 py-2 font-display text-[10px] font-bold uppercase tracking-wide text-zinc-600 dark:text-zinc-400">Opponent</th>
                                        <th scope="col" class="px-3 py-2 font-display text-[10px] font-bold uppercase tracking-wide text-zinc-600 dark:text-zinc-400">Score</th>
                                        <th scope="col" class="px-3 py-2 text-right font-display text-[10px] font-bold uppercase tracking-wide text-zinc-600 dark:text-zinc-400">Result</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($h2hByPlayer as $oppIdx => $block)
                                        @foreach ($block['lines'] as $lineIdx => $line)
                                            <tr
                                                class="border-b border-zinc-100 odd:bg-white/90 even:bg-zinc-50/50 dark:border-zinc-800 dark:odd:bg-zinc-950/50 dark:even:bg-zinc-900/20"
                                                wire:key="gq-h2h-detail-{{ $oppIdx }}-{{ $lineIdx }}-{{ $line['at'] }}"
                                            >
                                                <td class="max-w-[10rem] px-3 py-2 text-zinc-900 dark:text-zinc-100">
                                                    {{ $block['displayName'] }}
                                                </td>
                                                <td class="whitespace-nowrap px-3 py-2 font-mono text-xs tabular-nums text-zinc-800 dark:text-zinc-100">
                                                    {{ $eq->formatMatchScoreDisplay($line['scoreSelf']) }}
                                                    <span class="mx-0.5 text-zinc-400">–</span>
                                                    {{ $eq->formatMatchScoreDisplay($line['scoreOpp']) }}
                                                </td>
                                                <td class="whitespace-nowrap px-3 py-2 text-right">
                                                    @if ($line['won'] === true)
                                                        <span class="text-[11px] font-bold uppercase text-emerald-700 dark:text-emerald-400">W</span>
                                                    @elseif ($line['won'] === false)
                                                        <span class="text-[11px] font-bold uppercase text-zinc-600 dark:text-zinc-400">L</span>
                                                    @else
                                                        <span class="text-[11px] font-bold uppercase text-amber-900 dark:text-amber-300/90">T</span>
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
                <div class="mt-5 overflow-x-auto rounded-xl border border-zinc-200/90 dark:border-zinc-700">
                    <table class="w-full min-w-[22rem] border-collapse text-left text-sm">
                        <thead>
                            <tr class="border-b border-zinc-200 bg-zinc-50/90 dark:border-zinc-700 dark:bg-zinc-900/80">
                                <th scope="col" class="px-4 py-3 font-display text-xs font-bold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">Opponent</th>
                                <th scope="col" class="px-3 py-3 text-right font-display text-xs font-bold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">W</th>
                                <th scope="col" class="px-3 py-3 text-right font-display text-xs font-bold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">L</th>
                                <th scope="col" class="px-4 py-3 text-right font-display text-xs font-bold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Games</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($h2hRows as $row)
                                <tr class="border-b border-zinc-100 odd:bg-white/60 even:bg-zinc-50/40 dark:border-zinc-800 dark:odd:bg-zinc-950/40 dark:even:bg-zinc-900/25" wire:key="gq-h2h-fallback-{{ $loop->index }}">
                                    <td class="px-4 py-2.5 font-medium text-zinc-900 dark:text-zinc-100">{{ $row['opponentLabel'] }}</td>
                                    <td class="px-3 py-2.5 text-right font-mono text-sm font-semibold tabular-nums text-emerald-700 dark:text-emerald-400">{{ (int) ($row['winsSelf'] ?? 0) }}</td>
                                    <td class="px-3 py-2.5 text-right font-mono text-sm font-semibold tabular-nums text-zinc-700 dark:text-zinc-300">{{ (int) ($row['winsOpp'] ?? 0) }}</td>
                                    <td class="px-4 py-2.5 text-right font-mono text-xs tabular-nums text-zinc-500 dark:text-zinc-400">{{ (int) ($row['games'] ?? 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">No head-to-head games recorded yet.</p>
            @endif
        </section>
    @endif
</div>
