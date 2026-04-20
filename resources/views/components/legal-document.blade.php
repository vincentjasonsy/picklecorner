@props([
    'narrow' => true,
])

@php
    $container = $narrow ? 'max-w-3xl' : 'max-w-4xl';
@endphp

<article class="{{ $container }} mx-auto px-4 py-10 text-base leading-relaxed text-zinc-700 dark:text-zinc-300 sm:px-6 sm:py-12 lg:px-8">
    <div
        class="overflow-hidden rounded-2xl border border-zinc-200/90 bg-white shadow-sm ring-1 ring-zinc-950/5 dark:border-zinc-800 dark:bg-zinc-900/90 dark:ring-white/5"
    >
        <div
            @class([
                'prose prose-zinc max-w-none px-5 py-8 dark:prose-invert sm:px-8 sm:py-10',
                'prose-headings:scroll-mt-24 prose-headings:font-display',
                'prose-h2:mt-10 prose-h2:border-b prose-h2:border-emerald-200 prose-h2:pb-3 prose-h2:text-base prose-h2:font-bold prose-h2:uppercase prose-h2:tracking-wide prose-h2:text-zinc-900 dark:prose-h2:border-emerald-900/70 dark:prose-h2:text-white',
                'prose-h2:first:mt-0',
                'prose-p:mt-4 prose-p:first:mt-0 prose-p:leading-relaxed',
                'prose-strong:font-semibold prose-strong:text-zinc-900 dark:prose-strong:text-white',
                'prose-ul:my-4 prose-ul:list-disc prose-ul:pl-5 prose-ul:marker:text-emerald-600 dark:prose-ul:marker:text-emerald-400',
                'prose-li:my-1.5 prose-li:pl-1 prose-li:leading-relaxed',
                'prose-a:font-semibold prose-a:text-emerald-700 prose-a:no-underline prose-a:transition-colors hover:prose-a:underline dark:prose-a:text-emerald-400',
            ])
        >
            {{ $slot }}
        </div>
    </div>

    @include('partials.legal-pages-nav')
</article>
