<?php

namespace App\Livewire\Reviews;

use App\Models\CourtClient;
use App\Models\User;
use App\Models\UserReview;
use App\Services\ProfanityChecker;
use App\Services\UserReviewEligibility;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

class UserReviewsPanel extends Component
{
    #[Locked]
    public string $targetType = UserReview::TARGET_VENUE;

    #[Locked]
    public string $targetId = '';

    /** When false, the parent page supplies the main section title (e.g. “Venue & reviews”). */
    #[Locked]
    public bool $showHeading = true;

    public int $rating = 5;

    /** Venue-only: dimension scores; overall {@see $rating} is derived as the rounded mean of these. */
    public int $ratingLocation = 5;

    public int $ratingAmenities = 5;

    public int $ratingPrice = 5;

    public string $body = '';

    public function mount(string $targetType, string $targetId, bool $showHeading = true): void
    {
        if (! in_array($targetType, [UserReview::TARGET_VENUE, UserReview::TARGET_COACH], true)) {
            abort(404);
        }
        if ($targetType === UserReview::TARGET_COACH) {
            $coach = User::query()->whereKey($targetId)->first();
            if ($coach === null || ! $coach->isCoach()) {
                abort(404);
            }
        }

        $this->targetType = $targetType;
        $this->targetId = $targetId;
        $this->showHeading = $showHeading;

        $pending = $this->pendingReviewForAuthUser();
        if ($pending !== null) {
            $this->rating = $pending->rating;
            $this->body = (string) ($pending->body ?? '');
            if ($this->targetType === UserReview::TARGET_VENUE) {
                $this->ratingLocation = (int) ($pending->rating_location ?? $pending->rating);
                $this->ratingAmenities = (int) ($pending->rating_amenities ?? $pending->rating);
                $this->ratingPrice = (int) ($pending->rating_price ?? $pending->rating);
            }
        }
    }

    public function submitReview(): void
    {
        if (! config('booking.public_review_form_enabled')) {
            throw ValidationException::withMessages([
                'review' => 'Review submissions are turned off right now.',
            ]);
        }

        $user = auth()->user();
        if ($user === null) {
            throw ValidationException::withMessages([
                'review' => 'Sign in to leave a review.',
            ]);
        }

        if ($user->usesStaffAppNav()) {
            throw ValidationException::withMessages([
                'review' => 'Staff accounts cannot submit public reviews from this form.',
            ]);
        }

        if (! UserReviewEligibility::maySubmitOrUpdate($user, $this->targetType, $this->targetId)) {
            throw ValidationException::withMessages([
                'review' => sprintf(
                    'Reviews are only available after your booking ends, and for %d days afterward.',
                    UserReviewEligibility::windowDays()
                ),
            ]);
        }

        $rules = [
            'body' => ['nullable', 'string', 'max:2000'],
        ];
        if ($this->targetType === UserReview::TARGET_VENUE) {
            $rules['ratingLocation'] = ['required', 'integer', 'min:1', 'max:5'];
            $rules['ratingAmenities'] = ['required', 'integer', 'min:1', 'max:5'];
            $rules['ratingPrice'] = ['required', 'integer', 'min:1', 'max:5'];
        } else {
            $rules['rating'] = ['required', 'integer', 'min:1', 'max:5'];
        }
        $this->validate($rules);

        $overallRating = $this->targetType === UserReview::TARGET_VENUE
            ? (int) round(($this->ratingLocation + $this->ratingAmenities + $this->ratingPrice) / 3)
            : $this->rating;

        $key = 'submit-review:'.$user->id;
        if (RateLimiter::tooManyAttempts($key, 10)) {
            throw ValidationException::withMessages([
                'review' => 'Too many attempts. Try again in a minute.',
            ]);
        }
        RateLimiter::hit($key, 60);

        $hasApproved = UserReview::query()
            ->where('user_id', $user->id)
            ->where('target_type', $this->targetType)
            ->where('target_id', $this->targetId)
            ->where('status', UserReview::STATUS_APPROVED)
            ->exists();

        if ($hasApproved) {
            throw ValidationException::withMessages([
                'review' => 'You already have an approved review for this profile.',
            ]);
        }

        $profanity = ProfanityChecker::containsProfanity($this->body);

        $pending = UserReview::query()
            ->where('user_id', $user->id)
            ->where('target_type', $this->targetType)
            ->where('target_id', $this->targetId)
            ->where('status', UserReview::STATUS_PENDING)
            ->first();

        $reviewPayload = [
            'rating' => $overallRating,
            'body' => $this->body !== '' ? $this->body : null,
            'profanity_flag' => $profanity,
        ];
        if ($this->targetType === UserReview::TARGET_VENUE) {
            $reviewPayload['rating_location'] = $this->ratingLocation;
            $reviewPayload['rating_amenities'] = $this->ratingAmenities;
            $reviewPayload['rating_price'] = $this->ratingPrice;
        }

        if ($pending !== null) {
            $pending->forceFill($reviewPayload)->save();
        } else {
            UserReview::query()->create(array_merge([
                'user_id' => $user->id,
                'target_type' => $this->targetType,
                'target_id' => $this->targetId,
                'status' => UserReview::STATUS_PENDING,
            ], $reviewPayload));
        }

        session()->flash('review_status', 'Thanks — your review was submitted and will appear after a quick check.');
    }

