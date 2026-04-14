@php
    use App\Models\UserReview;
@endphp

<div class="mx-auto max-w-5xl space-y-10">
    <div>
        <h1 class="font-display text-2xl font-bold text-zinc-900 dark:text-white">User review approvals</h1>
        <p class="mt-2 max-w-2xl text-sm text-zinc-600 dark:text-zinc-400">
            Venue and coach ratings stay hidden until you approve them here. Reject removes the review from the queue
            (aggregates only include approved reviews). Flagged language is highlighted — still your call to approve or
            deny.
        </p>
    </div>

    <section class="space-y-4">
        <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">Pending</h2>
        @forelse ($pending as $rev)
            @php
                $targetLabel = 'Unknown';
                if ($rev->target_type === UserReview::TARGET_VENUE) {
                    $targetLabel = 'Venue: '.(\App\Models\CourtClient::query()->find($rev->target_id)?->name ?? $rev->target_id);
                } elseif ($rev->target_type === UserReview::TARGET_COACH) {
                    $targetLabel = 'Coach: '.(\App\Models\User::query()->find($rev->target_id)?->name ?? $rev->target_id);
                }
            @endphp
            <div
                class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900"
                wire:key="rev-pend-{{ $rev->id }}"
            >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ $targetLabel }}
                        </p>
                        <p class="mt-1 font-semibold text-zinc-900 dark:text-zinc-100">
                            {{ $rev->author?->name ?? 'Member' }}
                            <span class="font-normal text-zinc-500">· {{ $rev->rating }}/5 overall</span>
                            @if ($rev->target_type === UserReview::TARGET_VENUE && $rev->rating_location !== null)
                                <span class="block text-xs font-normal text-zinc-500">
                                    Location {{ $rev->rating_location }}/5 · Amenities {{ $rev->rating_amenities }}/5 · Price
                                    {{ $rev->rating_price }}/5
                                </span>
                            @endif
                        </p>
                        @if ($rev->profanity_flag)
                            <p
                                class="mt-2 inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-900 dark:bg-amber-950/60 dark:text-amber-200"
                            >
                                Possible profanity detected
                            </p>
                        @endif
                        @if ($rev->body)
                            <p class="mt-3 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">{{ $rev->body }}</p>
                        @else
                            <p class="mt-2 text-xs italic text-zinc-500">No comment — stars only.</p>
                        @endif
                        <p class="mt-2 text-xs text-zinc-400">Submitted {{ $rev->created_at?->diffForHumans() }}</p>
                    </div>
                    <div class="flex shrink-0 flex-wrap gap-2">
                        <button
                            type="button"
                            wire:click="approve('{{ $rev->id }}')"
                            class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-bold uppercase tracking-wide text-white hover:bg-emerald-700"
                        >
                            Approve
                        </button>
                        <button
                            type="button"
                            wire:click="reject('{{ $rev->id }}')"
                            wire:confirm="Reject this review? It will not be shown publicly."
                            class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-bold uppercase tracking-wide text-red-700 hover:bg-red-50 dark:border-red-900 dark:text-red-400 dark:hover:bg-red-950/40"
                        >
                            Reject
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <p class="text-sm text-zinc-500 dark:text-zinc-400">No reviews waiting for approval.</p>
        @endforelse
    </section>
</div>
