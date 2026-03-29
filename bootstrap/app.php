<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Convert empty form strings to null so nullable validation rules work correctly
        $middleware->convertEmptyStringsToNull();

        // Global middleware — runs on every request
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // Web group additions
        $middleware->appendToGroup('web', \App\Http\Middleware\PasswordSecurity::class);
        $middleware->appendToGroup('web', \App\Http\Middleware\EnsureAccountActive::class);

        // API group additions
        $middleware->appendToGroup('api', \App\Http\Middleware\RateLimitMiddleware::class);

        // Named middleware aliases
        $middleware->alias([
            '2fa.enrolled' => \App\Http\Middleware\EnsureTwoFactorEnrolled::class,
            '2fa.verified' => \App\Http\Middleware\TwoFactorVerified::class,
            'super.admin' => \App\Http\Middleware\EnsureSuperAdmin::class,
            'rate.limit'   => \App\Http\Middleware\RateLimitMiddleware::class,
            'password.security' => \App\Http\Middleware\PasswordSecurity::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
