<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class TwoFactorController extends Controller
{
    protected $twoFactorService;

    public function __construct(TwoFactorService $twoFactorService)
    {
        $this->twoFactorService = $twoFactorService;
    }

    /**
     * Show the 2FA setup page.
     * The generated secret is stored in the session (not in the HTML form) to
     * avoid exposing it to potential XSS on the setup page.
     */
    public function show(Request $request): View
    {
        $user = Auth::user();

        if ($user->hasFullyEnabledTwoFactor()) {
            return view('auth.two-factor.manage', compact('user'));
        }

        $secret = $this->twoFactorService->generateSecretKey();
        $qrCodeUrl = $this->twoFactorService->getQRCodeUrl($user, $secret);

        // Store secret in session — the form no longer sends it as a hidden field
        $request->session()->put('2fa_setup_secret', $secret);

        return view('auth.two-factor.setup', compact('user', 'qrCodeUrl', 'secret'));
    }

    /**
     * Enable 2FA for the user.
     * Reads the secret from the session rather than from a hidden form field.
     */
    public function enable(Request $request): RedirectResponse
    {
        $secret = $request->session()->get('2fa_setup_secret');

        if (!$secret) {
            return redirect()->route('two-factor.setup')
                ->withErrors(['code' => 'Setup session expired. Please start again.']);
        }

        $validator = Validator::make($request->all(), [
            'code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = Auth::user();

        // Temporarily set the secret on the model instance so verifyCode() can use it
        $user->two_factor_secret = $secret;

        if ($this->twoFactorService->enableTwoFactor($user, $secret, $request->code)) {
            $request->session()->forget('2fa_setup_secret');
            // Mark 2FA as verified for this session immediately after setup
            $request->session()->put('2fa_verified', true);
            return redirect()->route('profile.edit')->with('status', 'two-factor-enabled');
        }

        return back()->withErrors(['code' => 'Invalid verification code. Please try again.']);
    }

    /**
     * Disable 2FA for the user.
     */
    public function disable(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $user = Auth::user();

        if ($this->twoFactorService->disableTwoFactor($user, $request->code)) {
            $request->session()->forget('2fa_verified');
            return redirect()->route('profile.edit')->with('status', 'two-factor-disabled');
        }

        return back()->withErrors(['code' => 'Invalid verification code or backup code.']);
    }

    /**
     * Regenerate backup codes.
     */
    public function regenerateBackupCodes(): RedirectResponse
    {
        $user = Auth::user();

        if (!$user->two_factor_enabled) {
            return back()->withErrors(['error' => 'Two-factor authentication is not enabled.']);
        }

        $backupCodes = $this->twoFactorService->generateBackupCodes();
        $user->update(['two_factor_backup_codes' => json_encode($backupCodes)]);

        return back()->with('status', 'backup-codes-regenerated')
                    ->with('backup_codes', $backupCodes);
    }

    /**
     * Show backup codes.
     */
    public function showBackupCodes(): View
    {
        $user = Auth::user();
        $backupCodes = json_decode($user->two_factor_backup_codes ?? '[]', true);

        return view('auth.two-factor.backup-codes', compact('user', 'backupCodes'));
    }

    /**
     * Show the TOTP challenge page.
     */
    public function showChallenge(Request $request): View|RedirectResponse
    {
        if ($request->user() && $request->session()->has('2fa_verified')) {
            return redirect()->route('dashboard');
        }

        if ($request->user() && !$request->session()->has('2fa_pending_user')) {
            $request->session()->put('2fa_pending_user', $request->user()->id);
        }

        if (!$request->session()->has('2fa_pending_user')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor.challenge');
    }

    /**
     * Verify the TOTP code or backup code.
     */
    public function verifyChallenge(Request $request): RedirectResponse
    {
        $alreadyLoggedIn = (bool) $request->user();
        $userId = $request->session()->get('2fa_pending_user')
            ?? optional($request->user())->id;

        if (!$userId) {
            return redirect()->route('login');
        }

        $code = trim(str_replace(' ', '', $request->input('code', '') ?? ''));

        if (empty($code)) {
            return back()->withErrors(['code' => 'The code field is required.']);
        }

        $user = User::find($userId);

        if (!$user) {
            $request->session()->forget('2fa_pending_user');
            return redirect()->route('login');
        }

        // Normalise to uppercase so backup codes work regardless of input case
        $codeNormalised = strtoupper($code);

        $valid = $this->twoFactorService->verifyCode($user, $code)
            || $this->twoFactorService->verifyBackupCode($user, $codeNormalised);

        if (!$valid) {
            return back()->withErrors(['code' => 'The provided code is invalid. Please try again.']);
        }

        $request->session()->forget('2fa_pending_user');
        $request->session()->put('2fa_verified', true);

        if (!$alreadyLoggedIn) {
            $remember = $request->session()->pull('2fa_remember', false);
            Auth::loginUsingId($userId, $remember);
            $request->session()->regenerate();
        }

        return redirect()->intended(route('dashboard'));
    }
}
