@php
if (! isset($scrollTo)) {
    $scrollTo = 'body';
}

$scrollIntoViewJsSnippet = ($scrollTo !== false)
    ? <<<JS
       (\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()
    JS
    : '';

$btnBase =
    'inline-flex min-h-10 min-w-10 shrink-0 items-center justify-center border text-sm font-semibold transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500/60 focus-visible:ring-offset-2 focus-visible:ring-offset-white disabled:pointer-events-none disabled:opacity-40 dark:focus-visible:ring-offset-zinc-950';
$btnIdle = 'border-zinc-200 bg-white text-zinc-700 hover:bg-zinc-50 active:bg-zinc-100 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800';
$btnDisabled = 'cursor-not-allowed border-zinc-200 bg-zinc-50 text-zinc-400 dark:border-zinc-700 dark:bg-zinc-900/50 dark:text-zinc-500';
$currentPage =
    'cursor-default border-emerald-600 bg-emerald-600 text-white shadow-sm dark:border-emerald-500 dark:bg-emerald-600';
$iconClass = 'size-5 shrink-0';
@endphp

<div>
    @if ($paginator->hasPages())
        <nav role="navigation" aria-label="Pagination" class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            {{-- Mobile --}}
            <div class="flex w-full justify-between gap-3 sm:hidden">
                <span class="min-w-0 flex-1">
                    @if ($paginator->onFirstPage())
                        <span
                            class="{{ $btnBase }} {{ $btnDisabled }} w-full rounded-xl px-4 py-2.5"
                            aria-disabled="true"
                        >
                            {!! __('pagination.previous') !!}
                        </span>
                    @else
                        <button
                            type="button"
                            wire:click="previousPage('{{ $paginator->getPageName() }}')"
                            x-on:click="{{ $scrollIntoViewJsSnippet }}"
                            wire:loading.attr="disabled"
                            dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.before"
                            class="{{ $btnBase }} {{ $btnIdle }} w-full rounded-xl px-4 py-2.5"
                        >
                            {!! __('pagination.previous') !!}
                        </button>
                    @endif
                </span>

                <span class="min-w-0 flex-1">
                    @if ($paginator->hasMorePages())
                        <button
                            type="button"
                            wire:click="nextPage('{{ $paginator->getPageName() }}')"
                            x-on:click="{{ $scrollIntoViewJsSnippet }}"
                            wire:loading.attr="disabled"
                            dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.before"
                            class="{{ $btnBase }} {{ $btnIdle }} w-full rounded-xl px-4 py-2.5"
                        >
                            {!! __('pagination.next') !!}
                        </button>
                    @else
                        <span
                            class="{{ $btnBase }} {{ $btnDisabled }} w-full rounded-xl px-4 py-2.5"
                            aria-disabled="true"
                        >
                            {!! __('pagination.next') !!}
                        </span>
                    @endif
                </span>
            </div>

            {{-- Tablet / desktop --}}
            <div class="hidden gap-4 sm:flex sm:w-full sm:flex-col sm:items-stretch lg:flex-row lg:items-center lg:justify-between">
                <div class="shrink-0">
                    <p class="text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                        <span>{!! __('Showing') !!}</span>
                        <span class="font-semibold tabular-nums text-zinc-900 dark:text-zinc-100">{{ $paginator->firstItem() }}</span>
                        <span>{!! __('to') !!}</span>
                        <span class="font-semibold tabular-nums text-zinc-900 dark:text-zinc-100">{{ $paginator->lastItem() }}</span>
                        <span>{!! __('of') !!}</span>
                        <span class="font-semibold tabular-nums text-zinc-900 dark:text-zinc-100">{{ $paginator->total() }}</span>
                        <span>{!! __('results') !!}</span>
                    </p>
                </div>

                <div class="min-w-0 flex-1 sm:flex sm:justify-end">
                    <div
                        class="inline-flex max-w-full items-center gap-1 overflow-x-auto rounded-xl border border-zinc-200/90 bg-zinc-50/90 p-1 shadow-sm dark:border-zinc-700 dark:bg-zinc-900/60"
                    >
                        {{-- Previous --}}
                        @if ($paginator->onFirstPage())
                            <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                                <span
                                    class="{{ $btnBase }} {{ $btnDisabled }} rounded-lg"
                                    aria-hidden="true"
                                >
                                    <svg class="{{ $iconClass }}" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            fill-rule="evenodd"
                                            d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </span>
                            </span>
                        @else
                            <button
                                type="button"
                                wire:click="previousPage('{{ $paginator->getPageName() }}')"
                                x-on:click="{{ $scrollIntoViewJsSnippet }}"
                                dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.after"
                                class="{{ $btnBase }} {{ $btnIdle }} rounded-lg"
                                aria-label="{{ __('pagination.previous') }}"
                            >
                                <svg class="{{ $iconClass }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </button>
                        @endif

                        @foreach ($elements as $element)
                            @if (is_string($element))
                                <span aria-disabled="true" class="px-2 py-2 text-sm font-medium text-zinc-400 dark:text-zinc-500">
                                    {{ $element }}
                                </span>
                            @endif

                            @if (is_array($element))
                                @foreach ($element as $page => $url)
                                    <span wire:key="paginator-{{ $paginator->getPageName() }}-page{{ $page }}">
                                        @if ($page == $paginator->currentPage())
                                            <span aria-current="page">
                                                <span
                                                    class="{{ $btnBase }} {{ $currentPage }} min-w-10 rounded-lg px-3 tabular-nums"
                                                >
                                                    {{ $page }}
                                                </span>
                                            </span>
                                        @else
                                            <button
                                                type="button"
                                                wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')"
                                                x-on:click="{{ $scrollIntoViewJsSnippet }}"
                                                class="{{ $btnBase }} {{ $btnIdle }} min-w-10 rounded-lg px-3 tabular-nums"
                                                aria-label="{{ __('Go to page :page', ['page' => $page]) }}"
                                            >
                                                {{ $page }}
                                            </button>
                                        @endif
                                    </span>
                                @endforeach
                            @endif
                        @endforeach

                        {{-- Next --}}
                        @if ($paginator->hasMorePages())
                            <button
                                type="button"
                                wire:click="nextPage('{{ $paginator->getPageName() }}')"
                                x-on:click="{{ $scrollIntoViewJsSnippet }}"
                                dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.after"
                                class="{{ $btnBase }} {{ $btnIdle }} rounded-lg"
                                aria-label="{{ __('pagination.next') }}"
                            >
                                <svg class="{{ $iconClass }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </button>
                        @else
                            <span aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                                <span
                                    class="{{ $btnBase }} {{ $btnDisabled }} rounded-lg"
                                    aria-hidden="true"
                                >
                                    <svg class="{{ $iconClass }}" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            fill-rule="evenodd"
                                            d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </span>
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </nav>
    @endif
</div>
