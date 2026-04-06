<div class="space-y-6">
    <div>
        <h1 class="font-display text-2xl font-bold text-zinc-900 dark:text-white">Venues you coach</h1>
        <p class="mt-2 max-w-2xl text-sm text-zinc-600 dark:text-zinc-400">
            Turn on a whole venue to coach at every court there. Players still pick a specific court when booking; your
            availability can be set per venue for all courts at once.
        </p>
    </div>

    <ul class="space-y-3" role="list">
        @foreach ($venues as $venue)
            @php
                $on = $this->venueIsFullyCoached($venue->id);
            @endphp
            <li
                wire:key="coach-venue-row-{{ $venue->id }}"
                class="flex items-center justify-between gap-4 rounded-2xl border border-zinc-200 bg-white px-5 py-4 dark:border-zinc-800 dark:bg-zinc-900/80"
            >
                <div>
                    <p class="font-display font-bold text-zinc-900 dark:text-white">
                        {{ $venue->name }}
                        @if ($venue->city)
                            <span class="font-normal text-zinc-500 dark:text-zinc-400">· {{ $venue->city }}</span>
                        @endif
                    </p>
                    <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                        {{ $venue->bookable_courts_count }}
                        {{ \Illuminate\Support\Str::plural('court', $venue->bookable_courts_count) }}
                    </p>
                </div>
                <button
                    type="button"
                    role="switch"
                    wire:click="toggleVenue('{{ $venue->id }}')"
                    aria-checked="{{ $on ? 'true' : 'false' }}"
                    class="relative inline-flex h-7 w-12 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent px-0.5 transition-colors focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2 dark:focus:ring-offset-zinc-900 {{ $on ? 'justify-end bg-violet-600' : 'justify-start bg-zinc-200 dark:bg-zinc-600' }}"
                >
                    <span class="sr-only">Coach at {{ $venue->name }}</span>
                    <span
                        aria-hidden="true"
                        class="pointer-events-none inline-block size-6 rounded-full bg-white shadow"
                    ></span>
                </button>
            </li>
        @endforeach
    </ul>
</div>
