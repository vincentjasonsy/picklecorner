@props([
    'level' => 3,
    'playerIndex' => null,
])

@php
    use App\GameQ\Engine;

    $current = Engine::clampSkillLevel($level, 3);
@endphp

<div
    role="group"
    {{ $attributes->merge(['class' => 'inline-flex items-center gap-0.5']) }}
    aria-label="Skill level {{ $current }} of 5"
>
    @for ($star = 1; $star <= 5; $star++)
        <button
            type="button"
            @class([
                'touch-manipulation rounded p-0.5 text-base leading-none transition sm:text-lg',
                'text-amber-500 dark:text-amber-400' => $star <= $current,
                'text-zinc-300 hover:text-amber-400/70 dark:text-zinc-600 dark:hover:text-amber-400/80' => $star > $current,
            ])
            @if ($playerIndex === null)
                wire:click="setNewPlayerLevel({{ $star }})"
            @else
                wire:click="setRosterPlayerLevel({{ $playerIndex }}, {{ $star }})"
            @endif
            aria-label="Set skill level to {{ $star }}"
            aria-pressed="{{ $star <= $current ? 'true' : 'false' }}"
        >
            <span aria-hidden="true">{{ $star <= $current ? '★' : '☆' }}</span>
        </button>
    @endfor
    <span class="sr-only">{{ $current }} of 5 stars</span>
</div>
