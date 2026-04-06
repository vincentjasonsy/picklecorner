<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @include('partials.theme-init')

        <title>{{ $title ?? 'GameQ (Beta)' }} — {{ config('app.name') }}</title>

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

            .tool-focus-shell {
                background-color: #f4f4f5;
                background-image:
                    radial-gradient(ellipse 90% 70% at 50% -15%, rgba(16, 185, 129, 0.22), transparent 52%),
                    radial-gradient(ellipse 80% 50% at 100% 100%, rgba(20, 184, 166, 0.12), transparent 45%);
            }

            .dark .tool-focus-shell {
                background-color: #09090b;
                background-image:
                    radial-gradient(ellipse 90% 70% at 50% -15%, rgba(16, 185, 129, 0.14), transparent 52%),
                    radial-gradient(ellipse 80% 50% at 100% 100%, rgba(20, 184, 166, 0.08), transparent 45%);
            }
        </style>
    </head>
    <body
        class="min-h-[100dvh] antialiased text-zinc-900 transition-colors duration-200 dark:bg-zinc-950 dark:text-zinc-100"
    >
        @include('partials.flash-messages')
        @include('partials.impersonation-banner')

        @auth
            @if (auth()->user()->isDemoAccount() && ! auth()->user()->demoHasExpired())
                <div
                    class="border-b border-amber-200 bg-amber-50 px-4 py-2.5 text-center text-xs font-medium text-amber-950 dark:border-amber-900 dark:bg-amber-950/35 dark:text-amber-100"
                    role="status"
                >
                    Demo account — data removed after
                    {{ auth()->user()->demo_expires_at->timezone(config('app.timezone'))->format('M j, g:i a') }}.
                </div>
            @endif
        @endauth

        <div class="tool-focus-shell flex min-h-[100dvh] flex-col pb-[env(safe-area-inset-bottom)]">
            <header
                class="sticky top-0 z-40 flex shrink-0 items-center justify-between gap-3 border-b border-zinc-200/80 bg-white/85 px-4 py-3 pt-[max(0.75rem,env(safe-area-inset-top))] backdrop-blur-md dark:border-zinc-800/80 dark:bg-zinc-950/85"
            >
                <a
                    href="{{ route('account.dashboard') }}"
                    wire:navigate
                    class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-2xl border border-zinc-200/90 bg-white px-3 text-sm font-semibold text-zinc-700 shadow-sm transition hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800/80"
                >
                    ← Home
                </a>
                <div class="min-w-0 flex-1 text-center">
                    <p
                        class="font-display flex items-center justify-center gap-1 truncate text-[15px] font-extrabold tracking-tight text-zinc-900 dark:text-white"
                    >
                        @php($t = $title ?? '')
                        @if (in_array($t, ['', 'GameQ', 'GameQ (Beta)'], true))
                            <x-gameq-mark compact class="min-w-0 shrink" />
                        @else
                            <span class="truncate">{{ $t }}</span>
                        @endif
                    </p>
                    <p class="truncate text-[11px] font-medium text-zinc-500 dark:text-zinc-400">
                        Session queue
                    </p>
                </div>
                <div class="flex w-[88px] shrink-0 justify-end">
                    <x-theme-toggle />
                </div>
            </header>

            <main class="flex min-h-0 flex-1 flex-col px-3 pt-4 sm:px-4 sm:pt-6">
                <div
                    class="mx-auto flex w-full max-w-lg flex-1 flex-col rounded-[1.75rem] border border-zinc-200/90 bg-white/95 shadow-[0_24px_60px_-28px_rgba(0,0,0,0.18)] ring-1 ring-zinc-900/[0.04] dark:border-zinc-800 dark:bg-zinc-900/95 dark:ring-white/[0.06] lg:max-w-2xl lg:rounded-[2rem]"
                >
                    <div class="min-h-0 flex-1 rounded-[inherit] p-4 sm:p-5 lg:p-6">
                        {{ $slot }}
                    </div>
                </div>
            </main>

            <footer class="shrink-0 px-4 py-4 text-center">
                <p class="text-[11px] font-medium text-zinc-500 dark:text-zinc-500">
                    {{ config('app.name') }} · tool mode
                </p>
            </footer>
        </div>

        @livewireScripts
    </body>
</html>
