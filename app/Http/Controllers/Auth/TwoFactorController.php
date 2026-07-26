<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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
    public function show(Request $request): View|RedirectResponse
    {
        $user = Auth::user();

        if ($user->hasFullyEnabledTwoFactor()) {
            // Setup stays auth-only; send enrolled users to the verified manage route.
            if ($request->routeIs('two-factor.setup')) {
                return redirect()->route('two-factor.manage');
            }

            return view('auth.two-factor.manage', compact('user'));
        }

        if ($request->routeIs('two-factor.manage')) {
            return redirect()->route('two-factor.setup');
        }

        $secret = $this->twoFactorService->generateSecretKey();
        $qrCodeImage = $this->twoFactorService->getQRCodeImageHtml($user, $secret);

        // Store secret in session — the form no longer sends it as a hidden field
        $request->session()->put('2fa_setup_secret', $secret);

        return view('auth.two-factor.setup', compact('user', 'qrCodeImage', 'secret'));
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
            return back()->withErrors($validator, 'disable')->with('open_disable_form', true);
        }

        $user = Auth::user();
        $code = trim(str_replace(' ', '', $request->input('code', '') ?? ''));

        if ($this->twoFactorService->disableTwoFactor($user, $code)) {
            $request->session()->forget('2fa_verified');
            return redirect()->route('profile.edit')->with('status', 'two-factor-disabled');
        }

        return back()->withErrors(['code' => 'Invalid verification code or backup code.'], 'disable')
            ->with('open_disable_form', true);
    }

    /**
     * Regenerate backup codes.
     * Requires a current TOTP or backup code (same proof bar as disable).
     */
    public function regenerateBackupCodes(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator, 'regenerate')->with('open_regenerate_form', true);
        }

        $user = Auth::user();

        if (!$user->two_factor_enabled) {
            return back()->withErrors(['error' => 'Two-factor authentication is not enabled.'], 'regenerate')
                ->with('open_regenerate_form', true);
        }

        $code = trim(str_replace(' ', '', $request->input('code', '') ?? ''));
        $codeNormalised = strtoupper($code);

        $valid = $this->twoFactorService->verifyCode($user, $code)
            || $this->twoFactorService->verifyBackupCode($user, $codeNormalised);

        if (!$valid) {
            return back()->withErrors(['code' => 'Invalid verification code or backup code.'], 'regenerate')
                ->with('open_regenerate_form', true);
        }

        $backupCodes = $this->twoFactorService->generateBackupCodes();
        $user->update(['two_factor_backup_codes' => json_encode($backupCodes)]);

        return redirect()->route('two-factor.backup-codes')
            ->with('status', 'backup-codes-regenerated')
            ->with('backup_codes', $backupCodes);
    }

    /**
     * Show backup codes.
     */
    public function showBackupCodes(Request $request): View
    {
        $user = Auth::user();

        // Prefer freshly regenerated codes from the flash so the user always
        // sees the set that was just created (not a stale decoded copy).
        $backupCodes = $request->session()->get('backup_codes');
        if (! is_array($backupCodes)) {
            $decoded = json_decode($user->two_factor_backup_codes ?? '[]', true);
            $backupCodes = is_array($decoded) ? $decoded : [];
        }

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

        $throttleKey = $this->challengeThrottleKey($request, $userId);
        $this->ensureChallengeIsNotRateLimited($request, $throttleKey);

        $code = trim(str_replace(' ', '', $request->input('code', '') ?? ''));

        if (empty($code)) {
            return back()
                ->withInput($request->only('code'))
                ->withErrors(['code' => 'The code field is required.']);
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
            RateLimiter::hit($throttleKey);

            return back()
                ->withInput($request->only('code'))
                ->withErrors(['code' => 'The provided code is invalid. Please try again.']);
        }

        RateLimiter::clear($throttleKey);

        $request->session()->forget('2fa_pending_user');
        $request->session()->put('2fa_verified', true);

        if (!$alreadyLoggedIn) {
            $remember = $request->session()->pull('2fa_remember', false);
            Auth::loginUsingId($userId, $remember);
            $request->session()->regenerate();
        }

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Ensure the 2FA challenge is not rate limited (same 5-attempt bar as login).
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function ensureChallengeIsNotRateLimited(Request $request, string $throttleKey): void
    {
        if (! RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return;
        }

        event(new Lockout($request));

        $seconds = RateLimiter::availableIn($throttleKey);

        throw ValidationException::withMessages([
            'code' => __('Too many two-factor attempts. Please try again in :seconds seconds.', [
                'seconds' => $seconds,
            ]),
        ]);
    }

    /**
     * Rate-limit key for the TOTP/backup challenge (pending user + IP).
     */
    protected function challengeThrottleKey(Request $request, int|string $userId): string
    {
        return Str::transliterate('two-factor|'.$userId.'|'.$request->ip());
    }
}
