<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        @include('partials.theme-init')

        <title>{{ $title ?? 'Admin' }} — {{ config('app.name') }}</title>

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
                id="admin-sidebar"
                class="fixed inset-y-0 left-0 z-50 flex w-64 max-w-[min(100vw-1rem,20rem)] shrink-0 flex-col border-zinc-200 bg-white transition-transform duration-200 ease-out dark:border-zinc-800 dark:bg-zinc-900 lg:static lg:z-auto lg:max-w-none lg:translate-x-0 lg:border-r"
                :class="portalNavOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
            >
                <div
                    class="flex h-14 items-center border-b border-zinc-200 px-4 dark:border-zinc-800 lg:px-5"
                >
                    <span class="font-display text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                        Super admin
                    </span>
                </div>
                <nav
                    class="flex flex-col gap-6 overflow-y-auto p-3 text-sm font-medium"
                    aria-label="Super admin"
                    @click="if ($event.target.closest('a')) portalNavOpen = false"
                >
                    <div>
                        <p
                            class="mb-2 px-3 font-display text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500"
                        >
                            Dashboard
                        </p>
                        <div class="flex flex-col gap-1">
                            <a
                                href="{{ route('admin.dashboard') }}"
                                wire:navigate
                                @class([
                                    'rounded-lg px-3 py-2 transition-colors',
                                    request()->routeIs('admin.dashboard')
                                        ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200'
                                        : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50',
                                ])
                            >
                                Overview
                            </a>
                        </div>
                    </div>

                    <div>
                        <p
                            class="mb-2 px-3 font-display text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500"
                        >
                            Accounts &amp; venues
                        </p>
                        <div class="flex flex-col gap-1">
                            <a
                                href="{{ route('admin.users.index') }}"
                                wire:navigate
                                @class([
                                    'rounded-lg px-3 py-2 transition-colors',
                                    request()->routeIs('admin.users.*')
                                        ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200'
                                        : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50',
                                ])
                            >
                                Users
                            </a>
                            <a
                                href="{{ route('admin.court-clients.index') }}"
                                wire:navigate
                                @class([
                                    'rounded-lg px-3 py-2 transition-colors',
                                    request()->routeIs('admin.court-clients.*')
                                        ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200'
                                        : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50',
                                ])
                            >
                                Court clients
                            </a>
                            <a
                                href="{{ route('admin.venue-quick-setup') }}"
                                wire:navigate
                                @class([
                                    'rounded-lg px-3 py-2 transition-colors',
                                    request()->routeIs('admin.venue-quick-setup')
                                        ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200'
                                        : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50',
                                ])
                            >
                                Quick venue setup
                            </a>
                            <a
                                href="{{ route('admin.featured-venues') }}"
                                wire:navigate
                                @class([
                                    'rounded-lg px-3 py-2 transition-colors',
                                    request()->routeIs('admin.featured-venues')
                                        ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200'
                                        : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50',
                                ])
                            >
                                Featured venues
                            </a>
                        </div>
                    </div>

                    <div>
                        <p
                            class="mb-2 px-3 font-display text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500"
                        >
                            Booking operations
                        </p>
                        <div class="flex flex-col gap-1">
                            <a
                                href="{{ route('admin.court-change-requests') }}"
                                wire:navigate
                                @class([
                                    'rounded-lg px-3 py-2 transition-colors',
                                    request()->routeIs('admin.court-change-requests')
                                        ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200'
                                        : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50',
                                ])
                            >
                                Court requests
                            </a>
                            <a
                                href="{{ route('admin.manual-booking.hub') }}"
                                wire:navigate
                                @class([
                                    'rounded-lg px-3 py-2 transition-colors',
                                    request()->routeIs('admin.manual-booking.*') ||
                                    request()->routeIs('admin.court-clients.manual-booking')
                                        ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200'
                                        : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50',
                                ])
                            >
                                Manual booking
                            </a>
                            <a
                                href="{{ route('admin.bookings.index') }}"
                                wire:navigate
                                @class([
                                    'rounded-lg px-3 py-2 transition-colors',
                                    request()->routeIs('admin.bookings.index', 'admin.bookings.show')
                                        ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200'
                                        : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50',
                                ])
                            >
                                Booking history
                            </a>
                            <a
                                href="{{ route('admin.coach-bookings.index') }}"
                                wire:navigate
                                @class([
                                    'rounded-lg px-3 py-2 transition-colors',
                                    request()->routeIs('admin.coach-bookings.*')
                                        ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200'
                                        : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50',
                                ])
                            >
                                Coach bookings
                            </a>
                        </div>
                    </div>

                    <div>
                        <p
                            class="mb-2 px-3 font-display text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500"
                        >
                            Finance &amp; reports
                        </p>
                        <div class="flex flex-col gap-1">
                            <a
                                href="{{ route('admin.booking-rates') }}"
                                wire:navigate
                                @class([
                                    'rounded-lg px-3 py-2 transition-colors',
                                    request()->routeIs('admin.booking-rates')
                                        ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200'
                                        : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50',
                                ])
                            >
                                Booking rates
                            </a>
                            <a
                                href="{{ route('admin.gift-cards.index') }}"
                                wire:navigate
                                @class([
                                    'rounded-lg px-3 py-2 transition-colors',
                                    request()->routeIs('admin.gift-cards.*')
                                        ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200'
                                        : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50',
                                ])
                            >
                                Gift cards
                            </a>
                            <a
                                href="{{ route('admin.invoices.index') }}"
                                wire:navigate
                                @class([
                                    'rounded-lg px-3 py-2 transition-colors',
                                    request()->routeIs('admin.invoices.*')
                                        ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200'
                                        : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50',
                                ])
                            >
                                Client invoices
                            </a>
                            <a
                                href="{{ route('admin.reports') }}"
                                wire:navigate
                                @class([
                                    'rounded-lg px-3 py-2 transition-colors',
                                    request()->routeIs('admin.reports')
                                        ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200'
                                        : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50',
                                ])
                            >
                                Reports
                            </a>
                        </div>
                    </div>

                    <div>
                        <p
                            class="mb-2 px-3 font-display text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500"
                        >
                            Trust &amp; moderation
                        </p>
                        <div class="flex flex-col gap-1">
                            <a
                                href="{{ route('admin.gallery-approvals') }}"
                                wire:navigate
                                @class([
                                    'rounded-lg px-3 py-2 transition-colors',
                                    request()->routeIs('admin.gallery-approvals')
                                        ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200'
                                        : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50',
                                ])
                            >
                                Gallery approvals
                            </a>
                            <a
                                href="{{ route('admin.review-approvals') }}"
                                wire:navigate
                                @class([
                                    'rounded-lg px-3 py-2 transition-colors',
                                    request()->routeIs('admin.review-approvals')
                                        ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200'
                                        : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50',
                                ])
                            >
                                Review approvals
                            </a>
                        </div>
                    </div>

                    <div>
                        <p
                            class="mb-2 px-3 font-display text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500"
                        >
                            Platform
                        </p>
                        <div class="flex flex-col gap-1">
                            <a
                                href="{{ route('admin.activity.index') }}"
                                wire:navigate
                                @class([
                                    'rounded-lg px-3 py-2 transition-colors',
                                    request()->routeIs('admin.activity.*')
                                        ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200'
                                        : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50',
                                ])
                            >
                                Activity log
                            </a>
                            <a
                                href="{{ route('admin.internal-play-reminders') }}"
                                wire:navigate
                                @class([
                                    'rounded-lg px-3 py-2 transition-colors',
                                    request()->routeIs('admin.internal-play-reminders')
                                        ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200'
                                        : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50',
                                ])
                            >
                                Team play reminders
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
                            controls="admin-sidebar"
                            class="border-zinc-200 text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800"
                        />
                        <h1 class="truncate text-base font-semibold lg:text-lg">
                            {{ $title ?? 'Admin' }}
                        </h1>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <livewire:notification-bell />
                        <x-theme-toggle />
                    </div>
                </header>
                <main class="flex-1 p-4 md:p-6">
                    {{ $slot }}
                </main>
            </div>
        </div>

        @livewireScripts
    </body>
</html>
