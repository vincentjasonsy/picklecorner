<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCourtClientDesk
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        abort_unless($user->isCourtClientDesk(), 403);
        abort_unless($user->desk_court_client_id !== null, 403, 'No venue is assigned to this desk account.');

        return $next($request);
    }
}
