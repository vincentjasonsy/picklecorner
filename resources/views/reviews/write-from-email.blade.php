@component('layouts.guest', ['title' => 'Leave a review'])
    <div class="mx-auto max-w-2xl px-4 py-12 sm:px-6 lg:px-8">
        <p class="font-display text-xs font-semibold uppercase tracking-[0.2em] text-emerald-600 dark:text-emerald-400">
            Review
        </p>
        <h1 class="mt-2 font-display text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">
            {{ $targetLabel }}
        </h1>
        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
            @if ($targetType === \App\Models\UserReview::TARGET_VENUE)
                Rate <strong>location</strong>, <strong>amenities</strong>, and <strong>price / value</strong> (each out of five). Your overall score is the average of those three. Add an optional comment — submissions are checked before they appear publicly.
            @else
                Share an overall rating and optional comment. Submissions are reviewed before they appear publicly.
            @endif
        </p>

        <div class="mt-8">
            @if (public_reviews_enabled())
                <livewire:reviews.user-reviews-panel
                    :target-type="$targetType"
                    :target-id="$targetId"
                    :key="'review-email-'.$targetType.'-'.$targetId"
                />
            @else
                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                    Member reviews are not shown on the site right now.
                </p>
            @endif
        </div>

        <p class="mt-10 text-xs text-zinc-500 dark:text-zinc-400">
            <a href="{{ route('home') }}" wire:navigate class="font-semibold text-emerald-600 hover:underline dark:text-emerald-400">
                ← Back to home
            </a>
        </p>
    </div>
@endcomponent
