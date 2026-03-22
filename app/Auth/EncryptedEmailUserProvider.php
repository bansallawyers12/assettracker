<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Custom UserProvider that supports encrypted email fields.
 *
 * Because emails are stored as AES-256-CBC ciphertext (non-deterministic IV),
 * a direct SQL WHERE email = 'plaintext' will never match. Instead we look up
 * by the deterministic HMAC stored in email_hash, which is computed alongside
 * every encrypted email write in User::setAttribute().
 */
class EncryptedEmailUserProvider extends EloquentUserProvider
{
    /**
     * Retrieve a user by the given credentials, using email_hash for lookup.
     */
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        if (!isset($credentials['email'])) {
            return parent::retrieveByCredentials($credentials);
        }

        $emailHash = hash_hmac(
            'sha256',
            strtolower(trim((string) $credentials['email'])),
            config('app.key')
        );

        $query = $this->newModelQuery()->where('email_hash', $emailHash);

        return $query->first();
    }
}
