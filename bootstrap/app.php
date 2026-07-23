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
    ->withMiddleware(function (Middleware $middleware) {
        // Render (like most PaaS) terminates TLS at its edge and forwards plain
        // HTTP to the container, signalling the original scheme via
        // X-Forwarded-Proto. Without trusting that header Laravel believes the
        // request is http, so asset()/url()/route() emit http:// links on an
        // https page and the browser blocks them as mixed content.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'admin.auth' => \App\Http\Middleware\AdminAuth::class,
            'role'       => \App\Http\Middleware\RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
