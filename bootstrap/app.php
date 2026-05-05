<?php

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
            'session.timeout' => \App\Http\Middleware\AuthenticateSession::class,
            'permission'      => \App\Http\Middleware\CheckPermission::class,
            'role'            => \App\Http\Middleware\CheckRole::class,
            'org.scope'       => \App\Http\Middleware\EnforceOrgScope::class,
        ]);

        // Redirect unauthenticated requests to the named login route
        $middleware->redirectGuestsTo(fn () => route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
