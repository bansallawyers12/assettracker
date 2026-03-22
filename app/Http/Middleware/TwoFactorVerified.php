<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\TwoFactorService;
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

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$this->twoFactorService->isTwoFactorRequired($user)) {
            return $next($request);
        }

        // Check if 2FA has been verified in this session
        if ($request->session()->has('2fa_verified')) {
            return $next($request);
        }

        // Redirect to 2FA verification page
        return redirect()->route('two-factor.totp-challenge');
    }
}
