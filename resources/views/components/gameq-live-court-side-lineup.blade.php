@php
    /** @var \App\GameQ\Engine $eq */
    $ids = isset($playerIds) && is_array($playerIds) ? $playerIds : [];
    $variant = $variant ?? 'live';
    $compact = (bool) ($compact ?? false);
    $align = $align ?? 'start';
    $isOrganizer = $variant === 'organizer';
    $alignEnd = $align === 'end';
@endphp
<div
    @class([
        'flex flex-col gap-3 sm:flex-row sm:flex-wrap',
        'sm:items-start sm:justify-start' => ! $alignEnd,
        'sm:items-end sm:justify-end' => $alignEnd,
        'sm:gap-x-5 sm:gap-y-3' => ! $compact,
        'sm:gap-x-4 sm:gap-y-2' => $compact,
    ])
>
    @foreach ($ids as $idx => $pid)
        @php
            $p = $eq->playerById($pid);
            $name = $p ? trim((string) ($p['name'] ?? '?')) : '?';
            if ($name === '') {
                $name = '?';
            }
            $w = $p ? (int) ($p['wins'] ?? 0) : 0;
            $l = $p ? (int) ($p['losses'] ?? 0) : 0;
            $lvl = $p ? \App\GameQ\Engine::clampSkillLevel($p['level'] ?? 3, 3) : 3;
            $stars = $eq->skillStarsFilledFive($lvl);
        @endphp
        <div
            @class([
                'flex min-w-0 max-w-full flex-col gap-0.5',
                'items-start' => ! $alignEnd,
                'items-end text-right' => $alignEnd,
                'sm:max-w-[min(100%,14rem)]' => $compact,
            ])
        >
            <span
                @class([
                    'font-display font-bold leading-tight tracking-tight',
                    'text-base text-zinc-900 sm:text-lg dark:text-zinc-100' => $isOrganizer && ! $compact,
                    'text-sm text-zinc-900 sm:text-base dark:text-zinc-100' => $isOrganizer && $compact,
                    'text-lg text-slate-900 sm:text-xl dark:text-slate-50' => ! $isOrganizer && ! $compact,
                    'text-base text-slate-900 sm:text-lg dark:text-slate-50' => ! $isOrganizer && $compact,
                ])
            >{{ $name }}</span>
            <span
                @class([
                    'font-semibold tabular-nums',
                    'text-[11px] text-zinc-600 sm:text-xs dark:text-zinc-400' => $isOrganizer,
                    'text-[11px] text-slate-600 sm:text-xs dark:text-slate-400' => ! $isOrganizer,
                ])
            >({{ $w }}–{{ $l }})</span>
            <span
                @class([
                    'font-normal leading-none tracking-tight text-amber-600 dark:text-amber-400/95',
                    'text-xs' => ! $compact,
                    'text-[11px]' => $compact,
                ])
                title="Host skill level {{ $lvl }} (1–5), shown as {{ $stars }}/5 stars"
                aria-hidden="true"
            >{{ str_repeat('★', $stars) }}{{ str_repeat('☆', 5 - $stars) }}</span>
            <span class="sr-only">Skill {{ $stars }} of 5 stars (host level {{ $lvl }} of 5)</span>
        </div>
    @endforeach
</div>
