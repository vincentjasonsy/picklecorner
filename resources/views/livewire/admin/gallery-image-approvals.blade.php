<div class="mx-auto max-w-5xl space-y-10">
    <div>
        <h1 class="font-display text-2xl font-bold text-zinc-900 dark:text-white">Gallery image approvals</h1>
        <p class="mt-2 max-w-2xl text-sm text-zinc-600 dark:text-zinc-400">
            Venue and court photos stay hidden from the public Book now pages until you approve them here (or on each
            court client’s edit screen). Reject removes the file permanently.
        </p>
    </div>

    <section class="space-y-4">
        <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Venue photos (pending)</h2>
        @forelse ($venuePending as $row)
            <div
                class="flex flex-wrap items-center gap-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900"
                wire:key="vpend-{{ $row->id }}"
            >
                <div class="h-20 w-28 shrink-0 overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                    <img
                        src="{{ $row->publicUrl() }}"
                        alt=""
                        class="size-full object-cover object-center"
                        loading="lazy"
                    />
                </div>
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-zinc-900 dark:text-zinc-100">
                        {{ $row->courtClient?->name ?? 'Venue' }}
                    </p>
                    <p class="mt-0.5 text-xs text-zinc-500">
                        Uploaded {{ $row->created_at?->diffForHumans() }}
                    </p>
                    <a
                        href="{{ route('admin.court-clients.edit', $row->court_client_id) }}"
                        wire:navigate
                        class="mt-2 inline-block text-xs font-semibold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400"
                    >
                        Open venue settings
                    </a>
                </div>
                <div class="flex shrink-0 flex-wrap gap-2">
                    <button
                        type="button"
                        wire:click="approveVenueImage('{{ $row->id }}')"
                        class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-bold uppercase tracking-wide text-white hover:bg-emerald-700"
                    >
                        Approve
                    </button>
                    <button
                        type="button"
                        wire:click="rejectVenueImage('{{ $row->id }}')"
                        wire:confirm="Reject and delete this image? The venue will need to upload again."
                        class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-bold uppercase tracking-wide text-red-700 hover:bg-red-50 dark:border-red-900 dark:text-red-400 dark:hover:bg-red-950/40"
                    >
                        Reject
                    </button>
                </div>
            </div>
        @empty
            <p class="text-sm text-zinc-500 dark:text-zinc-400">No pending venue photos.</p>
        @endforelse
    </section>

    <section class="space-y-4">
        <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Court photos (pending)</h2>
        @forelse ($courtPending as $row)
            @php
                $court = $row->court;
                $venue = $court?->courtClient;
            @endphp
            <div
                class="flex flex-wrap items-center gap-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900"
                wire:key="cpend-{{ $row->id }}"
            >
                <div class="h-20 w-28 shrink-0 overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                    <img
                        src="{{ $row->publicUrl() }}"
                        alt=""
                        class="size-full object-cover object-center"
                        loading="lazy"
                    />
                </div>
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-zinc-900 dark:text-zinc-100">
                        {{ $court?->name ?? 'Court' }}
                        <span class="font-normal text-zinc-500">· {{ $venue?->name ?? 'Venue' }}</span>
                    </p>
                    <p class="mt-0.5 text-xs text-zinc-500">
                        Uploaded {{ $row->created_at?->diffForHumans() }}
                    </p>
                    @if ($venue)
                        <a
                            href="{{ route('admin.court-clients.edit', $venue) }}"
                            wire:navigate
                            class="mt-2 inline-block text-xs font-semibold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400"
                        >
                            Open venue settings
                        </a>
                    @endif
                </div>
                <div class="flex shrink-0 flex-wrap gap-2">
                    <button
                        type="button"
                        wire:click="approveCourtImage('{{ $row->id }}')"
                        class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-bold uppercase tracking-wide text-white hover:bg-emerald-700"
                    >
                        Approve
                    </button>
                    <button
                        type="button"
                        wire:click="rejectCourtImage('{{ $row->id }}')"
                        wire:confirm="Reject and delete this image?"
                        class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-bold uppercase tracking-wide text-red-700 hover:bg-red-50 dark:border-red-900 dark:text-red-400 dark:hover:bg-red-950/40"
                    >
                        Reject
                    </button>
                </div>
            </div>
        @empty
            <p class="text-sm text-zinc-500 dark:text-zinc-400">No pending court photos.</p>
        @endforelse
    </section>
</div>
