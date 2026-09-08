<?php

namespace App\Http\Middleware;

use App\Services\TwoFactorService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorVerified
{
    protected $twoFactorService;

    public function __construct(TwoFactorService $twoFactorService)
    {
        $this->twoFactorService = $twoFactorService;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('Unauthenticated.'),
                    'redirect' => route('login'),
                ], 401);
            }

            return redirect()->route('login');
        }

        if (! $this->twoFactorService->isTwoFactorRequired($user)) {
            return $next($request);
        }

        // Check if 2FA has been verified in this session
        if ($request->session()->has('2fa_verified')) {
            return $next($request);
        }

        $challengeUrl = route('two-factor.totp-challenge');

        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('Two-factor authentication is required to continue.'),
                'redirect' => $challengeUrl,
            ], 401);
        }

        if (! $request->session()->has('url.intended') && $request->isMethodSafe()) {
            $request->session()->put('url.intended', $request->fullUrl());
        }

        return redirect()->to($challengeUrl);
    }
}
