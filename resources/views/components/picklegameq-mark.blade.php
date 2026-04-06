@props([
    'compact' => false,
])

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5']) }}>
    <span>PickleGameQ</span>
    <span
        @class([
            'shrink-0 rounded-md border font-bold uppercase tracking-wider text-amber-900 dark:text-amber-200',
            'border-amber-200 bg-amber-50 px-1 py-px text-[9px] leading-none dark:border-amber-800/60 dark:bg-amber-950/50' => $compact,
            'border-amber-200/90 bg-amber-50 px-1.5 py-0.5 text-[10px] dark:border-amber-800/60 dark:bg-amber-950/50' => ! $compact,
        ])
    >
        Beta
    </span>
</span>
