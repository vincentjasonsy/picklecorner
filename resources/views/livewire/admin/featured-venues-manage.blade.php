<div class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
    <header class="max-w-2xl">
        <h1 class="font-display text-2xl font-extrabold tracking-tight text-zinc-900 dark:text-white">
            Featured venues by city
        </h1>
        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
            Pick 5–10 active venues per city. Players browsing Book now in that area see them in a horizontal
            “Featured” strip (after they match the city via filters, profile, or default region).
        </p>
    </header>

    @if ($this->cityOptions()->isEmpty())
        <p class="mt-8 rounded-2xl border border-dashed border-zinc-300 bg-white p-8 text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400">
            Add at least one active venue with a city set before configuring featured lists.
        </p>
    @else
        <div class="mt-8 space-y-6">
            <div>
                <label
                    for="featured-city"
                    class="block text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                >
                    City
                </label>
                <select
                    wire:model.live="selectedCity"
                    id="featured-city"
                    class="mt-1.5 block w-full max-w-md rounded-xl border border-zinc-200 bg-white px-4 py-2.5 text-sm text-zinc-900 shadow-sm outline-none ring-emerald-500/30 focus:border-emerald-500 focus:ring-4 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                >
                    @foreach ($this->cityOptions() as $cityName)
                        <option value="{{ $cityName }}">{{ $cityName }}</option>
                    @endforeach
                </select>
                @error('selectedCity')
                    <p class="mt-1 text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            @if ($venues->isEmpty())
                <p class="text-sm text-zinc-600 dark:text-zinc-400">No active venues in this city.</p>
            @else
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        Venues in {{ $selectedCity }}
                    </p>
                    <ul class="mt-3 divide-y divide-zinc-200 rounded-2xl border border-zinc-200 bg-white dark:divide-zinc-800 dark:border-zinc-800 dark:bg-zinc-900/80">
                        @foreach ($venues as $venue)
                            <li
                                wire:key="featured-venue-row-{{ $venue->id }}"
                                class="flex items-start gap-3 px-4 py-3"
                            >
                                <button
                                    type="button"
                                    wire:click="toggleVenue('{{ $venue->id }}')"
                                    role="checkbox"
                                    aria-checked="{{ $this->isSelected((string) $venue->id) ? 'true' : 'false' }}"
                                    @class([
                                        'mt-0.5 flex size-5 shrink-0 items-center justify-center rounded border-2 transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:ring-offset-2 dark:focus:ring-offset-zinc-900',
                                        'border-emerald-600 bg-emerald-600 text-white' => $this->isSelected((string) $venue->id),
                                        'border-zinc-300 bg-white dark:border-zinc-600 dark:bg-zinc-950' => ! $this->isSelected((string) $venue->id),
                                    ])
                                >
                                    @if ($this->isSelected((string) $venue->id))
                                        <svg class="size-3.5" viewBox="0 0 12 12" fill="none" aria-hidden="true">
                                            <path
                                                d="M10 3L4.5 8.5L2 6"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            />
                                        </svg>
                                    @endif
                                </button>
                                <button
                                    type="button"
                                    wire:click="toggleVenue('{{ $venue->id }}')"
                                    class="flex-1 cursor-pointer text-left text-sm"
                                >
                                    <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $venue->name }}</span>
                                    @if ($venue->public_rating_average !== null)
                                        <span class="ml-2 text-zinc-500 dark:text-zinc-400">
                                            {{ number_format((float) $venue->public_rating_average, 1) }}★
                                            @if ($venue->public_rating_count > 0)
                                                ({{ $venue->public_rating_count }})
                                            @endif
                                        </span>
                                    @endif
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>

                @if (count($orderedIds) > 0)
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Order (left = first in the slider)
                        </p>
                        <ol class="mt-2 space-y-2">
                            @foreach ($orderedIds as $i => $id)
                                @php($v = $venues->firstWhere('id', $id))
                                <li
                                    class="flex items-center justify-between gap-2 rounded-xl border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950/50"
                                >
                                    <span class="truncate font-medium text-zinc-800 dark:text-zinc-200">
                                        {{ $i + 1 }}. {{ $v?->name ?? 'Unknown' }}
                                    </span>
                                    <span class="flex shrink-0 gap-1">
                                        <button
                                            type="button"
                                            wire:click="moveUp({{ $i }})"
                                            class="rounded-lg border border-zinc-200 px-2 py-1 text-xs font-semibold text-zinc-700 hover:bg-white dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-900"
                                            @if ($i === 0) disabled @endif
                                        >
                                            Up
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="moveDown({{ $i }})"
                                            class="rounded-lg border border-zinc-200 px-2 py-1 text-xs font-semibold text-zinc-700 hover:bg-white dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-900"
                                            @if ($i === count($orderedIds) - 1) disabled @endif
                                        >
                                            Down
                                        </button>
                                    </span>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                @endif

                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                    Selected:
                    <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ count($orderedIds) }}</span>
                    / 10 (minimum 5 to publish, or 0 to clear).
                </p>
                @error('orderedIds')
                    <p class="text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror

                <div class="flex flex-wrap gap-3">
                    <button
                        type="button"
                        wire:click="save"
                        wire:loading.attr="disabled"
                        wire:target="save"
                        class="inline-flex items-center rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-emerald-700 dark:bg-emerald-600 dark:hover:bg-emerald-500"
                    >
                        <span wire:loading.remove wire:target="save">Save for this city</span>
                        <span wire:loading wire:target="save">Saving…</span>
                    </button>
                </div>
            @endif
        </div>
    @endif
</div>
