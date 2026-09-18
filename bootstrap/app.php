<?php

use App\Http\Middleware\EnsureApiGuest;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\IdentifyTenant;
use App\Http\Middleware\ResolveTenantFromUser;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            ForceJsonResponse::class,
        ]);

        $middleware->alias([
            'verified' => EnsureEmailIsVerified::class,
            'identify.tenant' => IdentifyTenant::class,
            'resolve.tenant.user' => ResolveTenantFromUser::class,
            'guest.api' => EnsureApiGuest::class,
        ]);

        // Select the tenant connection before implicit resource bindings run.
        $middleware->prependToPriorityList(
            SubstituteBindings::class,
            ResolveTenantFromUser::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
