@props([
    'paginator' => null,
])

@php
    $paginatorClasses = 'flex justify-center text-sm text-zinc-600 sm:justify-start dark:text-zinc-400';
@endphp

<div {{ $attributes->merge(['class' => 'space-y-6']) }}>
    @isset($intro)
        {{ $intro }}
    @endisset

    @isset($toolbar)
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="flex min-w-0 flex-1 flex-col gap-3 sm:flex-row sm:items-end sm:gap-4">
                {{ $toolbar }}
            </div>
            @isset($toolbarEnd)
                <div class="flex shrink-0 flex-wrap items-end gap-3">
                    {{ $toolbarEnd }}
                </div>
            @endisset
        </div>
    @endisset

    <div
        class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900"
    >
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                @isset($head)
                    <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                        {{ $head }}
                    </thead>
                @endisset
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    {{ $slot }}
                </tbody>
            </table>
        </div>
    </div>

    @if ($paginator instanceof \Illuminate\Contracts\Pagination\Paginator)
        <div class="{{ $paginatorClasses }}">
            {{ $paginator->links() }}
        </div>
    @endif
</div>
