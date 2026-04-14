<?php

namespace App\Livewire\Admin;

use App\Models\CourtClient;
use App\Models\User;
use App\Models\UserReview;
use App\Services\UserReviewAggregateService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::admin')]
#[Title('Review approvals')]
class ReviewApprovals extends Component
{
    public function approve(string $id): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $review = UserReview::query()->whereKey($id)->first();
        abort_unless($review !== null && $review->status === UserReview::STATUS_PENDING, 404);

        $review->forceFill([
            'status' => UserReview::STATUS_APPROVED,
            'moderated_by_user_id' => auth()->id(),
            'moderated_at' => now(),
        ])->save();

        if ($review->target_type === UserReview::TARGET_VENUE) {
            $client = CourtClient::query()->find($review->target_id);
            if ($client !== null) {
                UserReviewAggregateService::syncVenue($client);
            }
        } elseif ($review->target_type === UserReview::TARGET_COACH) {
            $coach = User::query()->find($review->target_id);
            if ($coach !== null) {
                UserReviewAggregateService::syncCoach($coach);
            }
        }
    }

    public function reject(string $id): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $review = UserReview::query()->whereKey($id)->first();
        abort_unless($review !== null && $review->status === UserReview::STATUS_PENDING, 404);

        $review->forceFill([
            'status' => UserReview::STATUS_REJECTED,
            'moderated_by_user_id' => auth()->id(),
            'moderated_at' => now(),
        ])->save();
    }

    public function render(): View
    {
        $pending = UserReview::query()
            ->pending()
            ->with('author')
            ->orderByDesc('created_at')
            ->get();

        return view('livewire.admin.review-approvals', [
            'pending' => $pending,
        ]);
    }
}
