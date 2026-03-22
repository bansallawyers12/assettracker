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
        // Global middleware — runs on every request
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // Web group additions
        $middleware->appendToGroup('web', \App\Http\Middleware\PasswordSecurity::class);

        // API group additions
        $middleware->appendToGroup('api', \App\Http\Middleware\RateLimitMiddleware::class);

        // Named middleware aliases
        $middleware->alias([
            '2fa.enrolled' => \App\Http\Middleware\EnsureTwoFactorEnrolled::class,
            '2fa.verified' => \App\Http\Middleware\TwoFactorVerified::class,
            'rate.limit'   => \App\Http\Middleware\RateLimitMiddleware::class,
            'password.security' => \App\Http\Middleware\PasswordSecurity::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
