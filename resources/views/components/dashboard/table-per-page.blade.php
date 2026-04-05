@props([
    'label' => 'Per page',
    'options' => [10, 25, 50, 100],
])

<div {{ $attributes->class(['w-full sm:w-28']) }}>
    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
        {{ $label }}
    </label>
    <select
        wire:model.live="perPage"
        class="mt-1 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
    >
        @foreach ($options as $n)
            <option value="{{ $n }}">{{ $n }}</option>
        @endforeach
    </select>
</div>
