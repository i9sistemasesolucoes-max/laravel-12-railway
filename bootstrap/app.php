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
        // Isso remove a barreira de segurança que está causando o erro 500
        $middleware->validateCsrfTokens(except: [
            'api/nfe/*', 
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
