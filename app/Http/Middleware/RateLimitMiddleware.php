<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class RateLimitMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $key = 'api'): Response
    {
        if (!config('security.rate_limiting.enabled', true)) {
            return $next($request);
        }

        $maxAttempts = $this->getMaxAttempts($key);
        $decayMinutes = $this->getDecayMinutes($key);

        $key = $this->resolveRequestSignature($request, $key);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return $this->buildResponse($key, $maxAttempts);
        }

        RateLimiter::hit($key, $decayMinutes * 60);

        $response = $next($request);

        return $this->addHeaders(
            $response,
            $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts)
        );
    }

    /**
     * Resolve request signature.
     */
    protected function resolveRequestSignature(Request $request, string $key): string
    {
        if ($user = $request->user()) {
            return $key . '|' . $user->getAuthIdentifier();
        }

        return $key . '|' . $request->ip();
    }

    /**
     * Get the maximum number of attempts for the given key.
     */
    protected function getMaxAttempts(string $key): int
    {
        return match ($key) {
            'login' => config('security.rate_limiting.login_attempts', 5),
            'password-reset' => config('security.rate_limiting.password_reset_attempts', 3),
            'api' => config('security.rate_limiting.api_requests_per_minute', 60),
            default => 60,
        };
    }

    /**
     * Get the number of minutes to decay the rate limiter.
     */
    protected function getDecayMinutes(string $key): int
    {
        return match ($key) {
            'login' => config('security.rate_limiting.login_decay_minutes', 15),
            'password-reset' => config('security.rate_limiting.password_reset_decay_minutes', 60),
            'api' => 1,
            default => 1,
        };
    }

    /**
     * Create a 'too many attempts' response.
     */
    protected function buildResponse(string $key, int $maxAttempts): Response
    {
        $retryAfter = RateLimiter::availableIn($key);

        return response()->json([
            'message' => 'Too many attempts. Please try again later.',
            'retry_after' => $retryAfter,
        ], 429)->header('Retry-After', $retryAfter);
    }

    /**
     * Add the limit header information to the given response.
     */
    protected function addHeaders(Response $response, int $maxAttempts, int $remainingAttempts): Response
    {
        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remainingAttempts,
        ]);

        return $response;
    }

    /**
     * Calculate the number of remaining attempts.
     */
    protected function calculateRemainingAttempts(string $key, int $maxAttempts): int
    {
        return max(0, $maxAttempts - RateLimiter::attempts($key));
    }
}
