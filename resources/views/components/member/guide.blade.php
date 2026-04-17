@props([
    'title' => 'How this works',
])
<div
    {{ $attributes->class('rounded-2xl border border-sky-200/90 bg-gradient-to-br from-sky-50/90 to-white p-4 text-sm leading-relaxed text-sky-950 shadow-sm dark:border-sky-900/40 dark:from-sky-950/40 dark:to-zinc-900/60 dark:text-sky-100') }}
>
    <p class="flex items-center gap-2 font-display text-xs font-bold tracking-wide text-sky-600 dark:text-sky-300">
        <span class="text-base leading-none" aria-hidden="true">✨</span>
        {{ $title }}
    </p>
    <div class="mt-2 space-y-2 text-sky-900/90 dark:text-sky-100/95">
        {{ $slot }}
    </div>
</div>
