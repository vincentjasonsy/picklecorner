@php
    $currentRoute = request()->route()?->getName();
@endphp
<nav
    class="mt-10 flex flex-wrap justify-center gap-x-3 gap-y-2 border-t border-zinc-200 pt-8 text-center text-[11px] font-semibold uppercase tracking-wider text-zinc-500 dark:border-zinc-700 dark:text-zinc-400"
    aria-label="Legal policies"
>
    <a
        href="{{ route('terms') }}"
        wire:navigate
        @class([
            'transition-colors hover:text-emerald-600 dark:hover:text-emerald-400',
            $currentRoute === 'terms' ? 'text-emerald-700 dark:text-emerald-300' : '',
        ])
    >
        Terms &amp; conditions
    </a>
    <span class="text-zinc-300 dark:text-zinc-600" aria-hidden="true">·</span>
    <a
        href="{{ route('privacy-policy') }}"
        wire:navigate
        @class([
            'transition-colors hover:text-emerald-600 dark:hover:text-emerald-400',
            $currentRoute === 'privacy-policy' ? 'text-emerald-700 dark:text-emerald-300' : '',
        ])
    >
        Privacy policy
    </a>
    <span class="text-zinc-300 dark:text-zinc-600" aria-hidden="true">·</span>
    <a
        href="{{ route('refund-policy') }}"
        wire:navigate
        @class([
            'transition-colors hover:text-emerald-600 dark:hover:text-emerald-400',
            $currentRoute === 'refund-policy' ? 'text-emerald-700 dark:text-emerald-300' : '',
        ])
    >
        Refund policy
    </a>
    <span class="text-zinc-300 dark:text-zinc-600" aria-hidden="true">·</span>
    <a
        href="{{ route('booking-cancellation-policy') }}"
        wire:navigate
        @class([
            'transition-colors hover:text-emerald-600 dark:hover:text-emerald-400',
            $currentRoute === 'booking-cancellation-policy' ? 'text-emerald-700 dark:text-emerald-300' : '',
        ])
    >
        Booking &amp; cancellation
    </a>
</nav>
<p class="mt-6 text-center text-xs text-zinc-500 dark:text-zinc-500">
    @guest
        @if (public_registration_enabled())
            <a href="{{ route('register') }}" wire:navigate class="font-semibold text-emerald-700 hover:underline dark:text-emerald-400">
                Register
            </a>
            <span class="mx-2 text-zinc-400" aria-hidden="true">·</span>
        @endif
    @endguest
    <a href="{{ route('home') }}" wire:navigate class="font-semibold text-emerald-700 hover:underline dark:text-emerald-400">
        Home
    </a>
    <span class="mx-2 text-zinc-400" aria-hidden="true">·</span>
    <a href="{{ route('contact') }}" wire:navigate class="font-semibold text-emerald-700 hover:underline dark:text-emerald-400">
        Contact
    </a>
</p>