    protected function pendingReviewForAuthUser(): ?UserReview
    {
        if (! auth()->check()) {
            return null;
        }

        return UserReview::query()
            ->where('user_id', auth()->id())
            ->where('target_type', $this->targetType)
            ->where('target_id', $this->targetId)
            ->where('status', UserReview::STATUS_PENDING)
            ->first();
    }

    /**
     * @return Collection<int, UserReview>
     */
    protected function approvedReviews()
    {
        return UserReview::query()
            ->where('target_type', $this->targetType)
            ->where('target_id', $this->targetId)
            ->where('status', UserReview::STATUS_APPROVED)
            ->with('author')
            ->orderByDesc('moderated_at')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();
    }

    public function render(): View
    {
        $pendingMine = $this->pendingReviewForAuthUser();
        $user = auth()->user();
        $canSubmitReview = $user !== null
            && ! $user->usesStaffAppNav()
            && UserReviewEligibility::maySubmitOrUpdate($user, $this->targetType, $this->targetId);

        return view('livewire.reviews.user-reviews-panel', [
            'approvedReviews' => $this->approvedReviews(),
            'pendingMine' => $pendingMine,
            'canSubmitReview' => $canSubmitReview,
            'showReviewForm' => config('booking.public_review_form_enabled'),
            'reviewWindowDays' => UserReviewEligibility::windowDays(),
            'targetLabel' => $this->resolveTargetLabel(),
            'ratingSummary' => $this->resolveRatingSummary(),
        ]);
    }

    /**
     * @return array{average: float, count: int, location: ?float, amenities: ?float, price: ?float}|null
     */
    protected function resolveRatingSummary(): ?array
    {
        if ($this->targetType === UserReview::TARGET_VENUE) {
            $v = CourtClient::query()->find($this->targetId);
            if ($v === null || $v->public_rating_average === null) {
                return null;
            }

            return [
                'average' => (float) $v->public_rating_average,
                'count' => (int) $v->public_rating_count,
                'location' => $v->public_rating_location !== null ? (float) $v->public_rating_location : null,
                'amenities' => $v->public_rating_amenities !== null ? (float) $v->public_rating_amenities : null,
                'price' => $v->public_rating_price !== null ? (float) $v->public_rating_price : null,
            ];
        }

        $u = User::query()->with('coachProfile')->find($this->targetId);
        $avg = $u?->coachProfile?->public_rating_average;
        if ($u === null || $avg === null) {
            return null;
        }

        return [
            'average' => (float) $avg,
            'count' => (int) ($u->coachProfile->public_rating_count ?? 0),
        ];
    }

    protected function resolveTargetLabel(): string
    {
        if ($this->targetType === UserReview::TARGET_VENUE) {
            $v = CourtClient::query()->find($this->targetId);

            return $v?->name ?? 'Venue';
        }

        $u = User::query()->find($this->targetId);

        return $u?->name ?? 'Coach';
    }

    public static function authorDisplayName(?User $author): string
    {
        if ($author === null) {
            return 'Member';
        }
        $parts = preg_split('/\s+/', trim($author->name)) ?: [];

        return (string) ($parts[0] ?? 'Member');
    }
}
