<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ConfirmablePasswordController extends Controller
{
    /**
     * Show the confirm password view.
     */
    public function show(Request $request): View
    {
        $redirect = $request->query('redirect');
        if (is_string($redirect) && $this->isSafeInternalRedirect($redirect)) {
            $request->session()->put('url.intended', $redirect);
        }

        return view('auth.confirm-password');
    }

    /**
     * Confirm the user's password.
     */
    public function store(Request $request): RedirectResponse
    {
        if (! Auth::guard('web')->validate([
            'email' => $request->user()->email,
            'password' => $request->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        $request->session()->put('auth.password_confirmed_at', time());

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function isSafeInternalRedirect(string $redirect): bool
    {
        $redirect = trim($redirect);

        if ($redirect === '' || str_contains($redirect, '\\') || str_contains($redirect, "\0")) {
            return false;
        }

        // Protocol-relative and scheme URLs must match this app origin.
        if (str_starts_with($redirect, '//') || preg_match('#^[a-z][a-z0-9+.-]*:#i', $redirect) === 1) {
            $appRoot = rtrim(URL::to('/'), '/');

            return str_starts_with($redirect, $appRoot.'/') || $redirect === $appRoot;
        }

        // Same-origin relative path only.
        return str_starts_with($redirect, '/') && ! str_starts_with($redirect, '//');
    }
}
