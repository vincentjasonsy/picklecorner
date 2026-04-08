@php
    $c = $this->courtClient;
@endphp

<div class="space-y-8">
    <p class="text-sm text-zinc-600 dark:text-zinc-400">
        You cannot add or remove courts directly. Submit a request; a platform admin will approve or reject it.
    </p>

    @if (! $c)
        <p class="text-sm text-red-600">No venue is assigned.</p>
    @else
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Current courts</h2>
            @if ($c->courts->isEmpty())
                <p class="mt-3 text-sm text-zinc-500">No courts yet. Request your first outdoor or indoor court.</p>
            @else
                <ul class="mt-4 divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($c->courts as $court)
                        <li
                            class="space-y-3 border-b border-zinc-100 py-4 last:border-0 dark:border-zinc-800"
                            wire:key="court-row-{{ $court->id }}"
                        >
                            <div class="flex flex-wrap items-center justify-between gap-3 text-sm">
                                <div class="flex min-w-0 items-center gap-3">
                                    <div
                                        class="h-10 w-16 shrink-0 overflow-hidden rounded-md border border-zinc-200 bg-zinc-100 dark:border-zinc-600 dark:bg-zinc-800"
                                    >
                                        <img
                                            src="{{ $court->galleryImages->first()?->publicUrl() ?? $court->staticImageUrl() }}"
                                            alt="{{ $court->name }}"
                                            class="size-full object-cover object-center"
                                            loading="lazy"
                                        />
                                    </div>
                                    <span class="truncate font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ $court->name }}
                                    </span>
                                </div>
                                <span class="text-xs uppercase text-zinc-500">{{ $court->environment }}</span>
                            </div>
                            <livewire:venue.court-gallery-editor :court-id="$court->id" :key="'court-gal-'.$court->id" />
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="mt-6 flex flex-wrap gap-2">
                <button
                    type="button"
                    wire:click="requestAddOutdoor"
                    class="rounded-lg border border-zinc-300 px-3 py-2 text-xs font-bold uppercase tracking-wide text-zinc-800 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800/50"
                >
                    Request add outdoor
                </button>
                <button
                    type="button"
                    wire:click="requestAddIndoor"
                    class="rounded-lg border border-zinc-300 px-3 py-2 text-xs font-bold uppercase tracking-wide text-zinc-800 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800/50"
                >
                    Request add indoor
                </button>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Request removal</h2>
            <p class="mt-1 text-xs text-zinc-500">
                Removal is only approved if the court has no bookings (platform admin will verify).
            </p>
            <form wire:submit="requestRemoveCourt" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="min-w-0 flex-1">
                    <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Court</label>
                    <select
                        wire:model="removeCourtId"
                        class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                    >
                        <option value="">Select court</option>
                        @foreach ($c->courts as $court)
                            <option value="{{ $court->id }}">{{ $court->name }}</option>
                        @endforeach
                    </select>
                    @error('removeCourtId')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <button
                    type="submit"
                    class="rounded-lg bg-zinc-800 px-4 py-2 text-xs font-bold uppercase tracking-wide text-white hover:bg-zinc-900 dark:bg-zinc-200 dark:text-zinc-900 dark:hover:bg-white"
                >
                    Request removal
                </button>
            </form>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Your pending requests</h2>
            @if ($this->pendingRequests->isEmpty())
                <p class="mt-3 text-sm text-zinc-500">None.</p>
            @else
                <ul class="mt-4 space-y-3 text-sm">
                    @foreach ($this->pendingRequests as $req)
                        <li
                            class="flex flex-wrap items-start justify-between gap-2 rounded-lg border border-zinc-100 px-3 py-2 dark:border-zinc-800"
                            wire:key="ccr-{{ $req->id }}"
                        >
                            <div>
                                @if ($req->action === \App\Models\CourtChangeRequest::ACTION_ADD_COURT)
                                    <span class="font-medium text-zinc-900 dark:text-zinc-100">Add court</span>
                                    <span class="text-zinc-500">— {{ $req->environment }}</span>
                                @else
                                    <span class="font-medium text-zinc-900 dark:text-zinc-100">Remove court</span>
                                    <span class="text-zinc-500">— {{ $req->court?->name ?? 'Court' }}</span>
                                @endif
                                <span class="mt-1 block text-xs text-zinc-400">
                                    Submitted {{ $req->created_at?->diffForHumans() }}
                                </span>
                            </div>
                            <button
                                type="button"
                                wire:click="withdrawPendingRequest('{{ $req->id }}')"
                                wire:confirm="Withdraw this request? Platform admin will no longer see it."
                                class="shrink-0 text-xs font-semibold text-red-600 hover:text-red-700 dark:text-red-400"
                            >
                                Withdraw
                            </button>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif
</div>
