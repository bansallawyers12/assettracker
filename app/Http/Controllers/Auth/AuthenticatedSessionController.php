<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        // Authenticate (logs the user in via Auth::attempt)
        $request->authenticate();

        // Retrieve the now-authenticated user directly from the guard —
        // avoids a secondary DB lookup and works with encrypted email columns.
        $user = Auth::user();

        if ($user && $user->two_factor_enabled && $user->two_factor_secret) {
            // Log the user back OUT so they cannot bypass 2FA by navigating
            // directly to any auth-protected route before completing the challenge.
            Auth::guard('web')->logout();

            $request->session()->put('2fa_pending_user', $user->id);
            $request->session()->put('2fa_remember', $request->boolean('remember'));

            // Regenerate the session to prevent session fixation on the 2FA path.
            $request->session()->regenerate();

            return redirect()->route('two-factor.totp-challenge');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
