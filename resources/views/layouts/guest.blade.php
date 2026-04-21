<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @include('partials.theme-init')

        @include('partials.document-title')

        @include('partials.favicon')

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link
            href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600|oswald:500,600,700"
            rel="stylesheet"
        />
        <style>
            .font-display {
                font-family: 'Oswald', 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
                letter-spacing: 0.04em;
            }
        </style>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body
        class="min-h-screen bg-zinc-50 text-zinc-900 antialiased transition-colors duration-200 dark:bg-zinc-950 dark:text-zinc-100"
    >
        @include('partials.flash-messages')
        @include('partials.impersonation-banner')

        @php
            $hideGuestPrimaryNav = request()->routeIs(['open-play.watch', 'open-play.watch.player']);
        @endphp

        <div class="flex min-h-screen flex-col">
            <header
                x-data="{ mobileNavOpen: false }"
                @keydown.escape.window="mobileNavOpen = false"
                class="sticky top-0 z-20 border-b border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900"
            >
                @auth
                    @php
                        $guestNavUser = auth()->user();
                        $staffAppUrl = $guestNavUser->staffAppHomeUrl();
                        $memberHomeUrl = $guestNavUser->memberHomeUrl();
                        $guestNavAppActive = $staffAppUrl !== null
                            ? ($guestNavUser->isSuperAdmin() && request()->routeIs('admin.*'))
                            || ($guestNavUser->isCourtAdmin() && request()->routeIs('venue.*'))
                            || ($guestNavUser->isCourtClientDesk() && request()->routeIs('desk.*'))
                            : request()->routeIs('account.*');
                    @endphp
                @endauth
                <div
                    class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-3 px-4 sm:px-6 lg:px-8"
                >
                    <a
                        href="{{ route('home') }}"
                        wire:navigate
                        class="font-display min-w-0 shrink truncate text-lg font-bold tracking-tight text-zinc-900 dark:text-zinc-100 sm:text-xl"
                    >
                        {{ config('app.name') }}
                    </a>
                    <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                        <x-theme-toggle />
                        @unless ($hideGuestPrimaryNav)
                        {{-- Desktop / large tablet navigation --}}
                        <nav
                            class="hidden items-center gap-1 text-sm font-medium lg:flex"
                            aria-label="Primary"
                        >
                            <div
                                class="relative"
                                x-data="{ homeNavOpen: false }"
                                @keydown.escape.window="homeNavOpen = false"
                            >
                                <button
                                    type="button"
                                    id="guest-home-menu-button"
                                    @class([
                                        'inline-flex items-center gap-0.5 rounded-lg px-2.5 py-2 transition-colors',
                                        request()->routeIs('home')
                                            ? 'font-semibold text-emerald-700 dark:text-emerald-400'
                                            : 'text-zinc-600 hover:text-emerald-700 dark:text-zinc-400 dark:hover:text-emerald-400',
                                    ])
                                    @click="homeNavOpen = ! homeNavOpen"
                                    :aria-expanded="homeNavOpen.toString()"
                                    aria-haspopup="menu"
                                    aria-controls="guest-home-submenu"
                                >
                                    Home
                                    <svg
                                        class="size-4 opacity-70"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                        aria-hidden="true"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </button>
                                <div
                                    id="guest-home-submenu"
                                    role="menu"
                                    x-show="homeNavOpen"
                                    x-transition
                                    x-cloak
                                    @click.outside="homeNavOpen = false"
                                    class="absolute left-0 top-full z-40 mt-1 min-w-[11rem] rounded-lg border border-zinc-200 bg-white py-1 shadow-lg dark:border-zinc-700 dark:bg-zinc-900"
                                >
                                    <a
                                        role="menuitem"
                                        href="{{ url('/#about') }}"
                                        class="block px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-50 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                        @click="homeNavOpen = false"
                                    >
                                        About
                                    </a>
                                    @if (public_reviews_enabled())
                                        <a
                                            role="menuitem"
                                            href="{{ url('/#reviews') }}"
                                            class="block px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-50 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                            @click="homeNavOpen = false"
                                        >
                                            Reviews
                                        </a>
                                    @endif
                                    <a
                                        role="menuitem"
                                        href="{{ url('/#pricing') }}"
                                        class="block px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-50 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                        @click="homeNavOpen = false"
                                    >
                                        Pricing
                                    </a>
                                </div>
                            </div>
                            <a
                                href="{{ route('book-now') }}"
                                wire:navigate
                                @class([
                                    'font-display ml-1 inline-flex items-center rounded-full bg-gradient-to-r from-yellow-400 to-amber-500 px-4 py-2 text-xs font-bold uppercase tracking-wide text-amber-950 shadow-md shadow-amber-900/25 transition hover:from-yellow-300 hover:to-amber-400 hover:shadow-lg dark:from-amber-500 dark:to-yellow-600 dark:text-amber-50 dark:shadow-amber-950/40 dark:hover:from-amber-400 dark:hover:to-yellow-500',
                                    request()->routeIs('book-now*')
                                        ? 'ring-2 ring-amber-400/90 ring-offset-2 ring-offset-white dark:ring-amber-300/80 dark:ring-offset-zinc-900'
                                        : '',
                                ])
                            >
                                Book now
                            </a>
                            <a
                                href="{{ route('contact') }}"
                                wire:navigate
                                @class([
                                    'rounded-lg px-2.5 py-2 transition-colors',
                                    request()->routeIs('contact')
                                        ? 'font-semibold text-emerald-700 dark:text-emerald-400'
                                        : 'text-zinc-600 hover:text-emerald-700 dark:text-zinc-400 dark:hover:text-emerald-400',
                                ])
                            >
                                Contact
                            </a>
                            <a
                                href="{{ route('open-play.about') }}"
                                wire:navigate
                                @class([
                                    'inline-flex items-center gap-1 rounded-lg px-2.5 py-2 transition-colors',
                                    request()->routeIs('open-play.about')
                                        ? 'font-semibold text-emerald-700 dark:text-emerald-400'
                                        : 'text-zinc-600 hover:text-emerald-700 dark:text-zinc-400 dark:hover:text-emerald-400',
                                ])
                            >
                                <x-gameq-mark compact />
                            </a>
                            @auth
                                <a
                                    href="{{ $staffAppUrl ?? $memberHomeUrl }}"
                                    wire:navigate
                                    @class([
                                        'rounded-lg px-2.5 py-2 transition-colors',
                                        $guestNavAppActive
                                            ? 'font-semibold text-emerald-700 dark:text-emerald-400'
                                            : 'text-zinc-600 hover:text-emerald-700 dark:text-zinc-400 dark:hover:text-emerald-400',
                                    ])
                                >
                                    {{ $staffAppUrl !== null ? 'Go to app' : 'My Corner' }}
                                </a>
                                <form method="POST" action="{{ route('logout') }}" class="inline">
                                    @csrf
                                    <button
                                        type="submit"
                                        class="rounded-lg px-2.5 py-2 text-zinc-600 transition-colors hover:text-emerald-700 dark:text-zinc-400 dark:hover:text-emerald-400"
                                    >
                                        Log out
                                    </button>
                                </form>
                            @else
                                <a
                                    href="{{ route('login') }}"
                                    wire:navigate
                                    @class([
                                        'rounded-lg px-2.5 py-2 transition-colors',
                                        request()->routeIs('login')
                                            ? 'font-semibold text-emerald-700 dark:text-emerald-400'
                                            : 'text-zinc-600 hover:text-emerald-700 dark:text-zinc-400 dark:hover:text-emerald-400',
                                    ])
                                >
                                    Log in
                                </a>
                                @if (public_registration_enabled())
                                    <a
                                        href="{{ route('register') }}"
                                        wire:navigate
                                        class="rounded-lg border border-emerald-600/30 bg-emerald-50/80 px-3 py-2 text-sm font-semibold text-emerald-800 transition-colors hover:bg-emerald-100 dark:border-emerald-500/40 dark:bg-emerald-950/40 dark:text-emerald-200 dark:hover:bg-emerald-950/70"
                                    >
                                        Register
                                    </a>
                                @endif
                            @endauth
                        </nav>
                        {{-- Mobile menu toggle --}}
                        <button
                            type="button"
                            class="inline-flex size-10 items-center justify-center rounded-lg border border-zinc-200 text-zinc-700 transition hover:text-emerald-700 dark:border-zinc-600 dark:text-zinc-200 dark:hover:text-emerald-400 lg:hidden"
                            aria-controls="guest-mobile-nav"
                            :aria-expanded="mobileNavOpen.toString()"
                            @click="mobileNavOpen = ! mobileNavOpen"
                        >
                            <span class="sr-only" x-text="mobileNavOpen ? 'Close menu' : 'Open menu'"></span>
                            <svg
                                x-show="! mobileNavOpen"
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
                                x-show="mobileNavOpen"
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
                        @endunless
                    </div>
                </div>
                @unless ($hideGuestPrimaryNav)
                {{-- Mobile / small tablet panel --}}
                <div
                    id="guest-mobile-nav"
                    x-show="mobileNavOpen"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-1"
                    x-cloak
                    class="border-t border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900 lg:hidden"
                >
                    <nav class="flex max-h-[min(70vh,calc(100dvh-4rem))] flex-col gap-0.5 overflow-y-auto px-4 py-3 text-base font-medium" aria-label="Primary mobile">
                        <div x-data="{ homeSubOpen: false }">
                            <button
                                type="button"
                                @class([
                                    'flex w-full items-center justify-between rounded-lg px-3 py-3 text-left transition-colors',
                                    request()->routeIs('home')
                                        ? 'font-semibold text-emerald-700 dark:text-emerald-400'
                                        : 'text-zinc-800 hover:text-emerald-700 dark:text-zinc-100 dark:hover:text-emerald-400',
                                ])
                                @click="homeSubOpen = ! homeSubOpen"
                                :aria-expanded="homeSubOpen.toString()"
                                aria-controls="guest-mobile-home-section"
                            >
                                <span>Home</span>
                                <svg
                                    class="size-5 shrink-0 opacity-70 transition-transform"
                                    :class="homeSubOpen && 'rotate-180'"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                    aria-hidden="true"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </button>
                            <div
                                id="guest-mobile-home-section"
                                x-show="homeSubOpen"
                                x-transition
                                x-cloak
                                class="mb-1 space-y-0.5 border-l-2 border-emerald-200 py-1 pl-3 dark:border-emerald-800"
                            >
                                <a
                                    href="{{ url('/#about') }}"
                                    class="block rounded-lg py-2 pl-2 text-sm text-zinc-700 hover:text-emerald-700 dark:text-zinc-300 dark:hover:text-emerald-400"
                                    @click="mobileNavOpen = false; homeSubOpen = false"
                                >
                                    About
                                </a>
                                @if (public_reviews_enabled())
                                    <a
                                        href="{{ url('/#reviews') }}"
                                        class="block rounded-lg py-2 pl-2 text-sm text-zinc-700 hover:text-emerald-700 dark:text-zinc-300 dark:hover:text-emerald-400"
                                        @click="mobileNavOpen = false; homeSubOpen = false"
                                    >
                                        Reviews
                                    </a>
                                @endif
                                <a
                                    href="{{ url('/#pricing') }}"
                                    class="block rounded-lg py-2 pl-2 text-sm text-zinc-700 hover:text-emerald-700 dark:text-zinc-300 dark:hover:text-emerald-400"
                                    @click="mobileNavOpen = false; homeSubOpen = false"
                                >
                                    Pricing
                                </a>
                            </div>
                        </div>
                        <a
                            href="{{ route('book-now') }}"
                            wire:navigate
                            @click="mobileNavOpen = false"
                            @class([
                                'font-display mt-1 inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-yellow-400 to-amber-500 px-4 py-3.5 text-center text-sm font-bold uppercase tracking-wide text-amber-950 shadow-lg shadow-amber-900/25 transition dark:from-amber-500 dark:to-yellow-600 dark:text-amber-50',
                                request()->routeIs('book-now*')
                                    ? 'ring-2 ring-amber-400/90 ring-offset-2 ring-offset-white dark:ring-amber-300/80 dark:ring-offset-zinc-900'
                                    : '',
                            ])
                        >
                            Book now
                        </a>
                        <a
                            href="{{ route('contact') }}"
                            wire:navigate
                            @click="mobileNavOpen = false"
                            @class([
                                'rounded-lg px-3 py-3 transition-colors',
                                request()->routeIs('contact')
                                    ? 'font-semibold text-emerald-700 dark:text-emerald-400'
                                    : 'text-zinc-800 hover:text-emerald-700 dark:text-zinc-100 dark:hover:text-emerald-400',
                            ])
                        >
                            Contact
                        </a>
                        <a
                            href="{{ route('open-play.about') }}"
                            wire:navigate
                            @click="mobileNavOpen = false"
                            @class([
                                'inline-flex items-center gap-1 rounded-lg px-3 py-3 transition-colors',
                                request()->routeIs('open-play.about')
                                    ? 'font-semibold text-emerald-700 dark:text-emerald-400'
                                    : 'text-zinc-800 hover:text-emerald-700 dark:text-zinc-100 dark:hover:text-emerald-400',
                            ])
                        >
                            <x-gameq-mark compact />
                        </a>
                        @auth
                            <a
                                href="{{ $staffAppUrl ?? $memberHomeUrl }}"
                                wire:navigate
                                @click="mobileNavOpen = false"
                                @class([
                                    'rounded-lg px-3 py-3 transition-colors',
                                    $guestNavAppActive
                                        ? 'font-semibold text-emerald-700 dark:text-emerald-400'
                                        : 'text-zinc-800 hover:text-emerald-700 dark:text-zinc-100 dark:hover:text-emerald-400',
                                ])
                            >
                                {{ $staffAppUrl !== null ? 'Go to app' : 'My Corner' }}
                            </a>
                            <form method="POST" action="{{ route('logout') }}" class="contents">
                                @csrf
                                <button
                                    type="submit"
                                    class="w-full rounded-lg px-3 py-3 text-left text-zinc-800 transition-colors hover:text-emerald-700 dark:text-zinc-100 dark:hover:text-emerald-400"
                                >
                                    Log out
                                </button>
                            </form>
                        @else
                            <a
                                href="{{ route('login') }}"
                                wire:navigate
                                @click="mobileNavOpen = false"
                                @class([
                                    'rounded-lg px-3 py-3 transition-colors',
                                    request()->routeIs('login')
                                        ? 'font-semibold text-emerald-700 dark:text-emerald-400'
                                        : 'text-zinc-800 hover:text-emerald-700 dark:text-zinc-100 dark:hover:text-emerald-400',
                                ])
                            >
                                Log in
                            </a>
                            @if (public_registration_enabled())
                                <a
                                    href="{{ route('register') }}"
                                    wire:navigate
                                    @click="mobileNavOpen = false"
                                    class="rounded-lg border border-emerald-600/30 bg-emerald-50/80 px-3 py-3 text-center font-semibold text-emerald-800 transition-colors hover:bg-emerald-100 dark:border-emerald-500/40 dark:bg-emerald-950/40 dark:text-emerald-200 dark:hover:bg-emerald-950/70"
                                >
                                    Register
                                </a>
                            @endif
                        @endauth
                    </nav>
                </div>
                @endunless
            </header>

            <main class="flex-1">
                {{ $slot }}
            </main>

            <footer class="border-t border-zinc-200 bg-zinc-100/80 py-10 dark:border-zinc-800 dark:bg-zinc-900/40">
                <div
                    class="mx-auto flex max-w-7xl flex-col items-center gap-6 px-4 text-center sm:px-6 lg:px-8"
                >
                    @unless ($hideGuestPrimaryNav)
                    <nav class="flex flex-wrap justify-center gap-x-6 gap-y-2 text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400" aria-label="Footer">
                        <a href="{{ route('home') }}" wire:navigate class="hover:text-emerald-600 dark:hover:text-emerald-400">Home</a>
                        <a href="{{ route('book-now') }}" wire:navigate class="font-semibold text-amber-700 hover:text-amber-600 dark:text-amber-400 dark:hover:text-amber-300">Book now</a>
                        <a href="{{ url('/#about') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400">About</a>
                        @if (public_reviews_enabled())
                            <a href="{{ url('/#reviews') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400">Reviews</a>
                        @endif
                        <a href="{{ url('/#pricing') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400">Pricing</a>
                        <a href="{{ route('contact') }}" wire:navigate class="hover:text-emerald-600 dark:hover:text-emerald-400">Contact</a>
                        <a href="{{ route('terms') }}" wire:navigate class="hover:text-emerald-600 dark:hover:text-emerald-400">Terms</a>
                        <a href="{{ route('privacy-policy') }}" wire:navigate class="hover:text-emerald-600 dark:hover:text-emerald-400">Privacy</a>
                        <a href="{{ route('refund-policy') }}" wire:navigate class="hover:text-emerald-600 dark:hover:text-emerald-400">Refunds</a>
                        <a href="{{ route('booking-cancellation-policy') }}" wire:navigate class="hover:text-emerald-600 dark:hover:text-emerald-400">Booking</a>
                        <a
                            href="{{ route('open-play.about') }}"
                            wire:navigate
                            class="inline-flex items-center gap-1 normal-case hover:text-emerald-600 dark:hover:text-emerald-400"
                        >
                            <x-gameq-mark compact />
                        </a>
                    </nav>
                    @endunless
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        &copy; {{ date('Y') }} {{ config('app.name') }}
                    </p>
                </div>
            </footer>
        </div>

        @livewireScripts
    </body>
</html>
