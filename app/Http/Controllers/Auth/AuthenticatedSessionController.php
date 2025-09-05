<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Models\User;
use App\Notifications\TwoFactorCode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

use Mail;
use Swift_SmtpTransport;
use Swift_Mailer;
use App\Mail\VerificationMail;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    /*public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = User::where('email', $request->email)->first();

        if ($user) {
            try {
                // Generate a 6-digit code
                $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $user->two_factor_code = $code;
                $user->two_factor_expires_at = now()->addMinutes(15);
                $user->save();

                // Send the notification
                $user->notify(new TwoFactorCode($code));

                $details = [
                    'title' => 'Your verification code is '.$code,
                    'subject' => 'verification Code From Assettracker'
                ];

                \Mail::to($user->email)->send(new \App\Mail\ContactUsMail($details));

                Log::info('2FA code sent to user', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'code' => $code
                ]);

                return redirect()->route('two-factor.challenge')
                    ->with('status', 'Verification code has been sent to your email. Please check your inbox.');
            } catch (\Exception $e) {
                Log::error('Failed to send 2FA code', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $e->getMessage()
                ]);

                return back()->withErrors([
                    'email' => 'Failed to send verification code. Please try again.',
                ]);
            }
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }*/

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ]);
        }

        Auth::login($user);
        return redirect()->route('dashboard');
    }

    public function verifyTwoFactor(Request $request)
    {
        $request->validate([
            'two_factor_code' => 'required|string|size:6',
        ]);

        $user = User::where('two_factor_code', $request->two_factor_code)
            ->where('two_factor_expires_at', '>', now())
            ->first();

        if (!$user) {
            return back()->withErrors([
                'two_factor_code' => 'The provided code is invalid or has expired.',
            ]);
        }

        $user->two_factor_code = null;
        $user->two_factor_expires_at = null;
        $user->save();

        Auth::login($user);

        return redirect()->route('dashboard');
    }

    public function resendTwoFactor(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return back()->withErrors(['email' => 'User not found.']);
        }

        try {
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $user->two_factor_code = $code;
            $user->two_factor_expires_at = now()->addMinutes(15);
            $user->save();

            $user->notify(new TwoFactorCode($code));

            $details = [
                'title' => 'Your verification code is '.$code,
                'subject' => 'verification Code From Assettracker'
            ];

            try {
                Mail::to($user->email)->send(new VerificationMail($details));
            } catch (\Exception $e) {
                Log::error('Mail sending failed (resend)', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $e->getMessage(),
                ]);
                return back()->withErrors([
                    'email' => 'Could not resend verification email. Try again shortly.',
                ]);
            }

            return redirect()->route('two-factor.challenge')
                ->with('status', 'A new verification code has been sent to your email.');
        } catch (\Exception $e) {
            Log::error('Failed to resend 2FA code', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);
            return back()->withErrors([
                'email' => 'Failed to resend verification code. Please try again.',
            ]);
        }
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
