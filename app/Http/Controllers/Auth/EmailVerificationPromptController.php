<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationPromptController extends Controller
{
    /**
     * Email verification is not required for this application. Users may still
     * land here from EnsureEmailIsVerified (e.g. cached routes on an older deploy)
     * or with a null email_verified_at row; mark verified and continue so login
     * cannot get stuck on this screen.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user && ! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
