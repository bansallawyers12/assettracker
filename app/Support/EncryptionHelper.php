<?php

namespace App\Support;

use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/**
 * Shared helpers for Laravel APP_KEY rotation and encrypted attribute backfills.
 */
final class EncryptionHelper
{
    public static function looksLikeLaravelCiphertext(string $raw): bool
    {
        if (! str_starts_with($raw, 'eyJ')) {
            return false;
        }

        $payload = json_decode(base64_decode($raw, true) ?: '', true);

        return is_array($payload)
            && isset($payload['iv'], $payload['value'], $payload['mac']);
    }

    /**
     * Decrypt using APP_KEY plus APP_PREVIOUS_KEYS (via the Crypt facade).
     */
    public static function attemptDecrypt(string $raw): ?string
    {
        try {
            $value = Crypt::decrypt($raw);

            return is_string($value) ? $value : (string) $value;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Decrypt using only the current APP_KEY (ignores APP_PREVIOUS_KEYS).
     */
    public static function attemptDecryptWithCurrentKeyOnly(string $raw): ?string
    {
        try {
            $encrypter = new Encrypter(
                self::parseKey((string) config('app.key')),
                (string) config('app.cipher', 'AES-256-CBC')
            );

            return $encrypter->decryptString($raw);
        } catch (\Throwable) {
            return null;
        }
    }

    public static function previousKeyCount(): int
    {
        return count(array_filter(config('app.previous_keys', [])));
    }

    public static function currentKeyFingerprint(): string
    {
        $key = (string) config('app.key');

        return $key === '' ? '(not set)' : substr(hash('sha256', $key), 0, 12);
    }

    private static function parseKey(string $key): string
    {
        if (Str::startsWith($key, 'base64:')) {
            return base64_decode(Str::after($key, 'base64:'), true) ?: '';
        }

        return $key;
    }
}
