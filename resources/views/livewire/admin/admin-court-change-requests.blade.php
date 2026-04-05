<div class="space-y-6">
    <p class="text-sm text-zinc-600 dark:text-zinc-400">
        Venue admins request court additions or removals here. Approving an add creates the court and renames/reorders
        like the venue editor; approving removal deletes the court only if it has no bookings.
    </p>

    @if ($this->pendingRequests->isEmpty())
        <p class="rounded-xl border border-dashed border-zinc-300 px-6 py-10 text-center text-sm text-zinc-500 dark:border-zinc-600">
            No pending requests.
        </p>
    @else
        <ul class="space-y-4">
            @foreach ($this->pendingRequests as $req)
                <li
                    class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900"
                    wire:key="ccr-{{ $req->id }}"
                >
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $req->courtClient?->name ?? 'Venue' }}
                            </p>
                            <p class="text-xs text-zinc-500">
                                From {{ $req->requester?->name ?? 'User' }}
                                · {{ $req->created_at?->isoFormat('MMM D, YYYY h:mm a') }}
                            </p>
                            @if ($req->action === \App\Models\CourtChangeRequest::ACTION_ADD_COURT)
                                <p class="mt-2 text-sm text-zinc-700 dark:text-zinc-300">
                                    <span class="font-semibold">Add</span>
                                    {{ $req->environment }} court
                                </p>
                            @else
                                <p class="mt-2 text-sm text-zinc-700 dark:text-zinc-300">
                                    <span class="font-semibold">Remove</span>
                                    {{ $req->court?->name ?? 'Court' }}
                                </p>
                            @endif
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button
                                type="button"
                                wire:click="approve('{{ $req->id }}')"
                                class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-bold uppercase tracking-wide text-white hover:bg-emerald-700"
                            >
                                Approve
                            </button>
                            <button
                                type="button"
                                wire:click="openReject('{{ $req->id }}')"
                                class="rounded-lg border border-zinc-300 px-3 py-1.5 text-xs font-semibold text-zinc-700 dark:border-zinc-600 dark:text-zinc-300"
                            >
                                Reject
                            </button>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif

    @if ($rejectingId)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-900/60 p-4"
            wire:click="cancelReject"
        >
            <div
                class="w-full max-w-md rounded-xl border border-zinc-200 bg-white p-6 shadow-xl dark:border-zinc-700 dark:bg-zinc-900"
                wire:click.stop
            >
                <h3 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Reject request</h3>
                <textarea
                    wire:model="rejectNote"
                    rows="3"
                    class="mt-4 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                    placeholder="Note to internal log"
                ></textarea>
                @error('rejectNote')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
                <div class="mt-4 flex justify-end gap-2">
                    <button
                        type="button"
                        wire:click="cancelReject"
                        class="rounded-lg border border-zinc-200 px-3 py-2 text-sm font-semibold dark:border-zinc-600"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        wire:click="confirmReject"
                        class="rounded-lg bg-red-600 px-3 py-2 text-sm font-bold text-white hover:bg-red-700"
                    >
                        Reject
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
