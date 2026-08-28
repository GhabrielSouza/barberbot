<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            \App\Http\Middleware\ForceJsonResponse::class,
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->alias([
            'verified'             => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'identify.tenant'      => \App\Http\Middleware\IdentifyTenant::class,
            'resolve.tenant.user'  => \App\Http\Middleware\ResolveTenantFromUser::class,
            'guest.api'            => \App\Http\Middleware\EnsureApiGuest::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
