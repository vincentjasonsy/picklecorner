<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        @include('partials.theme-init')

        <title>{{ $title ?? 'Front desk' }} — {{ config('app.name') }}</title>

        @include('partials.favicon')

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link
            href="https://fonts.bunny.net/css?family=barlow:600,700|dm-sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap"
            rel="stylesheet"
        />
        <style>
            .font-display {
                font-family: 'Barlow', ui-sans-serif, system-ui, sans-serif;
            }
            .font-desk {
                font-family: 'DM Sans', ui-sans-serif, system-ui, sans-serif;
            }
        </style>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    @php
        $deskUser = auth()->user();
        $deskVenue = $deskUser?->deskCourtClient;
        $deskTz = config('app.timezone', 'UTC');
        $deskToday = $deskUser ? now()->timezone($deskTz)->isoFormat('dddd, MMMM D, YYYY') : '';
    @endphp
    <body
        class="font-desk min-h-screen bg-stone-100 text-stone-900 antialiased transition-colors duration-200 dark:bg-stone-950 dark:text-stone-100"
    >
        @include('partials.flash-messages')
        @include('partials.impersonation-banner')

        @if ($deskUser)
            <header
                class="border-b border-teal-900/40 bg-gradient-to-br from-stone-800 via-stone-800 to-teal-950 px-4 py-4 text-stone-100 shadow-lg shadow-stone-900/20 md:px-8 md:py-5"
            >
                <div class="mx-auto flex max-w-[1600px] flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p
                            class="font-display text-[10px] font-bold uppercase tracking-[0.22em] text-teal-300/95"
                        >
                            Front desk
                        </p>
                        @if ($deskVenue)
                            <h1 class="font-display mt-1 text-xl font-bold tracking-tight text-white md:text-2xl">
                                {{ $deskVenue->name }}
                            </h1>
                            <p class="mt-1 text-sm text-stone-400">
                                {{ $deskVenue->city ?? 'Venue counter' }}
                            </p>
                        @else
                            <p class="mt-1 text-lg font-semibold text-stone-200">No venue assigned</p>
                        @endif
                        <p class="mt-2 text-xs text-stone-500">
                            Signed in as <span class="font-medium text-stone-300">{{ $deskUser->name }}</span>
                        </p>
                    </div>
                    <div
                        class="overflow-hidden rounded-2xl border border-white/10 bg-white/5 px-5 py-3 text-right backdrop-blur-sm"
                    >
                        <p class="text-xs font-medium uppercase tracking-wider text-teal-200/80">Today</p>
                        <p class="mt-1 text-sm font-medium text-stone-200">{{ $deskToday }}</p>
                        <p
                            class="font-display mt-1 text-3xl font-bold tabular-nums tracking-tight text-white md:text-4xl"
                            id="desk-front-clock"
                            aria-live="polite"
                        >
                            —:—
                        </p>
                    </div>
                </div>
            </header>
        @endif

        <div
            class="mx-auto flex min-h-0 max-w-[1600px] flex-1 flex-col lg:min-h-[calc(100vh-9rem)] lg:flex-row"
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
                class="fixed inset-0 z-40 bg-stone-900/50 backdrop-blur-[1px] dark:bg-black/60 lg:hidden"
                x-cloak
                aria-hidden="true"
            ></div>

            <aside
                id="desk-sidebar"
                class="fixed inset-y-0 left-0 z-50 flex w-72 max-w-[min(100vw-1rem,20rem)] shrink-0 flex-col border-stone-200/80 bg-white/95 shadow-sm transition-transform duration-200 ease-out dark:border-stone-800 dark:bg-stone-900/95 lg:static lg:z-auto lg:max-w-none lg:translate-x-0 lg:border-r lg:shadow-none"
                :class="portalNavOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
            >
                <div class="border-b border-stone-200 px-4 py-4 dark:border-stone-800 lg:px-5">
                    <p class="font-display text-xs font-bold uppercase tracking-wider text-stone-500 dark:text-stone-400">
                        Desk
                    </p>
                    <p class="mt-1 text-[11px] font-medium text-stone-500 dark:text-stone-500">
                        Front counter navigation
                    </p>
                </div>
                <nav
                    class="flex flex-col gap-6 overflow-y-auto p-3 lg:p-4"
                    aria-label="Front desk"
                    @click="if ($event.target.closest('a')) portalNavOpen = false"
                >
                    <div>
                        <p
                            class="mb-2 px-1 font-display text-[11px] font-bold uppercase tracking-wider text-stone-400 dark:text-stone-500"
                        >
                            Shift &amp; floor
                        </p>
                        <div class="flex flex-col gap-1.5">
                            <a
                                href="{{ route('desk.home') }}"
                                wire:navigate
                                @class([
                                    'rounded-xl px-4 py-3 text-sm font-semibold transition-all',
                                    request()->routeIs('desk.home')
                                        ? 'bg-teal-600 text-white shadow-md shadow-teal-900/25 dark:bg-teal-600 dark:text-white'
                                        : 'text-stone-700 hover:bg-stone-100 dark:text-stone-300 dark:hover:bg-stone-800/80',
                                ])
                            >
                                <span class="block">Home</span>
                                <span
                                    @class([
                                        'mt-0.5 block text-xs font-normal',
                                        request()->routeIs('desk.home')
                                            ? 'text-teal-100'
                                            : 'text-stone-500 dark:text-stone-500',
                                    ])
                                >
                                    Shift overview
                                </span>
                            </a>
                            <a
                                href="{{ route('desk.courts-live') }}"
                                wire:navigate
                                @class([
                                    'rounded-xl px-4 py-3 text-sm font-semibold transition-all',
                                    request()->routeIs('desk.courts-live')
                                        ? 'bg-teal-600 text-white shadow-md shadow-teal-900/25 dark:bg-teal-600 dark:text-white'
                                        : 'text-stone-700 hover:bg-stone-100 dark:text-stone-300 dark:hover:bg-stone-800/80',
                                ])
                            >
                                <span class="block">Courts live</span>
                                <span
                                    @class([
                                        'mt-0.5 block text-xs font-normal',
                                        request()->routeIs('desk.courts-live')
                                            ? 'text-teal-100'
                                            : 'text-stone-500 dark:text-stone-500',
                                    ])
                                >
                                    Who is on each court
                                </span>
                            </a>
                        </div>
                    </div>

                    <div>
                        <p
                            class="mb-2 px-1 font-display text-[11px] font-bold uppercase tracking-wider text-stone-400 dark:text-stone-500"
                        >
                            Walk-ins &amp; calls
                        </p>
                        <div class="flex flex-col gap-1.5">
                            <a
                                href="{{ route('desk.booking-request') }}"
                                wire:navigate
                                @class([
                                    'rounded-xl px-4 py-3 text-sm font-semibold transition-all',
                                    request()->routeIs('desk.booking-request')
                                        ? 'bg-teal-600 text-white shadow-md shadow-teal-900/25 dark:bg-teal-600 dark:text-white'
                                        : 'text-stone-700 hover:bg-stone-100 dark:text-stone-300 dark:hover:bg-stone-800/80',
                                ])
                            >
                                <span class="block">New booking request</span>
                                <span
                                    @class([
                                        'mt-0.5 block text-xs font-normal',
                                        request()->routeIs('desk.booking-request')
                                            ? 'text-teal-100'
                                            : 'text-stone-500 dark:text-stone-500',
                                    ])
                                >
                                    Start a guest request
                                </span>
                            </a>
                            <a
                                href="{{ route('desk.my-requests') }}"
                                wire:navigate
                                @class([
                                    'rounded-xl px-4 py-3 text-sm font-semibold transition-all',
                                    request()->routeIs('desk.my-requests')
                                        ? 'bg-teal-600 text-white shadow-md shadow-teal-900/25 dark:bg-teal-600 dark:text-white'
                                        : 'text-stone-700 hover:bg-stone-100 dark:text-stone-300 dark:hover:bg-stone-800/80',
                                ])
                            >
                                <span class="block">My requests</span>
                                <span
                                    @class([
                                        'mt-0.5 block text-xs font-normal',
                                        request()->routeIs('desk.my-requests')
                                            ? 'text-teal-100'
                                            : 'text-stone-500 dark:text-stone-500',
                                    ])
                                >
                                    Status &amp; history
                                </span>
                            </a>
                        </div>
                    </div>

                    <div>
                        <p
                            class="mb-2 px-1 font-display text-[11px] font-bold uppercase tracking-wider text-stone-400 dark:text-stone-500"
                        >
                            Submitted bookings
                        </p>
                        <div class="flex flex-col gap-1.5">
                            <a
                                href="{{ route('desk.bookings.calendar') }}"
                                wire:navigate
                                @class([
                                    'rounded-xl px-4 py-3 text-sm font-semibold transition-all',
                                    request()->routeIs('desk.bookings.calendar', 'desk.bookings.show')
                                        ? 'bg-teal-600 text-white shadow-md shadow-teal-900/25 dark:bg-teal-600 dark:text-white'
                                        : 'text-stone-700 hover:bg-stone-100 dark:text-stone-300 dark:hover:bg-stone-800/80',
                                ])
                            >
                                <span class="block">Booking calendar</span>
                                <span
                                    @class([
                                        'mt-0.5 block text-xs font-normal',
                                        request()->routeIs('desk.bookings.calendar', 'desk.bookings.show')
                                            ? 'text-teal-100'
                                            : 'text-stone-500 dark:text-stone-500',
                                    ])
                                >
                                    Your submissions by day
                                </span>
                            </a>
                        </div>
                    </div>
                </nav>
                <div class="shrink-0 border-t border-stone-200 p-3 dark:border-stone-800 lg:p-4">
                    <form method="POST" action="{{ route('logout') }}" @submit="portalNavOpen = false">
                        @csrf
                        <button
                            type="submit"
                            class="w-full rounded-lg px-3 py-2 text-left text-sm font-semibold text-stone-600 transition-colors hover:bg-stone-100 dark:text-stone-400 dark:hover:bg-stone-800/80"
                        >
                            Log out
                        </button>
                    </form>
                </div>
            </aside>

            <div class="flex min-w-0 flex-1 flex-col bg-stone-50/80 dark:bg-stone-950/50 lg:min-h-0">
                <div
                    class="flex shrink-0 items-center justify-between gap-4 border-b border-stone-200/80 bg-white/80 px-4 py-3 backdrop-blur-sm dark:border-stone-800 dark:bg-stone-900/50 lg:px-8"
                >
                    <div class="flex min-w-0 flex-1 items-center gap-2">
                        <x-layout.portal-menu-button
                            controls="desk-sidebar"
                            class="border-stone-200 text-stone-700 hover:bg-stone-100 dark:border-stone-600 dark:text-stone-200 dark:hover:bg-stone-800/80"
                        />
                        <h2 class="font-display truncate text-lg font-bold text-stone-900 dark:text-white lg:text-xl">
                            {{ $title ?? 'Front desk' }}
                        </h2>
                    </div>
                    <x-theme-toggle />
                </div>
                <main class="flex-1 px-4 py-6 md:px-8 md:py-8">
                    {{ $slot }}
                </main>
            </div>
        </div>

        @if ($deskUser)
            <script>
                (function () {
                    var el = document.getElementById('desk-front-clock');
                    if (!el) return;
                    var tz = @json($deskTz);
                    function tick() {
                        try {
                            el.textContent = new Intl.DateTimeFormat(undefined, {
                                hour: 'numeric',
                                minute: '2-digit',
                                second: '2-digit',
                                hour12: true,
                                timeZone: tz,
                            }).format(new Date());
                        } catch (e) {
                            el.textContent = new Date().toLocaleTimeString();
                        }
                    }
                    tick();
                    setInterval(tick, 1000);
                })();
            </script>
        @endif

        @livewireScripts
    </body>
</html>
