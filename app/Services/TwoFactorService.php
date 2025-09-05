<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorService
{
    protected $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /**
     * Generate a secret key for the user.
     */
    public function generateSecretKey(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    /**
     * Generate QR code URL for the user.
     */
    public function getQRCodeUrl(User $user, string $secret): string
    {
        $issuer = config('security.two_factor.issuer', config('app.name'));
        
        return $this->google2fa->getQRCodeUrl(
            $issuer,
            $user->email,
            $secret
        );
    }

    /**
     * Verify the 2FA code.
     */
    public function verifyCode(User $user, string $code): bool
    {
        if (!$user->two_factor_secret) {
            return false;
        }

        $window = config('security.two_factor.window', 1);
        
        return $this->google2fa->verifyKey(
            $user->two_factor_secret,
            $code,
            $window
        );
    }

    /**
     * Generate backup codes for the user.
     */
    public function generateBackupCodes(int $count = null): array
    {
        $count = $count ?? config('security.two_factor.backup_codes_count', 10);
        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(Str::random(8));
        }

        return $codes;
    }

    /**
     * Verify a backup code.
     */
    public function verifyBackupCode(User $user, string $code): bool
    {
        $backupCodes = json_decode($user->two_factor_backup_codes ?? '[]', true);
        
        if (in_array($code, $backupCodes)) {
            // Remove used backup code
            $backupCodes = array_values(array_filter($backupCodes, function($c) use ($code) {
                return $c !== $code;
            }));
            
            $user->update([
                'two_factor_backup_codes' => json_encode($backupCodes)
            ]);
            
            return true;
        }

        return false;
    }

    /**
     * Enable 2FA for a user.
     */
    public function enableTwoFactor(User $user, string $secret, string $code): bool
    {
        if (!$this->verifyCode($user, $code)) {
            return false;
        }

        $backupCodes = $this->generateBackupCodes();
        
        $user->update([
            'two_factor_secret' => $secret,
            'two_factor_backup_codes' => json_encode($backupCodes),
            'two_factor_enabled' => true,
        ]);

        return true;
    }

    /**
     * Disable 2FA for a user.
     */
    public function disableTwoFactor(User $user, string $code): bool
    {
        if (!$this->verifyCode($user, $code) && !$this->verifyBackupCode($user, $code)) {
            return false;
        }

        $user->update([
            'two_factor_secret' => null,
            'two_factor_backup_codes' => null,
            'two_factor_enabled' => false,
        ]);

        return true;
    }

    /**
     * Check if 2FA is required for the user.
     */
    public function isTwoFactorRequired(User $user): bool
    {
        return $user->two_factor_enabled && $user->two_factor_secret;
    }

    /**
     * Store a temporary 2FA verification.
     */
    public function storeTemporaryVerification(User $user, int $minutes = 30): string
    {
        $token = Str::random(32);
        $key = "2fa_verified_{$token}";
        
        Cache::put($key, $user->id, $minutes * 60);
        
        return $token;
    }

    /**
     * Verify a temporary 2FA verification.
     */
    public function verifyTemporaryVerification(string $token): ?User
    {
        $key = "2fa_verified_{$token}";
        $userId = Cache::get($key);
        
        if ($userId) {
            Cache::forget($key);
            return User::find($userId);
        }
        
        return null;
    }
}
