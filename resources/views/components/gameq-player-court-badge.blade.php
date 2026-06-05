@props([
    'eq',
    'playerId',
])

@php
    $courtLabel = $eq->playerActiveCourtLabel($playerId);
@endphp

@if ($courtLabel)
    <span
        {{ $attributes->merge([
            'class' => 'inline-flex shrink-0 items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-800 ring-1 ring-emerald-200/80 dark:bg-emerald-950/50 dark:text-emerald-200 dark:ring-emerald-800/50',
        ]) }}
    >
        <span class="relative flex h-1.5 w-1.5" aria-hidden="true">
            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-500 opacity-75"></span>
            <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
        </span>
        <span>Playing · {{ $courtLabel }}</span>
    </span>
@endif
