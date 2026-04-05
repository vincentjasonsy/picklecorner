@props([
    'column',
    'label',
    'active' => '',
    'direction' => 'desc',
    'align' => 'left',
])

@php
    $alignClass = match ($align) {
        'right' => 'text-right',
        'center' => 'text-center',
        default => 'text-left',
    };
@endphp

<th class="px-4 py-3 font-semibold text-zinc-700 dark:text-zinc-300 {{ $alignClass }}">
    <button
        type="button"
        wire:click="sortBy('{{ $column }}')"
        @class([
            'group inline-flex max-w-full items-center gap-1 font-semibold text-zinc-700 hover:text-zinc-950 dark:text-zinc-300 dark:hover:text-white',
            'text-left' => $align === 'left',
            'w-full justify-end text-right' => $align === 'right',
            'w-full justify-center' => $align === 'center',
        ])
    >
        <span class="truncate">{{ $label }}</span>
        <span
            class="shrink-0 tabular-nums text-xs text-zinc-400 dark:text-zinc-500"
            aria-hidden="true"
        >
            @if ($active === $column)
                {{ $direction === 'asc' ? '↑' : '↓' }}
            @else
                <span class="opacity-0 transition group-hover:opacity-100">↕</span>
            @endif
        </span>
    </button>
</th>
