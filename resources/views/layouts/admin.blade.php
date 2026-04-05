<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        @include('partials.theme-init')

        <title>{{ $title ?? 'Admin' }} — {{ config('app.name') }}</title>

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

        <div class="flex min-h-screen flex-col md:flex-row">
            <aside
                class="shrink-0 border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900 md:w-64 md:border-r"
            >
                <div
                    class="flex h-14 items-center border-b border-zinc-200 px-4 dark:border-zinc-800 md:px-5"
                >
                    <span class="font-display text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                        Super admin
                    </span>
                </div>
                <nav class="flex flex-col gap-6 p-3 text-sm font-medium" aria-label="Admin">
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

                    <div>
                        <p
                            class="mb-2 px-3 font-display text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500"
                        >
                            Directory
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
                                    request()->routeIs('admin.court-clients.index', 'admin.court-clients.edit')
                                        ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200'
                                        : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50',
                                ])
                            >
                                Court clients
                            </a>
                        </div>
                    </div>

                    <div>
                        <p
                            class="mb-2 px-3 font-display text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500"
                        >
                            Bookings
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
                                    request()->routeIs('admin.bookings.*')
                                        ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200'
                                        : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50',
                                ])
                            >
                                Booking history
                            </a>
                        </div>
                    </div>

                    <div>
                        <p
                            class="mb-2 px-3 font-display text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500"
                        >
                            Revenue & reports
                        </p>
                        <div class="flex flex-col gap-1">
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
                            System
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
                        </div>
                    </div>
                </nav>
                <div class="shrink-0 border-t border-zinc-200 p-3 dark:border-zinc-800">
                    <form method="POST" action="{{ route('logout') }}">
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

            <div class="flex min-w-0 flex-1 flex-col">
                <header
                    class="flex h-14 shrink-0 items-center justify-between gap-4 border-b border-zinc-200 bg-white px-4 dark:border-zinc-800 dark:bg-zinc-900 md:px-6"
                >
                    <h1 class="truncate text-base font-semibold md:text-lg">
                        {{ $title ?? 'Admin' }}
                    </h1>
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
