<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCourtAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        abort_unless($user->isCourtAdmin(), 403);
        abort_unless($user->administeredCourtClient !== null, 403, 'No venue is assigned to this account.');

        return $next($request);
    }
}
