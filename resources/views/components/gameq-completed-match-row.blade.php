@props([
    'eq',
    'match',
    'index',
    'compact' => false,
])

@php
    $sideAWon = $eq->completedMatchSideAWon($match);
    $courtIndex = isset($match['courtIndex']) ? (int) $match['courtIndex'] : null;
    $courtLabel = $courtIndex !== null ? $eq->courtDisplayLabel($courtIndex) : null;
    $canRestore = $eq->canRestoreCompletedMatchToCourt($index);
@endphp

<li
    {{ $attributes->merge(['class' => 'rounded-xl border border-zinc-200/80 bg-white px-3 py-3 dark:border-zinc-700 dark:bg-zinc-950/60 sm:px-4']) }}
    wire:key="match-log-{{ $index }}"
>
    <div class="flex flex-wrap items-center justify-between gap-2">
        <p class="text-[11px] font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
            Game {{ $index + 1 }}
            @if ($courtLabel)
                · {{ $courtLabel }}
            @endif
        </p>
        @if ($sideAWon)
            <span class="rounded-md bg-emerald-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-200">A won</span>
        @else
            <span class="rounded-md bg-emerald-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-200">B won</span>
        @endif
    </div>

    <div @class([
        'mt-3 grid grid-cols-1 items-start gap-3',
        'sm:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] sm:gap-3' => ! $compact,
        'gap-2' => $compact,
    ])>
        <div @class([
            'min-w-0 rounded-lg border px-3 py-2.5 sm:text-left',
            'border-emerald-300/90 bg-emerald-50/60 dark:border-emerald-800/50 dark:bg-emerald-950/25' => $sideAWon,
            'border-zinc-200/80 bg-zinc-50/40 dark:border-zinc-700 dark:bg-zinc-900/40' => ! $sideAWon,
        ])>
            <p class="text-[10px] font-bold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Side A</p>
            <div class="mt-1 min-w-0 text-zinc-800 dark:text-zinc-200">
                @include('components.gameq-live-court-side-lineup', [
                    'eq' => $eq,
                    'playerIds' => $match['sideA'] ?? [],
                    'variant' => 'organizer',
                    'compact' => true,
                ])
            </div>
            <button
                type="button"
                @class([
                    'mt-2 w-full touch-manipulation rounded-lg px-3 py-2 text-xs font-bold shadow-sm transition active:scale-[0.98]',
                    'bg-emerald-600 text-white hover:bg-emerald-500 dark:shadow-emerald-950/40' => $sideAWon,
                    'border border-emerald-200/90 bg-white text-emerald-900 hover:bg-emerald-50 dark:border-emerald-900/40 dark:bg-zinc-900 dark:text-emerald-100 dark:hover:bg-emerald-950/40' => ! $sideAWon,
                ])
                wire:click="setLogMatchWinner({{ $index }}, 'a')"
            >
                {{ $sideAWon ? 'A won ✓' : 'Change to A' }}
            </button>
        </div>

        @unless ($compact)
            <div class="hidden items-center justify-center self-center sm:flex">
                <span class="text-xs font-medium text-zinc-400 dark:text-zinc-500">vs</span>
            </div>
        @endunless

        <div @class([
            'min-w-0 rounded-lg border px-3 py-2.5 sm:text-right',
            'border-emerald-300/90 bg-emerald-50/60 dark:border-emerald-800/50 dark:bg-emerald-950/25' => ! $sideAWon,
            'border-zinc-200/80 bg-zinc-50/40 dark:border-zinc-700 dark:bg-zinc-900/40' => $sideAWon,
        ])>
            <p class="text-[10px] font-bold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Side B</p>
            <div class="mt-1 min-w-0 text-zinc-800 dark:text-zinc-200">
                @include('components.gameq-live-court-side-lineup', [
                    'eq' => $eq,
                    'playerIds' => $match['sideB'] ?? [],
                    'variant' => 'organizer',
                    'compact' => true,
                    'align' => 'end',
                ])
            </div>
            <button
                type="button"
                @class([
                    'mt-2 w-full touch-manipulation rounded-lg px-3 py-2 text-xs font-bold shadow-sm transition active:scale-[0.98]',
                    'bg-emerald-600 text-white hover:bg-emerald-500 dark:shadow-emerald-950/40' => ! $sideAWon,
                    'border border-emerald-200/90 bg-white text-emerald-900 hover:bg-emerald-50 dark:border-emerald-900/40 dark:bg-zinc-900 dark:text-emerald-100 dark:hover:bg-emerald-950/40' => $sideAWon,
                ])
                wire:click="setLogMatchWinner({{ $index }}, 'b')"
            >
                {{ ! $sideAWon ? 'B won ✓' : 'Change to B' }}
            </button>
        </div>
    </div>

    <div class="mt-3 flex flex-wrap items-center justify-end gap-x-4 gap-y-2 border-t border-zinc-100 pt-2.5 dark:border-zinc-800">
        @if ($canRestore)
            <button
                type="button"
                class="touch-manipulation text-xs font-semibold text-emerald-700 underline decoration-emerald-300 underline-offset-2 hover:text-emerald-900 dark:text-emerald-300 dark:decoration-emerald-800 dark:hover:text-emerald-100"
                wire:confirm="Put this game back on court? It will be removed from the finished list and won't count toward standings."
                wire:click="restoreCompletedMatchToCourt({{ $index }})"
            >
                Put back on court
            </button>
        @endif
        <button
            type="button"
            class="touch-manipulation text-xs font-semibold text-zinc-500 underline decoration-zinc-300 underline-offset-2 hover:text-red-600 hover:decoration-red-400 dark:text-zinc-400 dark:decoration-zinc-600 dark:hover:text-red-400"
            wire:confirm="Remove this game from the log? It won't count toward standings."
            wire:click="removeCompletedMatch({{ $index }})"
        >
            Remove from log
        </button>
    </div>
</li>
