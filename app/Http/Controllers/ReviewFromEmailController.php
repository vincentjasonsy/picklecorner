<?php

namespace App\Http\Controllers;

use App\Models\CourtClient;
use App\Models\User;
use App\Models\UserReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewFromEmailController extends Controller
{
    public function __invoke(Request $request, User $user, string $target_type, string $target_id): RedirectResponse|View
    {
        if (! in_array($target_type, [UserReview::TARGET_VENUE, UserReview::TARGET_COACH], true)) {
            abort(404);
        }

        if ($target_type === UserReview::TARGET_COACH) {
            $coach = User::query()->whereKey($target_id)->first();
            if ($coach === null || ! $coach->isCoach()) {
                abort(404);
            }
        } else {
            CourtClient::query()->whereKey($target_id)->firstOrFail();
        }

        if (! $request->user()) {
            session(['url.intended' => $request->fullUrl()]);

            return redirect()->route('login');
        }

        if ($request->user()->getKey() !== $user->getKey()) {
            abort(403);
        }

        $targetLabel = $target_type === UserReview::TARGET_VENUE
            ? (CourtClient::query()->find($target_id)?->name ?? 'Venue')
            : (User::query()->find($target_id)?->name ?? 'Coach');

        return view('reviews.write-from-email', [
            'targetType' => $target_type,
            'targetId' => $target_id,
            'targetLabel' => $targetLabel,
        ]);
    }
}
