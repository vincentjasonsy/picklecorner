<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockAdminWhileImpersonating
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->has('impersonator_id')) {
            return redirect()
                ->route('home')
                ->with('warning', 'Leave impersonation before opening the super admin area.');
        }

        return $next($request);
    }
}
