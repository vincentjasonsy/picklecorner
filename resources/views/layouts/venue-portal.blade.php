<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        @include('partials.theme-init')

        <title>{{ $title ?? 'Venue' }} — {{ config('app.name') }}</title>

        @include('partials.favicon')

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link
            href="https://fonts.bunny.net/css?family=barlow:600,700|instrument-sans:400,500,600"
            rel="stylesheet"
        />
        <style>
            .font-display {
                font-family: 'Barlow', ui-sans-serif, system-ui, sans-serif;
            }
        </style>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body
        class="min-h-screen bg-zinc-100 text-zinc-900 antialiased transition-colors duration-200 dark:bg-zinc-950 dark:text-zinc-100"
    >
        @include('partials.flash-messages')
        @include('partials.impersonation-banner')

        <div
            class="flex min-h-screen flex-col lg:flex-row"
            x-data="{ portalNavOpen: false }"
            @keydown.escape.window="portalNavOpen = false"
        >
            <div
                x-show="portalNavOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="portalNavOpen = false"
                class="fixed inset-0 z-40 bg-zinc-900/50 backdrop-blur-[1px] dark:bg-black/60 lg:hidden"
                x-cloak
                aria-hidden="true"
            ></div>

            <aside
                id="venue-sidebar"
                class="fixed inset-y-0 left-0 z-50 flex w-64 max-w-[min(100vw-1rem,20rem)] shrink-0 flex-col border-zinc-200 bg-white transition-transform duration-200 ease-out dark:border-zinc-800 dark:bg-zinc-900 lg:sticky lg:top-0 lg:z-auto lg:h-screen lg:max-w-none lg:translate-x-0 lg:border-r"
                :class="portalNavOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
            >
                <div
                    class="flex h-14 shrink-0 items-center border-b border-zinc-200 px-4 dark:border-zinc-800 lg:px-5"
                >
                    <span class="font-display text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                        Venue portal
                    </span>
                </div>
                @php
                    $premiumNav = $venueHasPremiumFeatures ?? false;
                @endphp
                <nav
                    class="flex min-h-0 flex-1 flex-col gap-6 overflow-y-auto p-3 text-sm font-semibold"
                    aria-label="Venue"
                    @click="if ($event.target.closest('a')) portalNavOpen = false"
                >
                    <div class="flex flex-col gap-1">
                        <a
                            href="{{ route('venue.home') }}"
                            wire:navigate
                            @class([
                                'rounded-lg px-3 py-2 transition-colors',
                                request()->routeIs('venue.home')
                                    ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200'
                                    : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50',
                            ])
                        >
                            Overview
                        </a>
                    </div>

                    <div>
                        <p
                            class="mb-2 px-3 font-display text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500"
                        >
                            Operations
                        </p>
                        <div class="flex flex-col gap-1">
                            <a
                                href="{{ route('venue.manual-booking') }}"
                                wire:navigate
                                @class([
                                    'rounded-lg px-3 py-2 transition-colors',
                                    request()->routeIs('venue.manual-booking')
                                        ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200'
                                        : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50',
                                ])
                            >
                                Manual booking
                            </a>
                            <a
                                href="{{ route('venue.bookings.pending') }}"
                                wire:navigate
                                @class([
                                    'rounded-lg px-3 py-2 transition-colors',
                                    request()->routeIs('venue.bookings.pending')
                                        ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200'
                                        : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50',
                                ])
                            >
                                <span class="block">Manual booking requests</span>
                                <span
                                    @class([
                                        'mt-0.5 block text-[11px] font-normal normal-case',
                                        request()->routeIs('venue.bookings.pending')
                                            ? 'text-emerald-700/80 dark:text-emerald-300/80'
                                            : 'text-zinc-500 dark:text-zinc-400',
                                    ])
                                >
                                    Desk queue &amp; auto rules
                                </span>
                            </a>
                            <a
                                href="{{ route('venue.bookings.history') }}"
                                wire:navigate
                                @class([
                                    'rounded-lg px-3 py-2 transition-colors',
                                    request()->routeIs('venue.bookings.history', 'venue.bookings.show')
                                        ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200'
                                        : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50',
                                ])
                            >
                                Booking history
                            </a>
                            <a
                                href="{{ $premiumNav ? route('venue.crm.index') : route('venue.plan') }}"
                                wire:navigate
                                @class([
                                    'rounded-lg px-3 py-2 transition-colors',
                                    $premiumNav && request()->routeIs('venue.crm.*')
                                        ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200'
                                        : ($premiumNav
                                            ? 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50'
                                            : 'border border-dashed border-amber-300/90 bg-amber-50/70 text-amber-950 dark:border-amber-800/60 dark:bg-amber-950/30 dark:text-amber-100'),
                                ])
                            >
                                <span class="flex items-start gap-2">
                                    @unless ($premiumNav)
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke-width="1.5"
                                            stroke="currentColor"
                                            class="mt-0.5 h-4 w-4 shrink-0 opacity-80"
                                            aria-hidden="true"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"
                                            />
                                        </svg>
                                    @endunless
                                    <span class="min-w-0 flex-1">
                                        <span class="block">Customers</span>
                                        <span
                                            @class([
                                                'mt-0.5 block text-[11px] font-normal normal-case',
                                                $premiumNav && request()->routeIs('venue.crm.*')
                                                    ? 'text-emerald-700/80 dark:text-emerald-300/80'
                                                    : ($premiumNav ? 'text-zinc-500 dark:text-zinc-400' : 'text-amber-800/90 dark:text-amber-200/90'),
                                            ])
                                        >
                                            @if ($premiumNav)
                                                Search, profiles &amp; notes
                                            @else
                                                Locked — Premium only
                                            @endif
                                        </span>
                                    </span>
                                </span>
                            </a>
                        </div>
                    </div>

                    <div>
                        <p
                            class="mb-2 px-3 font-display text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500"
                        >
                            Venue setup
                        </p>
                        <div class="flex flex-col gap-1">
                            <a
                                href="{{ route('venue.settings') }}"
                                wire:navigate
                                @class([
                                    'rounded-lg px-3 py-2 transition-colors',
                                    request()->routeIs('venue.settings')
                                        ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200'
                                        : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50',
                                ])
                            >
                                Settings &amp; schedule
                            </a>
                            <a
                                href="{{ route('venue.courts') }}"
                                wire:navigate
                                @class([
                                    'rounded-lg px-3 py-2 transition-colors',
                                    request()->routeIs('venue.courts')
                                        ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200'
                                        : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50',
                                ])
                            >
                                Courts
                            </a>
                        </div>
                    </div>

                    <div>
                        <p
                            class="mb-2 px-3 font-display text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500"
                        >
                            Revenue &amp; reports
                        </p>
                        <div class="flex flex-col gap-1">
                            <a
                                href="{{ $premiumNav ? route('venue.gift-cards.index') : route('venue.plan') }}"
                                wire:navigate
                                @class([
                                    'rounded-lg px-3 py-2 transition-colors',
                                    $premiumNav && request()->routeIs('venue.gift-cards.*')
                                        ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200'
                                        : ($premiumNav
                                            ? 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50'
                                            : 'border border-dashed border-amber-300/90 bg-amber-50/70 text-amber-950 dark:border-amber-800/60 dark:bg-amber-950/30 dark:text-amber-100'),
                                ])
                            >
                                <span class="flex items-start gap-2">
                                    @unless ($premiumNav)
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke-width="1.5"
                                            stroke="currentColor"
                                            class="mt-0.5 h-4 w-4 shrink-0 opacity-80"
                                            aria-hidden="true"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"
                                            />
                                        </svg>
                                    @endunless
                                    <span class="min-w-0 flex-1">
                                        <span class="block">Gift cards</span>
                                        @unless ($premiumNav)
                                            <span
                                                class="mt-0.5 block text-[11px] font-normal normal-case text-amber-800/90 dark:text-amber-200/90"
                                            >
                                                Locked — Premium only
                                            </span>
                                        @endunless
                                    </span>
                                </span>
                            </a>
                            <a
                                href="{{ route('venue.reports') }}"
                                wire:navigate
                                @class([
                                    'rounded-lg px-3 py-2 transition-colors',
                                    request()->routeIs('venue.reports')
                                        ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200'
                                        : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50',
                                ])
                            >
                                Reports
                            </a>
                        </div>
                    </div>
                </nav>
                <div class="shrink-0 border-t border-zinc-200 p-3 dark:border-zinc-800">
                    <form method="POST" action="{{ route('logout') }}" @submit="portalNavOpen = false">
                        @csrf
                        <button
                            type="submit"
                            class="w-full rounded-lg px-3 py-2 text-left text-sm font-semibold text-zinc-600 transition-colors hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50"
                        >
                            Log out
                        </button>
                    </form>
                </div>
            </aside>

            <div class="flex min-w-0 flex-1 flex-col lg:min-h-screen">
                <header
                    class="flex h-14 shrink-0 items-center justify-between gap-4 border-b border-zinc-200 bg-white px-4 dark:border-zinc-800 dark:bg-zinc-900 lg:px-6"
                >
                    <div class="flex min-w-0 flex-1 items-center gap-2">
                        <x-layout.portal-menu-button
                            controls="venue-sidebar"
                            class="border-zinc-200 text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800"
                        />
                        <h1 class="truncate text-base font-semibold lg:text-lg">
                            {{ $title ?? 'Venue' }}
                        </h1>
                    </div>
                    <x-theme-toggle />
                </header>
                <main class="flex-1 p-4 md:p-6">
                    {{ $slot }}
                </main>
            </div>
        </div>

        @livewireScripts
    </body>
</html>
