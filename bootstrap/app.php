<?php

use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\EnsureTwoFactorEnrolled;
use App\Http\Middleware\PasswordSecurity;
use App\Http\Middleware\RateLimitMiddleware;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\TwoFactorVerified;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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
        $middleware->append(SecurityHeaders::class);

        // Web group additions
        $middleware->appendToGroup('web', PasswordSecurity::class);
        $middleware->appendToGroup('web', EnsureAccountActive::class);
        // Invalidate other sessions after password change (all session drivers).
        $middleware->appendToGroup('web', AuthenticateSession::class);

        // API group additions
        $middleware->appendToGroup('api', RateLimitMiddleware::class);

        // Named middleware aliases
        $middleware->alias([
            '2fa.enrolled' => EnsureTwoFactorEnrolled::class,
            '2fa.verified' => TwoFactorVerified::class,
            'super.admin' => EnsureSuperAdmin::class,
            'rate.limit' => RateLimitMiddleware::class,
            'password.security' => PasswordSecurity::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->renderable(function (HttpExceptionInterface $exception, Request $request) {
            if ($exception->getStatusCode() !== 403 || $request->expectsJson()) {
                return null;
            }

            return response()->view('errors.403', ['exception' => $exception], 403);
        });
    })->create();
