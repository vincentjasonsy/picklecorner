<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @include('partials.theme-init')

        @include('partials.document-title')

        @include('partials.favicon')

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link
            href="https://fonts.bunny.net/css?family=barlow:600,700,800|instrument-sans:400,500,600,700"
            rel="stylesheet"
        />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
        <style>
            [x-cloak] {
                display: none !important;
            }

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
        x-data="{
            pwaGuideOpen: false,
            pwaPlatform: 'other',
            pwaInstalled: false,
            init() {
                const ua = (navigator.userAgent || '').toLowerCase();
                const isiOS = /iphone|ipad|ipod/.test(ua);
                const isAndroid = ua.includes('android');
                this.pwaPlatform = isiOS ? 'ios' : (isAndroid ? 'android' : 'other');
                this.pwaInstalled = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
            },
        }"
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
                        @php
                            $t = $title ?? '';
                        @endphp
                        @if (in_array($t, ['', 'GameQ', 'GameQ (Beta)'], true))
                            <x-gameq-mark compact class="min-w-0 shrink" />
                        @else
                            <span class="truncate">{{ $t }}</span>
                        @endif
                    </p>
                    <p class="hidden truncate text-[11px] font-medium text-zinc-500 dark:text-zinc-400 sm:block">
                        Session queue
                    </p>
                </div>
                <div class="flex shrink-0 items-center justify-end gap-1.5">
                    <button
                        type="button"
                        x-cloak
                        x-show="!pwaInstalled"
                        @click="pwaGuideOpen = true"
                        class="inline-flex min-h-[44px] items-center rounded-2xl border border-zinc-200/90 bg-white px-2.5 text-[11px] font-semibold text-zinc-700 shadow-sm transition hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800/80 lg:hidden"
                    >
                        Install app
                    </button>
                    <x-theme-toggle />
                </div>
            </header>

            <main class="flex min-h-0 flex-1 flex-col px-2 pt-3 sm:px-4 sm:pt-6">
                <div
                    class="mx-auto flex w-full max-w-5xl flex-1 flex-col rounded-2xl border border-zinc-200/90 bg-white/95 shadow-sm ring-1 ring-zinc-900/[0.04] dark:border-zinc-800 dark:bg-zinc-900/95 dark:ring-white/[0.06] sm:rounded-[1.75rem] sm:shadow-[0_24px_60px_-28px_rgba(0,0,0,0.18)] lg:rounded-[2rem]"
                >
                    <div class="min-h-0 flex-1 rounded-[inherit] p-3 sm:p-5 lg:p-6">
                        {{ $slot }}
                    </div>
                </div>
            </main>

            <footer class="hidden shrink-0 px-4 py-3 text-center sm:block">
                <p class="text-[11px] font-medium text-zinc-500 dark:text-zinc-500">
                    {{ config('app.name') }} · tool mode
                </p>
            </footer>
        </div>

        <div
            x-cloak
            x-show="pwaGuideOpen"
            x-transition.opacity
            @keydown.escape.window="pwaGuideOpen = false"
            class="fixed inset-0 z-50 flex items-end justify-center bg-zinc-900/60 p-0 backdrop-blur-[1px] sm:items-center sm:p-4"
            @click.self="pwaGuideOpen = false"
        >
            <div class="w-full max-w-md rounded-t-3xl border border-zinc-200 bg-white p-4 shadow-xl dark:border-zinc-700 dark:bg-zinc-900 sm:rounded-3xl sm:p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-display text-lg font-extrabold text-zinc-900 dark:text-white">Install as app</p>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Keep GameQ on your home screen for quick access.</p>
                    </div>
                    <button
                        type="button"
                        @click="pwaGuideOpen = false"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-zinc-500 transition hover:bg-zinc-100 dark:hover:bg-zinc-800"
                        aria-label="Close install guide"
                    >
                        ✕
                    </button>
                </div>

                <div class="mt-4 space-y-3 text-sm text-zinc-700 dark:text-zinc-200">
                    <div x-show="pwaPlatform === 'ios'" x-cloak class="rounded-xl border border-zinc-200/80 bg-zinc-50/70 p-3 dark:border-zinc-700 dark:bg-zinc-950/60">
                        <p class="text-xs font-bold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">iPhone / iPad (Safari)</p>
                        <ol class="mt-2 list-decimal space-y-1.5 pl-4 text-[13px] leading-relaxed">
                            <li>Tap the <span class="font-semibold">Share</span> icon in Safari.</li>
                            <li>Choose <span class="font-semibold">Add to Home Screen</span>.</li>
                            <li>Tap <span class="font-semibold">Add</span>.</li>
                        </ol>
                    </div>

                    <div x-show="pwaPlatform === 'android'" x-cloak class="rounded-xl border border-zinc-200/80 bg-zinc-50/70 p-3 dark:border-zinc-700 dark:bg-zinc-950/60">
                        <p class="text-xs font-bold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Android (Chrome)</p>
                        <ol class="mt-2 list-decimal space-y-1.5 pl-4 text-[13px] leading-relaxed">
                            <li>Tap the browser <span class="font-semibold">⋮ menu</span>.</li>
                            <li>Select <span class="font-semibold">Install app</span> or <span class="font-semibold">Add to Home screen</span>.</li>
                            <li>Confirm <span class="font-semibold">Install</span>.</li>
                        </ol>
                    </div>

                    <div x-show="pwaPlatform === 'other'" x-cloak class="rounded-xl border border-zinc-200/80 bg-zinc-50/70 p-3 dark:border-zinc-700 dark:bg-zinc-950/60">
                        <p class="text-xs font-bold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Browser install</p>
                        <ol class="mt-2 list-decimal space-y-1.5 pl-4 text-[13px] leading-relaxed">
                            <li>Open your browser menu.</li>
                            <li>Pick <span class="font-semibold">Install app</span> / <span class="font-semibold">Add to home screen</span>.</li>
                            <li>Confirm to pin GameQ as an app.</li>
                        </ol>
                    </div>
                </div>

                <button
                    type="button"
                    @click="pwaGuideOpen = false"
                    class="mt-4 w-full rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-500 active:scale-[0.99]"
                >
                    Got it
                </button>
            </div>
        </div>

        @livewireScripts
    </body>
</html>
