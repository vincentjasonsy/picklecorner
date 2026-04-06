<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureVenuePremiumSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        $client = $user->administeredCourtClient;

        if ($client === null) {
            abort(403);
        }

        if ($client->hasPremiumSubscription()) {
            return $next($request);
        }

        return redirect()
            ->route('venue.plan')
            ->with('status', 'Gift cards and customer CRM are part of Premium. Upgrade your plan to unlock them.');
    }
}
