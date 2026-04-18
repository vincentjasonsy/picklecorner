<div class="space-y-6">
    @if ($this->showHeading)
        <div>
            <h2 class="font-display text-lg font-bold text-zinc-900 dark:text-white">
                @if ($this->targetType === \App\Models\UserReview::TARGET_VENUE)
                    Venue reviews
                @else
                    Coach reviews
                @endif
            </h2>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                @guest
                    Read what other players say. Sign in after you play if you’d like to leave your own — submissions are
                    checked before they appear.
                @else
                    @if ($showReviewForm)
                        Ratings and comments are checked before they appear publicly. You can submit only after a confirmed booking
                        ends, within a short window.
                    @else
                        Ratings and comments are moderated before they appear publicly. Submitting new reviews is turned off for now.
                    @endif
                @endguest
            </p>
        </div>
    @else
        <div>
            <h3 class="font-display text-base font-bold text-zinc-900 dark:text-white">Member reviews</h3>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                @guest
                    Anyone can read published feedback. Sign in after you play to submit your own.
                @else
                    @if ($showReviewForm)
                        Submit or update your review when your booking window is open.
                    @else
                        Published feedback appears below; submitting reviews is turned off for now.
                    @endif
                @endguest
            </p>
        </div>
    @endif

    @if ($ratingSummary !== null && $ratingSummary['count'] > 0)
        <div>
            <p class="mt-2 inline-flex flex-wrap items-center gap-1 text-sm font-semibold text-zinc-800 dark:text-zinc-200">
                <x-app-icon name="star-solid" class="size-4 text-amber-500 dark:text-amber-400" />
                {{ number_format($ratingSummary['average'], 1) }}
                <span class="font-normal text-zinc-500 dark:text-zinc-400">
                    from {{ number_format($ratingSummary['count']) }} {{ \Illuminate\Support\Str::plural('review', $ratingSummary['count']) }}
                </span>
            </p>
            @if ($this->targetType === \App\Models\UserReview::TARGET_VENUE && (($ratingSummary['location'] ?? null) !== null || ($ratingSummary['amenities'] ?? null) !== null || ($ratingSummary['price'] ?? null) !== null))
                <dl class="mt-3 space-y-2 rounded-xl border border-zinc-200 bg-zinc-50/80 px-3 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900/40">
                    @foreach (['location' => 'Location', 'amenities' => 'Amenities', 'price' => 'Price / value'] as $dimKey => $dimLabel)
                        @if (($ratingSummary[$dimKey] ?? null) !== null)
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <dt class="font-medium text-zinc-600 dark:text-zinc-400">{{ $dimLabel }}</dt>
                                <dd class="inline-flex items-center gap-1.5 text-zinc-900 dark:text-zinc-100">
                                    @php
                                        $dimVal = (float) $ratingSummary[$dimKey];
                                    @endphp
                                    @for ($s = 1; $s <= 5; $s++)
                                        <x-app-icon
                                            name="star-solid"
                                            class="size-3.5 {{ $s <= (int) round($dimVal) ? 'text-amber-500 dark:text-amber-400' : 'text-zinc-300 dark:text-zinc-600' }}"
                                        />
                                    @endfor
                                    <span class="ml-1 text-xs font-semibold tabular-nums">{{ number_format($dimVal, 1) }}</span>
                                </dd>
                            </div>
                        @endif
                    @endforeach
                </dl>
            @endif
        </div>
    @endif

    @if (session('review_status'))
        <div
            class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-950 dark:border-emerald-900/50 dark:bg-emerald-950/40 dark:text-emerald-100"
            role="status"
        >
            {{ session('review_status') }}
        </div>
    @endif

    <div>
        <h3 class="text-sm font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">What players say</h3>
        @forelse ($approvedReviews as $rev)
            <div
                class="mt-3 border-b border-zinc-100 pb-4 dark:border-zinc-800"
                wire:key="ur-ap-{{ $rev->id }}"
            >
                <div class="flex flex-wrap items-center gap-2">
                    <span class="font-semibold text-zinc-900 dark:text-white">
                        {{ \App\Livewire\Reviews\UserReviewsPanel::authorDisplayName($rev->author) }}
                    </span>
                    @if ($rev->moderated_at)
                        <span class="text-xs text-zinc-400">{{ $rev->moderated_at->format('M j, Y') }}</span>
                    @endif
                </div>
                @if ($this->targetType === \App\Models\UserReview::TARGET_VENUE && $rev->rating_location !== null)
                    <dl class="mt-2 space-y-1.5 text-sm">
                        @foreach (['rating_location' => 'Location', 'rating_amenities' => 'Amenities', 'rating_price' => 'Price / value'] as $attr => $dimLabel)
                            @php
                                $dim = (int) $rev->{$attr};
                            @endphp
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <dt class="w-28 shrink-0 text-zinc-500 dark:text-zinc-400">{{ $dimLabel }}</dt>
                                <dd class="inline-flex items-center gap-0.5" aria-label="{{ $dim }} out of 5">
                                    @for ($s = 1; $s <= 5; $s++)
                                        <x-app-icon
                                            name="star-solid"
                                            class="size-3.5 {{ $s <= $dim ? 'text-amber-500 dark:text-amber-400' : 'text-zinc-300 dark:text-zinc-600' }}"
                                        />
                                    @endfor
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                @else
                    <div class="mt-1 inline-flex items-center gap-0.5 text-amber-600 dark:text-amber-400" aria-label="{{ $rev->rating }} out of 5 overall">
                        @for ($s = 1; $s <= 5; $s++)
                            <x-app-icon
                                name="star-solid"
                                class="size-4 {{ $s <= $rev->rating ? 'text-amber-500 dark:text-amber-400' : 'text-zinc-300 dark:text-zinc-600' }}"
                            />
                        @endfor
                    </div>
                @endif
                @if ($rev->body)
                    <p class="mt-2 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">{{ $rev->body }}</p>
                @endif
            </div>
        @empty
            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">No published reviews yet.</p>
        @endforelse
    </div>

    @auth
        @if ($pendingMine)
            <div
                class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-100"
                role="status"
            >
                <p class="font-semibold">Your review is pending moderation.</p>
                @if ($canSubmitReview && $showReviewForm)
                    <p class="mt-1 text-xs opacity-90">
                        You can update it below; it replaces your previous pending submission.
                    </p>
                @elseif ($canSubmitReview && ! $showReviewForm)
                    <p class="mt-1 text-xs opacity-90">
                        Review submissions are turned off right now; your pending review stays in the queue.
                    </p>
                @else
                    <p class="mt-1 text-xs opacity-90">
                        The submission window has closed; your pending review is still in the queue.
                    </p>
                @endif
            </div>
        @endif

        @if (! auth()->user()->usesStaffAppNav())
            @if ($canSubmitReview && $showReviewForm)
                <form wire:submit="submitReview" class="space-y-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900/80">
                    <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        Write a review — {{ $targetLabel }}
                    </p>
                    @if ($this->targetType === \App\Models\UserReview::TARGET_VENUE)
                        <div class="space-y-3">
                            <p class="text-xs font-medium text-zinc-600 dark:text-zinc-400">Rate this venue</p>
                            <div>
                                <label class="text-xs text-zinc-600 dark:text-zinc-400" for="ur-loc-{{ $targetId }}">Location</label>
                                <select
                                    wire:model="ratingLocation"
                                    id="ur-loc-{{ $targetId }}"
                                    class="mt-1 w-full max-w-xs rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                                >
                                    @foreach (range(5, 1) as $n)
                                        <option value="{{ $n }}">{{ $n }} — {{ $n === 5 ? 'Excellent' : ($n === 4 ? 'Good' : ($n === 3 ? 'OK' : ($n === 2 ? 'Poor' : 'Very poor'))) }}</option>
                                    @endforeach
                                </select>
                                @error('ratingLocation')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="text-xs text-zinc-600 dark:text-zinc-400" for="ur-amen-{{ $targetId }}">Amenities</label>
                                <select
                                    wire:model="ratingAmenities"
                                    id="ur-amen-{{ $targetId }}"
                                    class="mt-1 w-full max-w-xs rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                                >
                                    @foreach (range(5, 1) as $n)
                                        <option value="{{ $n }}">{{ $n }} — {{ $n === 5 ? 'Excellent' : ($n === 4 ? 'Good' : ($n === 3 ? 'OK' : ($n === 2 ? 'Poor' : 'Very poor'))) }}</option>
                                    @endforeach
                                </select>
                                @error('ratingAmenities')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="text-xs text-zinc-600 dark:text-zinc-400" for="ur-price-{{ $targetId }}">Price / value</label>
                                <select
                                    wire:model="ratingPrice"
                                    id="ur-price-{{ $targetId }}"
                                    class="mt-1 w-full max-w-xs rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                                >
                                    @foreach (range(5, 1) as $n)
                                        <option value="{{ $n }}">{{ $n }} — {{ $n === 5 ? 'Excellent' : ($n === 4 ? 'Good' : ($n === 3 ? 'OK' : ($n === 2 ? 'Poor' : 'Very poor'))) }}</option>
                                    @endforeach
                                </select>
                                @error('ratingPrice')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                Overall score sent to moderators is the average of the three ratings above.
                            </p>
                        </div>
                    @else
                        <div>
                            <label class="text-xs font-medium text-zinc-600 dark:text-zinc-400" for="ur-rating-{{ $targetId }}">
                                Rating
                            </label>
                            <select
                                wire:model="rating"
                                id="ur-rating-{{ $targetId }}"
                                class="mt-1 w-full max-w-xs rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                            >
                                @foreach (range(5, 1) as $n)
                                    <option value="{{ $n }}">{{ $n }} — {{ $n === 5 ? 'Excellent' : ($n === 4 ? 'Good' : ($n === 3 ? 'OK' : ($n === 2 ? 'Poor' : 'Very poor'))) }}</option>
                                @endforeach
                            </select>
                            @error('rating')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif
                    <div>
                        <label class="text-xs font-medium text-zinc-600 dark:text-zinc-400" for="ur-body-{{ $targetId }}">
                            Comment (optional)
                        </label>
                        <textarea
                            wire:model="body"
                            id="ur-body-{{ $targetId }}"
                            rows="3"
                            maxlength="2000"
                            class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                            placeholder="Share your experience…"
                        ></textarea>
                        @error('body')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    @error('review')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                    <button
                        type="submit"
                        class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600"
                    >
                        Submit for review
                    </button>
                </form>
            @elseif (! $canSubmitReview)
                <div
                    class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-700 dark:border-zinc-800 dark:bg-zinc-900/50 dark:text-zinc-300"
                    role="status"
                >
                    <p class="font-medium text-zinc-900 dark:text-zinc-100">Review window</p>
                    <p class="mt-1 text-xs leading-relaxed">
                        You can leave a review only after a confirmed or completed booking has ended
                        @if ($this->targetType === \App\Models\UserReview::TARGET_VENUE)
                            at this venue
                        @else
                            with this coach
                        @endif
                        , and within {{ $reviewWindowDays }} days after the booking ends.
                    </p>
                </div>
            @endif
        @endif
    @else
        @if ($showReviewForm)
            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                <a href="{{ route('login') }}" wire:navigate class="font-semibold text-emerald-600 hover:underline dark:text-emerald-400">Sign in</a>
                to leave a review after you’ve played here.
            </p>
        @endif
    @endauth
</div>
