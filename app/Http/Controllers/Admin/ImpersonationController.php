<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    public function store(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();

        abort_unless($actor->isSuperAdmin(), 403);
        abort_if($request->session()->has('impersonator_id'), 403);
        abort_if($user->id === $actor->id, 403);

        abort_if($user->isSuperAdmin(), 403);

        ActivityLogger::log(
            'impersonation.started',
            [
                'target_user_id' => $user->id,
                'target_email' => $user->email,
            ],
            $user,
            "{$actor->name} started impersonating {$user->name}",
            actorUserId: $actor->id,
        );

        $request->session()->put('impersonator_id', $actor->id);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home')->with('status', "Viewing as {$user->name}.");
    }

    public function destroy(Request $request): RedirectResponse
    {
        $impersonatorId = $request->session()->pull('impersonator_id');

        abort_unless($impersonatorId, 403);

        $super = User::query()->find($impersonatorId);

        abort_unless($super && $super->isSuperAdmin(), 403);

        $impersonated = Auth::user();

        ActivityLogger::log(
            'impersonation.ended',
            [
                'impersonated_user_id' => $impersonated->id,
                'impersonated_email' => $impersonated->email,
            ],
            $impersonated,
            "Stopped impersonating {$impersonated->name}",
            actorUserId: $super->id,
        );

        Auth::login($super);
        $request->session()->regenerate();

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'You are back in your super admin account.');
    }
}
