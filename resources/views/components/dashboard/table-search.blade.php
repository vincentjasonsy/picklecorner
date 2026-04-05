@props([
    'label' => 'Search',
    'placeholder' => '',
])

<div class="min-w-0 flex-1">
    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
        {{ $label }}
    </label>
    <input
        type="search"
        placeholder="{{ $placeholder }}"
        {{ $attributes->merge([
            'class' =>
                'mt-1 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 placeholder:text-zinc-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:placeholder:text-zinc-500',
        ]) }}
    />
</div>
