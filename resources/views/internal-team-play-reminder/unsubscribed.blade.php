<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        @include('partials.theme-init')

        <title>{{ $title ?? 'Reminders off' }} — {{ config('app.name') }}</title>

        @include('partials.favicon')

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link rel="preconnect" href="https://fonts.bunny.net" />
        <link
            href="https://fonts.bunny.net/css?family=barlow:600,700|instrument-sans:400,500,600"
            rel="stylesheet"
        />
        <style>
            .font-display {
                font-family: 'Barlow', ui-sans-serif, system-ui, sans-serif;
            }
        </style>
    </head>
    <body
        class="min-h-screen bg-zinc-100 text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100"
    >
        <div class="mx-auto flex min-h-screen max-w-lg flex-col justify-center px-4 py-12">
            <div
                class="rounded-2xl border border-zinc-200 bg-white p-8 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
            >
                <p class="font-display text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                    Booking reminders
                </p>
                <h1 class="font-display mt-2 text-2xl font-bold text-zinc-900 dark:text-white">
                    {{ $title }}
                </h1>
                <p class="mt-3 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                    You will not get more “time to book” emails or scheduled nudges for this account. Notifications
                    already in your bell stay until you mark them read or clear them.
                </p>
                @auth
                    @if (auth()->id() === $user->id)
                        <form
                            method="POST"
                            action="{{ route('internal-team-play-reminders.resubscribe') }}"
                            class="mt-6"
                        >
                            @csrf
                            <button
                                type="submit"
                                class="font-display w-full rounded-xl bg-emerald-600 px-4 py-3 text-sm font-bold uppercase tracking-wide text-white transition hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600"
                            >
                                Turn reminders back on
                            </button>
                        </form>
                        <p class="mt-4 text-center text-xs text-zinc-500">
                            @if (auth()->user()->usesStaffAppNav() && auth()->user()->staffAppHomeUrl())
                                <a
                                    href="{{ auth()->user()->staffAppHomeUrl() }}"
                                    class="font-semibold text-emerald-700 hover:text-emerald-800 dark:text-emerald-400"
                                >
                                    Back to app
                                </a>
                            @else
                                <a
                                    href="{{ route('account.dashboard') }}"
                                    class="font-semibold text-emerald-700 hover:text-emerald-800 dark:text-emerald-400"
                                >
                                    Back to my court
                                </a>
                            @endif
                        </p>
                    @endif
                @else
                    <p class="mt-6 text-xs text-zinc-500">
                        Signed in as this user?
                        <a
                            href="{{ route('login') }}"
                            class="font-semibold text-emerald-700 hover:text-emerald-800 dark:text-emerald-400"
                        >
                            Log in
                        </a>
                        to turn reminders back on from this page.
                    </p>
                @endauth
            </div>
        </div>
    </body>
</html>
