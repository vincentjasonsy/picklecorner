<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        @include('partials.theme-init')

        <title>{{ $title ?? config('app.name') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body
        class="min-h-screen bg-zinc-50 text-zinc-900 antialiased transition-colors duration-200 dark:bg-zinc-950 dark:text-zinc-100"
    >
        @include('partials.flash-messages')
        @include('partials.impersonation-banner')

        <div class="flex min-h-screen flex-col">
            <header
                class="sticky top-0 z-20 border-b border-zinc-200/80 bg-white/90 backdrop-blur-md dark:border-zinc-800/80 dark:bg-zinc-900/90"
            >
                <div
                    class="mx-auto flex h-14 max-w-5xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8"
                >
                    <a
                        href="{{ route('home') }}"
                        wire:navigate
                        class="text-sm font-semibold tracking-tight text-zinc-900 dark:text-zinc-100"
                    >
                        {{ config('app.name') }}
                    </a>
                    <div class="flex flex-wrap items-center justify-end gap-x-3 gap-y-2 sm:gap-x-5">
                        <x-theme-toggle />
                        <nav
                            class="flex flex-wrap items-center justify-end gap-x-6 gap-y-2 text-sm font-medium"
                            aria-label="Primary"
                        >
                        <a
                            href="{{ route('home') }}"
                            wire:navigate
                            @class([
                                'transition-colors',
                                request()->routeIs('home')
                                    ? 'text-emerald-600 dark:text-emerald-400'
                                    : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100',
                            ])
                        >
                            Home
                        </a>
                        <a
                            href="{{ route('about') }}"
                            wire:navigate
                            @class([
                                'transition-colors',
                                request()->routeIs('about')
                                    ? 'text-emerald-600 dark:text-emerald-400'
                                    : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100',
                            ])
                        >
                            About
                        </a>
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
                            <a
                                href="{{ $staffAppUrl ?? $memberHomeUrl }}"
                                wire:navigate
                                @class([
                                    'transition-colors',
                                    $guestNavAppActive
                                        ? 'text-emerald-600 dark:text-emerald-400'
                                        : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100',
                                ])
                            >
                                {{ $staffAppUrl !== null ? 'Go to app' : 'My court' }}
                            </a>
                            <form method="POST" action="{{ route('logout') }}" class="inline">
                                @csrf
                                <button
                                    type="submit"
                                    class="text-zinc-600 transition-colors hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100"
                                >
                                    Log out
                                </button>
                            </form>
                        @else
                            <a
                                href="{{ route('login') }}"
                                wire:navigate
                                @class([
                                    'transition-colors',
                                    request()->routeIs('login')
                                        ? 'text-emerald-600 dark:text-emerald-400'
                                        : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100',
                                ])
                            >
                                Log in
                            </a>
                            <a
                                href="{{ route('register') }}"
                                wire:navigate
                                class="rounded-lg bg-emerald-600 px-3 py-1.5 text-white transition-colors hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600"
                            >
                                Register
                            </a>
                        @endauth
                        </nav>
                    </div>
                </div>
            </header>

            <main class="flex-1">
                {{ $slot }}
            </main>

            <footer class="border-t border-zinc-200 py-8 dark:border-zinc-800">
                <div
                    class="mx-auto max-w-5xl px-4 text-center text-xs text-zinc-500 dark:text-zinc-400 sm:px-6 lg:px-8"
                >
                    &copy; {{ date('Y') }} {{ config('app.name') }}
                </div>
            </footer>
        </div>

        @livewireScripts
    </body>
</html>
