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

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Check if 2FA is required for this user
        if (!$this->twoFactorService->isTwoFactorRequired($user)) {
            return $next($request);
        }

        // Check if 2FA has been verified in this session
        if ($request->session()->has('2fa_verified')) {
            return $next($request);
        }

        // Check for temporary verification token
        if ($request->has('2fa_token')) {
            $verifiedUser = $this->twoFactorService->verifyTemporaryVerification($request->input('2fa_token'));
            if ($verifiedUser && $verifiedUser->id === $user->id) {
                $request->session()->put('2fa_verified', true);
                return $next($request);
            }
        }

        // Redirect to 2FA verification page
        return redirect()->route('2fa.verify');
    }
}
