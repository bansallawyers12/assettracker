<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorEnrolled
{
    /**
     * Redirect unenrolled users to the 2FA setup page.
     * This enforces 2FA for all users — no one can access the app
     * without first completing the authenticator app setup.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$user->two_factor_enabled || !$user->two_factor_secret) {
            return redirect()->route('two-factor.setup')
                ->with('status', 'Please set up two-factor authentication to secure your account before continuing.');
        }

        return $next($request);
    }
}
