<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TwoFactorController extends Controller
{
    protected $twoFactorService;

    public function __construct(TwoFactorService $twoFactorService)
    {
        $this->twoFactorService = $twoFactorService;
    }

    /**
     * Show the 2FA setup page.
     */
    public function show(): View
    {
        $user = Auth::user();
        
        if ($user->two_factor_enabled) {
            return view('auth.two-factor.manage', compact('user'));
        }

        $secret = $this->twoFactorService->generateSecretKey();
        $qrCodeUrl = $this->twoFactorService->getQRCodeUrl($user, $secret);

        return view('auth.two-factor.setup', compact('user', 'secret', 'qrCodeUrl'));
    }

    /**
     * Enable 2FA for the user.
     */
    public function enable(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'secret' => 'required|string',
            'code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = Auth::user();
        
        // Temporarily set the secret to verify the code
        $user->two_factor_secret = $request->secret;
        
        if ($this->twoFactorService->enableTwoFactor($user, $request->secret, $request->code)) {
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
}
