<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        @include('partials.theme-init')

        @include('partials.document-title')

        @include('partials.favicon')

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles
    </head>
    <body
        class="min-h-screen bg-zinc-50 text-zinc-900 antialiased transition-colors duration-200 dark:bg-zinc-950 dark:text-zinc-100"
    >
        <div class="fixed right-4 top-4 z-40">
            <x-pwa-install-guide />
        </div>

        {{ $slot }}

        @livewireScripts
    </body>
</html>
