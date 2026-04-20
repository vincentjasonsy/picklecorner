<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        @include('partials.theme-init')

        @include('partials.document-title')

        @include('partials.favicon')

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link
            href="https://fonts.bunny.net/css?family=barlow:600,700,800|instrument-sans:400,500,600"
            rel="stylesheet"
        />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
        <style>
            .font-display {
                font-family: 'Barlow', ui-sans-serif, system-ui, sans-serif;
            }
        </style>
    </head>
    <body
        class="min-h-screen bg-zinc-50 text-zinc-900 antialiased transition-colors duration-200 dark:bg-zinc-950 dark:text-zinc-100"
    >
        <div class="flex min-h-screen flex-col lg:flex-row">
            {{-- Brand / sport panel --}}
            <div
                class="relative min-h-44 overflow-hidden bg-gradient-to-br from-emerald-100 via-teal-50 to-emerald-200 lg:min-h-screen lg:w-[44%] lg:max-w-xl dark:from-emerald-950 dark:via-emerald-900 dark:to-teal-950"
            >
                <div
                    class="pointer-events-none absolute inset-0 opacity-[0.06] dark:hidden"
                    style="
                        background-image: linear-gradient(
                                90deg,
                                rgb(6 78 59 / 0.35) 1px,
                                transparent 1px
                            ),
                            linear-gradient(rgb(6 78 59 / 0.35) 1px, transparent 1px);
                        background-size: 40px 40px;
                    "
                    aria-hidden="true"
                ></div>
                <div
                    class="pointer-events-none absolute inset-0 hidden opacity-[0.12] dark:block"
                    style="
                        background-image: linear-gradient(
                                90deg,
                                rgb(255 255 255 / 0.35) 1px,
                                transparent 1px
                            ),
                            linear-gradient(rgb(255 255 255 / 0.35) 1px, transparent 1px);
                        background-size: 40px 40px;
                    "
                    aria-hidden="true"
                ></div>
                <div
                    class="absolute -right-16 -top-24 h-72 w-72 rounded-full bg-emerald-400/25 blur-3xl dark:bg-lime-400/15"
                    aria-hidden="true"
                ></div>
                <div
                    class="absolute -bottom-20 -left-10 h-64 w-64 rounded-full bg-teal-400/20 blur-3xl dark:bg-emerald-400/10"
                    aria-hidden="true"
                ></div>

                <div class="relative flex h-full flex-col justify-between px-8 py-8 lg:px-12 lg:py-12">
                    <div>
                        <a
                            href="{{ route('home') }}"
                            wire:navigate
                            class="inline-flex items-center gap-2 font-display text-xl font-extrabold tracking-tight text-emerald-950 dark:text-white"
                        >
                            <span
                                class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-900/10 ring-1 ring-emerald-900/15 dark:bg-white/10 dark:ring-white/20"
                                aria-hidden="true"
                            >
                                <svg
                                    class="size-5 text-emerald-700 dark:text-lime-300"
                                    viewBox="0 0 24 24"
                                    fill="currentColor"
                                >
                                    <circle cx="12" cy="12" r="3" />
                                    <path
                                        d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"
                                        opacity=".35"
                                    />
                                </svg>
                            </span>
                            {{ config('app.name') }}
                        </a>
                    </div>

                    <div class="my-8 lg:my-0">
                        <p
                            class="font-display text-xs font-bold uppercase tracking-[0.2em] text-emerald-800 dark:text-lime-300/90"
                        >
                            {{ config('app.name') }}
                        </p>
                        <h1
                            class="mt-3 font-display text-3xl font-bold leading-[1.1] tracking-tight text-emerald-950 sm:text-4xl dark:text-white"
                        >
                            Your corner of the court — and the whole game.
                        </h1>
                        <p class="mt-4 max-w-sm text-sm leading-relaxed text-emerald-900/85 dark:text-emerald-100/85">
                            Booking, venues, and more in one place. Sign in to pick up where you left off.
                        </p>
                    </div>

                    <p class="text-xs text-emerald-800/55 dark:text-emerald-200/50">
                        &copy; {{ date('Y') }} {{ config('app.name') }}
                    </p>
                </div>
            </div>

            {{-- Form panel --}}
            <div
                class="relative flex flex-1 flex-col bg-zinc-50 text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100"
            >
                <div class="absolute right-4 top-4 z-10 sm:right-8 sm:top-8">
                    <x-theme-toggle />
                </div>
                <div class="flex flex-1 items-center justify-center px-4 py-10 sm:px-8">
                    <div class="w-full max-w-md">
                        @include('partials.flash-messages')
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>

        @livewireScripts
    </body>
</html>
