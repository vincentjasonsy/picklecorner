<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureGiftCardStaffModuleEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(gift_card_staff_module_visible_to(), 403);

        return $next($request);
    }
}
