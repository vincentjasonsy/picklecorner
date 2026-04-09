@php
    use App\GameQ\Engine;

    $eq = new Engine($state);
    $player = $playerFound ? $eq->playerById($playerId) : null;
    $h2hBreakdown = $playerFound ? $eq->playerOpponentGameBreakdown($playerId) : [];
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
                Record against each opponent or pair, with individual game scores from the session log when available.
            </p>

            @if (count($h2hBreakdown) > 0)
                <div class="mt-5 space-y-5">
                    @foreach ($h2hBreakdown as $block)
                        <div
                            class="rounded-xl border border-zinc-100 bg-zinc-50/80 p-4 dark:border-zinc-800 dark:bg-zinc-950/50"
                            wire:key="gq-h2h-block-{{ $block['pairingKey'] }}"
                        >
                            <div class="flex flex-wrap items-baseline justify-between gap-2">
                                <p class="font-semibold leading-snug text-zinc-900 dark:text-zinc-100">
                                    vs {{ $block['opponentLabel'] }}
                                </p>
                                <p class="shrink-0 text-right text-sm tabular-nums text-zinc-600 dark:text-zinc-300">
                                    <span class="font-mono font-bold text-emerald-700 dark:text-emerald-400">{{ $block['winsSelf'] }} – {{ $block['winsOpp'] }}</span>
                                    <span class="ml-2 text-[11px] text-zinc-400 dark:text-zinc-500">
                                        ({{ $block['games'] }} {{ $block['games'] === 1 ? 'game' : 'games' }})
                                    </span>
                                </p>
                            </div>
                            <ul class="mt-3 space-y-2 border-t border-zinc-200/80 pt-3 dark:border-zinc-700/80">
                                @foreach ($block['lines'] as $line)
                                    <li class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 text-sm" wire:key="gq-h2h-line-{{ $block['pairingKey'] }}-{{ $line['at'] }}-{{ $loop->index }}">
                                        <span class="font-mono tabular-nums text-zinc-800 dark:text-zinc-100">
                                            {{ $eq->formatMatchScoreDisplay($line['scoreSelf']) }}
                                            <span class="mx-1 text-zinc-400">–</span>
                                            {{ $eq->formatMatchScoreDisplay($line['scoreOpp']) }}
                                        </span>
                                        @if ($line['won'] === true)
                                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide text-emerald-900 dark:bg-emerald-950/60 dark:text-emerald-200">Win</span>
                                        @elseif ($line['won'] === false)
                                            <span class="rounded-full bg-zinc-200/90 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide text-zinc-700 dark:bg-zinc-600 dark:text-zinc-100">Loss</span>
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
                <ul class="mt-4 divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($h2hRows as $row)
                        <li class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 py-3 first:pt-0">
                            <span class="min-w-0 flex-1 font-medium leading-snug text-zinc-800 dark:text-zinc-100">
                                vs {{ $row['opponentLabel'] }}
                            </span>
                            <span class="shrink-0 text-right">
                                <span class="font-mono text-sm font-bold tabular-nums text-emerald-700 dark:text-emerald-400">
                                    {{ $row['winsSelf'] }} – {{ $row['winsOpp'] }}
                                </span>
                                <span class="ml-2 text-[11px] text-zinc-400 dark:text-zinc-500">
                                    ({{ $row['games'] }} {{ $row['games'] === 1 ? 'game' : 'games' }})
                                </span>
                            </span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">No head-to-head games recorded yet.</p>
            @endif
        </section>
    @endif
</div>
