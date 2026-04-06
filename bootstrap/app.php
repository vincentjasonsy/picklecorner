<?php

use App\Http\Middleware\BlockAdminWhileImpersonating;
use App\Http\Middleware\EnsureCourtAdmin;
use App\Http\Middleware\EnsureCourtClientDesk;
use App\Http\Middleware\EnsureDemoAccountValid;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\EnsureUserIsCoach;
use App\Http\Middleware\EnsureVenuePremiumSubscription;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'super_admin' => EnsureSuperAdmin::class,
            'court_admin' => EnsureCourtAdmin::class,
            'court_client_desk' => EnsureCourtClientDesk::class,
            'admin_not_impersonating' => BlockAdminWhileImpersonating::class,
            'demo.valid' => EnsureDemoAccountValid::class,
            'coach' => EnsureUserIsCoach::class,
            'venue_premium' => EnsureVenuePremiumSubscription::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
