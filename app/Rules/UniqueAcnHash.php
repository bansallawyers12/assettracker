<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

/**
 * Checks that the given ACN is not already taken by another business entity,
 * using the acn_hash column (encrypted ACN cannot use a direct SQL unique check).
 *
 * Usage:
 *   store : new UniqueAcnHash()
 *   update: new UniqueAcnHash($businessEntity->id)
 */
class UniqueAcnHash implements ValidationRule
{
    public function __construct(private readonly ?int $excludeId = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $digits = preg_replace('/\D/', '', (string) $value);

        if ($digits === '') {
            return;
        }

        $hash = hash_hmac('sha256', $digits, config('app.key'));

        $query = DB::table('business_entities')
            ->where('acn_hash', $hash);

        if ($this->excludeId !== null) {
            $query->where('id', '!=', $this->excludeId);
        }

        if ($query->exists()) {
            $fail('The ACN has already been taken.');
        }
    }
}
