<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @include('partials.theme-init')

        <title>{{ $title ?? 'My corner' }} — {{ config('app.name') }}</title>

        @include('partials.favicon')

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

        @auth
            @if (auth()->user()->isDemoAccount() && ! auth()->user()->demoHasExpired())
                <div
                    class="border-b border-amber-200 bg-amber-50 px-4 py-2.5 text-center text-xs font-medium text-amber-950 dark:border-amber-900 dark:bg-amber-950/35 dark:text-amber-100"
                    role="status"
                >
                    Demo account — your data is removed after
                    {{ auth()->user()->demo_expires_at->timezone(config('app.timezone'))->format('M j, g:i a') }}
                    ({{ config('app.timezone') }}).
                </div>
            @endif
        @endauth

        <div class="flex min-h-screen flex-col">
            <div
                class="flex min-h-0 flex-1 flex-col lg:flex-row"
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
                    id="member-sidebar"
                    class="fixed inset-y-0 left-0 z-50 flex w-64 max-w-[min(100vw-1rem,20rem)] shrink-0 flex-col border-zinc-200 bg-white transition-transform duration-200 ease-out dark:border-zinc-800 dark:bg-zinc-900 lg:sticky lg:top-0 lg:z-auto lg:h-screen lg:max-w-none lg:translate-x-0 lg:border-b-0 lg:border-r"
                    :class="portalNavOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
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
                        @if (auth()->user()->isCoach())
                            <span
                                class="inline-flex rounded-full bg-violet-100 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-violet-900 dark:bg-violet-950/80 dark:text-violet-200"
                            >
                                Coach account
                            </span>
                        @else
                            <span
                                class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300"
                            >
                                Player zone
                            </span>
                        @endif
                    </div>

                    <nav
                        class="flex min-h-0 flex-1 flex-col gap-6 overflow-y-auto p-3 text-sm font-semibold"
                        aria-label="Member"
                        @click="if ($event.target.closest('a')) portalNavOpen = false"
                    >
                        @unless (auth()->user()->isCoach())
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
                                    <a
                                        href="{{ route('account.court-open-plays.index') }}"
                                        wire:navigate
                                        @class([
                                            'rounded-lg px-3 py-2 transition-colors',
                                            request()->routeIs('account.court-open-plays.*')
                                                ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200'
                                                : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50',
                                        ])
                                    >
                                        Court open play
                                    </a>
                                </div>
                            </div>
                        @endunless

                        @if (auth()->user()->isCoach())
                            <div>
                                <p
                                    class="mb-2 px-3 font-display text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500"
                                >
                                    Coaching
                                </p>
                                <div class="flex flex-col gap-1">
                                    <a
                                        href="{{ route('account.coach.dashboard') }}"
                                        wire:navigate
                                        @class([
                                            'rounded-lg px-3 py-2 transition-colors',
                                            request()->routeIs('account.coach.dashboard')
                                                ? 'bg-violet-50 text-violet-900 dark:bg-violet-950/50 dark:text-violet-200'
                                                : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50',
                                        ])
                                    >
                                        Coach home
                                    </a>
                                    <a
                                        href="{{ route('account.coach.courts') }}"
                                        wire:navigate
                                        @class([
                                            'rounded-lg px-3 py-2 transition-colors',
                                            request()->routeIs('account.coach.courts')
                                                ? 'bg-violet-50 text-violet-900 dark:bg-violet-950/50 dark:text-violet-200'
                                                : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50',
                                        ])
                                    >
                                        Venues you coach
                                    </a>
                                    <a
                                        href="{{ route('account.coach.availability') }}"
                                        wire:navigate
                                        @class([
                                            'rounded-lg px-3 py-2 transition-colors',
                                            request()->routeIs('account.coach.availability')
                                                ? 'bg-violet-50 text-violet-900 dark:bg-violet-950/50 dark:text-violet-200'
                                                : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50',
                                        ])
                                    >
                                        Schedule &amp; rate
                                    </a>
                                    <a
                                        href="{{ route('account.coach.bookings.calendar') }}"
                                        wire:navigate
                                        @class([
                                            'rounded-lg px-3 py-2 transition-colors',
                                            request()->routeIs('account.coach.bookings.calendar', 'account.coach.bookings.show')
                                                ? 'bg-violet-50 text-violet-900 dark:bg-violet-950/50 dark:text-violet-200'
                                                : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50',
                                        ])
                                    >
                                        Booking calendar
                                    </a>
                                    <a
                                        href="{{ route('account.coach.gift-cards.index') }}"
                                        wire:navigate
                                        @class([
                                            'rounded-lg px-3 py-2 transition-colors',
                                            request()->routeIs('account.coach.gift-cards.*')
                                                ? 'bg-violet-50 text-violet-900 dark:bg-violet-950/50 dark:text-violet-200'
                                                : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50',
                                        ])
                                    >
                                        Gift cards
                                    </a>
                                    <a
                                        href="{{ route('account.coach.profile') }}"
                                        wire:navigate
                                        @class([
                                            'rounded-lg px-3 py-2 transition-colors',
                                            request()->routeIs('account.coach.profile')
                                                ? 'bg-violet-50 text-violet-900 dark:bg-violet-950/50 dark:text-violet-200'
                                                : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50',
                                        ])
                                    >
                                        Coach profile
                                    </a>
                                </div>
                            </div>
                        @endif

                        <div>
                            <p
                                class="mb-2 px-3 font-display text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500"
                            >
                                Tools
                            </p>
                            <div class="flex flex-col gap-1">
                                <a
                                    href="{{ route('account.open-play') }}"
                                    wire:navigate
                                    @class([
                                        'inline-flex items-center gap-1.5 rounded-lg px-3 py-2 transition-colors',
                                        request()->routeIs('account.open-play', 'account.open-play.legacy')
                                            ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200'
                                            : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50',
                                    ])
                                >
                                    <x-gameq-mark compact />
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
                        class="flex h-14 shrink-0 items-center justify-between gap-4 border-b border-zinc-200 bg-white/90 px-4 backdrop-blur-sm dark:border-zinc-800 dark:bg-zinc-900/90 lg:px-6"
                    >
                        <div class="flex min-w-0 flex-1 items-center gap-2">
                            <x-layout.portal-menu-button
                                controls="member-sidebar"
                                class="border-zinc-200 text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800"
                            />
                            <h1 class="truncate font-display text-base font-bold text-zinc-900 dark:text-white lg:text-lg">
                                {{ $title ?? 'My corner' }}
                            </h1>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <livewire:notification-bell />
                            <x-theme-toggle />
                        </div>
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
