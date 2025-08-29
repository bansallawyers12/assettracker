<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureTwoFactorVerified
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if ($user && $user->two_factor_code) {
            // If the user has a 2FA code, redirect to the 2FA challenge
            return redirect()->route('two-factor.challenge')
                ->with('status', 'You must verify your login code before accessing this page.');
        }
        return $next($request);
    }
} 