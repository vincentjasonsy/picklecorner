<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InternalTeamPlayReminderPreferencesController extends Controller
{
    public function unsubscribe(User $user): View
    {
        $user->forceFill(['internal_team_play_reminders_unsubscribed_at' => now()])->save();

        return view('internal-team-play-reminder.unsubscribed', [
            'user' => $user,
            'title' => 'Reminders turned off',
        ]);
    }

    public function resubscribe(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(403);
        }

        $user->forceFill(['internal_team_play_reminders_unsubscribed_at' => null])->save();

        $home = $user->usesStaffAppNav() && $user->staffAppHomeUrl() !== null
            ? $user->staffAppHomeUrl()
            : route('account.dashboard');

        return redirect()
            ->to($home)
            ->with('status', 'Booking reminders are back on. We will let you know when it is time to play again.');
    }
}
