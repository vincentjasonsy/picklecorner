@props([
    'controls' => 'portal-sidebar',
])

<button
    type="button"
    {{ $attributes->merge([
        'class' =>
            'inline-flex size-10 shrink-0 items-center justify-center rounded-lg border transition lg:hidden',
    ]) }}
    @click="portalNavOpen = ! portalNavOpen"
    :aria-expanded="portalNavOpen.toString()"
    aria-controls="{{ $controls }}"
>
    <span class="sr-only" x-text="portalNavOpen ? 'Close navigation' : 'Open navigation'"></span>
    <svg
        x-show="! portalNavOpen"
        class="size-6"
        fill="none"
        viewBox="0 0 24 24"
        stroke-width="1.5"
        stroke="currentColor"
        aria-hidden="true"
    >
        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
    </svg>
    <svg
        x-show="portalNavOpen"
        x-cloak
        class="size-6"
        fill="none"
        viewBox="0 0 24 24"
        stroke-width="1.5"
        stroke="currentColor"
        aria-hidden="true"
    >
        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
    </svg>
</button>
