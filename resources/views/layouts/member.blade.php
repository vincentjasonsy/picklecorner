<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        @include('partials.theme-init')

        <title>{{ $title ?? 'My court' }} — {{ config('app.name') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link
            href="https://fonts.bunny.net/css?family=barlow:600,700,800|instrument-sans:400,500,600,700"
            rel="stylesheet"
        />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
        <style>
            .font-display {
                font-family: 'Barlow', ui-sans-serif, system-ui, sans-serif;
            }

            .member-court-bg {
                background-color: #fafaf9;
                background-image:
                    radial-gradient(ellipse 120% 80% at 0% -20%, rgba(16, 185, 129, 0.18), transparent 55%),
                    radial-gradient(ellipse 100% 60% at 100% 110%, rgba(20, 184, 166, 0.14), transparent 50%),
                    repeating-linear-gradient(
                        105deg,
                        transparent,
                        transparent 48px,
                        rgba(16, 185, 129, 0.035) 48px,
                        rgba(16, 185, 129, 0.035) 49px
                    );
            }

            .dark .member-court-bg {
                background-color: #09090b;
                background-image:
                    radial-gradient(ellipse 120% 80% at 0% -20%, rgba(16, 185, 129, 0.12), transparent 55%),
                    radial-gradient(ellipse 100% 60% at 100% 110%, rgba(20, 184, 166, 0.1), transparent 50%),
                    repeating-linear-gradient(
                        105deg,
                        transparent,
                        transparent 48px,
                        rgba(16, 185, 129, 0.04) 48px,
                        rgba(16, 185, 129, 0.04) 49px
                    );
            }
        </style>
    </head>
    <body
        class="min-h-screen bg-zinc-100 text-zinc-900 antialiased transition-colors duration-200 dark:bg-zinc-950 dark:text-zinc-100"
    >
        @include('partials.flash-messages')
        @include('partials.impersonation-banner')

        <div class="flex min-h-screen flex-col">
            <div class="flex min-h-0 flex-1 flex-col md:flex-row">
                <aside
                    class="flex shrink-0 flex-col border-b border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900 md:sticky md:top-0 md:h-screen md:w-64 md:border-b-0 md:border-r"
                    aria-label="Account navigation"
                >
                    <div
                        class="flex h-14 shrink-0 items-center border-b border-zinc-200 px-4 dark:border-zinc-800 md:px-5"
                    >
                        <a
                            href="{{ route('account.dashboard') }}"
                            wire:navigate
                            class="font-display text-base font-extrabold tracking-tight text-emerald-700 dark:text-emerald-400"
                        >
                            {{ config('app.name') }}
                        </a>
                    </div>
                    <div
                        class="shrink-0 border-b border-zinc-200 px-4 py-2 dark:border-zinc-800 md:px-5"
                    >
                        <span
                            class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300"
                        >
                            Player zone
                        </span>
                    </div>

                    <nav
                        class="flex min-h-0 flex-1 flex-col gap-6 overflow-y-auto p-3 text-sm font-semibold"
                        aria-label="Member"
                    >
                        <div>
                            <p
                                class="mb-2 px-3 font-display text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500"
                            >
                                Your court
                            </p>
                            <div class="flex flex-col gap-1">
                                <a
                                    href="{{ route('account.dashboard') }}"
                                    wire:navigate
                                    @class([
                                        'rounded-lg px-3 py-2 transition-colors',
                                        request()->routeIs('account.dashboard')
                                            ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200'
                                            : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50',
                                    ])
                                >
                                    Home court
                                </a>
                                <a
                                    href="{{ route('account.book') }}"
                                    wire:navigate
                                    @class([
                                        'rounded-lg px-3 py-2 transition-colors',
                                        request()->routeIs('account.book')
                                            ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200'
                                            : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50',
                                    ])
                                >
                                    Book now
                                </a>
                                <a
                                    href="{{ route('account.bookings') }}"
                                    wire:navigate
                                    @class([
                                        'rounded-lg px-3 py-2 transition-colors',
                                        request()->routeIs('account.bookings')
                                            ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200'
                                            : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50',
                                    ])
                                >
                                    My games
                                </a>
                            </div>
                        </div>

                        <div>
                            <p
                                class="mb-2 px-3 font-display text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500"
                            >
                                Account
                            </p>
                            <div class="flex flex-col gap-1">
                                <a
                                    href="{{ route('account.settings') }}"
                                    wire:navigate
                                    @class([
                                        'rounded-lg px-3 py-2 transition-colors',
                                        request()->routeIs('account.settings')
                                            ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200'
                                            : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50',
                                    ])
                                >
                                    Profile
                                </a>
                            </div>
                        </div>

                        <div>
                            <p
                                class="mb-2 px-3 font-display text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500"
                            >
                                Elsewhere
                            </p>
                            <div class="flex flex-col gap-1">
                                <a
                                    href="{{ route('home') }}"
                                    wire:navigate
                                    class="rounded-lg px-3 py-2 text-zinc-600 transition-colors hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50"
                                >
                                    Back to site
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
                        class="flex h-14 shrink-0 items-center justify-between gap-4 border-b border-zinc-200 bg-white/90 px-4 backdrop-blur-sm dark:border-zinc-800 dark:bg-zinc-900/90 md:px-6"
                    >
                        <h1 class="truncate font-display text-base font-bold text-zinc-900 dark:text-white md:text-lg">
                            {{ $title ?? 'My court' }}
                        </h1>
                        <x-theme-toggle />
                    </header>
                    <main class="member-court-bg flex-1 p-4 md:p-6">
                        <div class="mx-auto max-w-4xl lg:max-w-5xl">
                            {{ $slot }}
                        </div>
                    </main>
                    <footer
                        class="shrink-0 border-t border-zinc-200 bg-white py-4 dark:border-zinc-800 dark:bg-zinc-900"
                    >
                        <p class="px-4 text-center text-xs font-medium text-zinc-500 dark:text-zinc-500 md:px-6 md:text-left">
                            Stay loose, have fun — see you on the court.
                        </p>
                    </footer>
                </div>
            </div>
        </div>

        @livewireScripts
    </body>
</html>
