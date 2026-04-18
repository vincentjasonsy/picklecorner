<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @include('partials.theme-init')

        <title>{{ $title ?? 'My Corner' }} — {{ config('app.name') }}</title>

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
                    radial-gradient(ellipse 100% 70% at 10% 0%, rgba(52, 211, 153, 0.12), transparent 50%),
                    radial-gradient(ellipse 80% 50% at 90% 100%, rgba(45, 212, 191, 0.1), transparent 50%),
                    repeating-linear-gradient(
                        118deg,
                        transparent,
                        transparent 56px,
                        rgba(16, 185, 129, 0.02) 56px,
                        rgba(16, 185, 129, 0.02) 57px
                    );
            }

            .dark .member-court-bg {
                background-color: #09090b;
                background-image:
                    radial-gradient(ellipse 100% 70% at 10% 0%, rgba(52, 211, 153, 0.08), transparent 50%),
                    radial-gradient(ellipse 80% 50% at 90% 100%, rgba(45, 212, 191, 0.06), transparent 50%),
                    repeating-linear-gradient(
                        118deg,
                        transparent,
                        transparent 56px,
                        rgba(16, 185, 129, 0.025) 56px,
                        rgba(16, 185, 129, 0.025) 57px
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
                                class="inline-flex rounded-full bg-violet-100/90 px-3 py-1 text-xs font-semibold text-violet-900 dark:bg-violet-950/70 dark:text-violet-200"
                            >
                                Coach mode
                            </span>
                        @else
                            <span
                                class="inline-flex rounded-full bg-emerald-100/90 px-3 py-1 text-xs font-semibold text-emerald-900 dark:bg-emerald-950/70 dark:text-emerald-200"
                            >
                                Playing
                            </span>
                        @endif
                    </div>

                    <nav
                        class="flex min-h-0 flex-1 flex-col gap-5 overflow-y-auto p-3 text-sm font-medium"
                        aria-label="{{ auth()->user()->isCoach() ? 'Coach' : 'Player' }} account"
                        @click="if ($event.target.closest('a')) portalNavOpen = false"
                    >
                        @unless (auth()->user()->isCoach())
                            <div>
                                <p class="mb-1.5 px-3 text-xs font-semibold text-zinc-500 dark:text-zinc-400">Play</p>
                                <div class="flex flex-col gap-0.5">
                                    <a
                                        href="{{ route('account.book') }}"
                                        wire:navigate
                                        @class([
                                            'rounded-xl px-3 py-2 transition-colors',
                                            request()->routeIs('account.book')
                                                ? 'bg-emerald-50 font-semibold text-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-200'
                                                : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50',
                                        ])
                                    >
                                        Book Now
                                    </a>
                                    <a
                                        href="{{ route('account.dashboard') }}"
                                        wire:navigate
                                        @class([
                                            'rounded-xl px-3 py-2 transition-colors',
                                            request()->routeIs('account.dashboard')
                                                ? 'bg-emerald-50 font-semibold text-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-200'
                                                : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50',
                                        ])
                                    >
                                        Home
                                    </a>
                                    <a
                                        href="{{ route('account.bookings') }}"
                                        wire:navigate
                                        @class([
                                            'rounded-xl px-3 py-2 transition-colors',
                                            request()->routeIs('account.bookings', 'account.bookings.show')
                                                ? 'bg-emerald-50 font-semibold text-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-200'
                                                : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50',
                                        ])
                                    >
                                        My bookings
                                    </a>
                                    <a
                                        href="{{ route('account.court-open-plays.index') }}"
                                        wire:navigate
                                        @class([
                                            'rounded-xl px-3 py-2 transition-colors',
                                            request()->routeIs('account.court-open-plays.*')
                                                ? 'bg-emerald-50 font-semibold text-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-200'
                                                : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50',
                                        ])
                                    >
                                        Open play
                                    </a>
                                </div>
                            </div>
                        @endunless

                        @if (auth()->user()->isCoach())
                            <div>
                                <p class="mb-1.5 px-3 text-xs font-semibold text-zinc-500 dark:text-zinc-400">Overview</p>
                                <div class="flex flex-col gap-0.5">
                                    <a
                                        href="{{ route('account.coach.dashboard') }}"
                                        wire:navigate
                                        @class([
                                            'rounded-xl px-3 py-2 transition-colors',
                                            request()->routeIs('account.coach.dashboard')
                                                ? 'bg-violet-50 font-semibold text-violet-950 dark:bg-violet-950/50 dark:text-violet-200'
                                                : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50',
                                        ])
                                    >
                                        Home
                                    </a>
                                </div>
                            </div>

                            <div>
                                <p class="mb-1.5 px-3 text-xs font-semibold text-zinc-500 dark:text-zinc-400">Where you coach</p>
                                <div class="flex flex-col gap-0.5">
                                    <a
                                        href="{{ route('account.coach.courts') }}"
                                        wire:navigate
                                        @class([
                                            'rounded-xl px-3 py-2 transition-colors',
                                            request()->routeIs('account.coach.courts')
                                                ? 'bg-violet-50 font-semibold text-violet-950 dark:bg-violet-950/50 dark:text-violet-200'
                                                : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50',
                                        ])
                                    >
                                        Venues
                                    </a>
                                    <a
                                        href="{{ route('account.coach.availability') }}"
                                        wire:navigate
                                        @class([
                                            'rounded-xl px-3 py-2 transition-colors',
                                            request()->routeIs('account.coach.availability')
                                                ? 'bg-violet-50 font-semibold text-violet-950 dark:bg-violet-950/50 dark:text-violet-200'
                                                : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50',
                                        ])
                                    >
                                        Hours &amp; rate
                                    </a>
                                </div>
                            </div>

                            <div>
                                <p class="mb-1.5 px-3 text-xs font-semibold text-zinc-500 dark:text-zinc-400">Sessions</p>
                                <div class="flex flex-col gap-0.5">
                                    <a
                                        href="{{ route('account.coach.bookings.calendar') }}"
                                        wire:navigate
                                        @class([
                                            'rounded-xl px-3 py-2 transition-colors',
                                            request()->routeIs('account.coach.bookings.calendar', 'account.coach.bookings.show')
                                                ? 'bg-violet-50 font-semibold text-violet-950 dark:bg-violet-950/50 dark:text-violet-200'
                                                : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50',
                                        ])
                                    >
                                        Calendar
                                    </a>
                                    <a
                                        href="{{ route('account.coach.gift-cards.index') }}"
                                        wire:navigate
                                        @class([
                                            'rounded-xl px-3 py-2 transition-colors',
                                            request()->routeIs('account.coach.gift-cards.*')
                                                ? 'bg-violet-50 font-semibold text-violet-950 dark:bg-violet-950/50 dark:text-violet-200'
                                                : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50',
                                        ])
                                    >
                                        Gift cards
                                    </a>
                                </div>
                            </div>

                            <div>
                                <p class="mb-1.5 px-3 text-xs font-semibold text-zinc-500 dark:text-zinc-400">Your page</p>
                                <div class="flex flex-col gap-0.5">
                                    <a
                                        href="{{ route('account.coach.profile') }}"
                                        wire:navigate
                                        @class([
                                            'rounded-xl px-3 py-2 transition-colors',
                                            request()->routeIs('account.coach.profile')
                                                ? 'bg-violet-50 font-semibold text-violet-950 dark:bg-violet-950/50 dark:text-violet-200'
                                                : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50',
                                        ])
                                    >
                                        Public profile
                                    </a>
                                </div>
                            </div>
                        @endif

                        <div>
                            <p class="mb-1.5 px-3 text-xs font-semibold text-zinc-500 dark:text-zinc-400">Fun extra</p>
                            <div class="flex flex-col gap-0.5">
                                <a
                                    href="{{ route('account.open-play') }}"
                                    wire:navigate
                                    @class([
                                        'inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm transition-colors',
                                        request()->routeIs('account.open-play', 'account.open-play.legacy', 'account.open-play.player')
                                            ? 'bg-emerald-50 font-semibold text-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-200'
                                            : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50',
                                    ])
                                >
                                    <x-gameq-mark compact />
                                </a>
                            </div>
                        </div>

                        <div>
                            <p class="mb-1.5 px-3 text-xs font-semibold text-zinc-500 dark:text-zinc-400">You</p>
                            <div class="flex flex-col gap-0.5">
                                <a
                                    href="{{ route('account.settings') }}"
                                    wire:navigate
                                    @class([
                                        'rounded-xl px-3 py-2 transition-colors',
                                        request()->routeIs('account.settings')
                                            ? 'bg-emerald-50 font-semibold text-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-200'
                                            : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50',
                                    ])
                                >
                                    Profile &amp; settings
                                </a>
                                <a
                                    href="{{ route('home') }}"
                                    wire:navigate
                                    class="rounded-xl px-3 py-2 text-zinc-600 transition-colors hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50"
                                >
                                    Main website
                                </a>
                            </div>
                        </div>

                        <div class="rounded-xl border border-dashed border-zinc-200/90 bg-white/60 px-3 py-3 text-xs leading-relaxed text-zinc-500 dark:border-zinc-700 dark:bg-zinc-950/40 dark:text-zinc-400">
                            <p class="font-semibold text-zinc-600 dark:text-zinc-300">Quick map</p>
                            @unless (auth()->user()->isCoach())
                                <ul class="mt-2 list-inside list-disc space-y-1">
                                    <li>
                                        <strong class="font-medium text-zinc-600 dark:text-zinc-300">Book Now</strong>
                                        — book a slot.
                                    </li>
                                    <li>
                                        <strong class="font-medium text-zinc-600 dark:text-zinc-300">Home</strong>
                                        — what’s next.
                                    </li>
                                    <li>
                                        <strong class="font-medium text-zinc-600 dark:text-zinc-300">My bookings</strong>
                                        — history &amp; refs.
                                    </li>
                                </ul>
                            @else
                                <ul class="mt-2 list-inside list-disc space-y-1">
                                    <li>
                                        <strong class="font-medium text-zinc-600 dark:text-zinc-300">Calendar</strong>
                                        — your sessions.
                                    </li>
                                    <li>
                                        <strong class="font-medium text-zinc-600 dark:text-zinc-300">Venues</strong>
                                        — where you’re listed.
                                    </li>
                                    <li>
                                        <strong class="font-medium text-zinc-600 dark:text-zinc-300">Public profile</strong>
                                        — what players see.
                                    </li>
                                </ul>
                            @endunless
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
                        class="flex h-14 shrink-0 items-center justify-between gap-4 border-b border-zinc-200/80 bg-white/80 px-4 backdrop-blur-md dark:border-zinc-800 dark:bg-zinc-900/85 lg:px-6"
                    >
                        <div class="flex min-w-0 flex-1 items-center gap-2">
                            <x-layout.portal-menu-button
                                controls="member-sidebar"
                                class="border-zinc-200 text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800"
                            />
                            <h1 class="truncate font-display text-base font-bold text-zinc-900 dark:text-white lg:text-lg">
                                {{ $title ?? 'My Corner' }}
                            </h1>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            @unless (auth()->user()->isCoach())
                                <a
                                    href="{{ route('account.book') }}"
                                    wire:navigate
                                    @class([
                                        'inline-flex shrink-0 items-center rounded-lg border px-3 py-1.5 text-xs font-semibold transition-colors',
                                        request()->routeIs('account.book')
                                            ? 'border-emerald-600 bg-emerald-100 text-emerald-900 dark:border-emerald-400/70 dark:bg-emerald-950/55 dark:text-emerald-100'
                                            : 'border-emerald-500/55 bg-emerald-50 text-emerald-900 hover:border-emerald-600 hover:bg-emerald-100 dark:border-emerald-500/35 dark:bg-emerald-950/30 dark:text-emerald-200 dark:hover:bg-emerald-950/50',
                                    ])
                                >
                                    Book Now
                                </a>
                            @endunless
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
                        class="shrink-0 border-t border-zinc-200/80 bg-white/90 py-3 dark:border-zinc-800 dark:bg-zinc-900/90"
                    >
                        <p class="px-4 text-center text-xs text-zinc-500 dark:text-zinc-500 md:px-6 md:text-left">
                            Made for good rallies and cold drinks afterward.
                        </p>
                    </footer>
                </div>
            </div>
        </div>

        @auth
            @if (! auth()->user()->usesStaffAppNav())
                @livewire(\App\Livewire\Member\MemberBookingNudge::class)
            @endif
        @endauth

        @livewireScripts
    </body>
</html>
