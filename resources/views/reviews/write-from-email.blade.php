@component('layouts.guest', ['title' => 'Leave a review — '.config('app.name')])
    <div class="mx-auto max-w-2xl px-4 py-12 sm:px-6 lg:px-8">
        <p class="font-display text-xs font-semibold uppercase tracking-[0.2em] text-emerald-600 dark:text-emerald-400">
            Review
        </p>
        <h1 class="mt-2 font-display text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">
            {{ $targetLabel }}
        </h1>
        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
            Share a rating and optional comment. Submissions are reviewed before they appear publicly.
        </p>

        <div class="mt-8">
            <livewire:reviews.user-reviews-panel
                :target-type="$targetType"
                :target-id="$targetId"
                :key="'review-email-'.$targetType.'-'.$targetId"
            />
        </div>

        <p class="mt-10 text-xs text-zinc-500 dark:text-zinc-400">
            <a href="{{ route('home') }}" wire:navigate class="font-semibold text-emerald-600 hover:underline dark:text-emerald-400">
                ← Back to home
            </a>
        </p>
    </div>
@endcomponent
