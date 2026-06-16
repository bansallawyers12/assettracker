<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

/**
 * Checks that the given ABN is not already taken by another business entity,
 * using the abn_hash column (encrypted ABN cannot use a direct SQL unique check).
 *
 * Usage:
 *   store : new UniqueAbnHash()
 *   update: new UniqueAbnHash($businessEntity->id)
 */
class UniqueAbnHash implements ValidationRule
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
            ->where('abn_hash', $hash);

        if ($this->excludeId !== null) {
            $query->where('id', '!=', $this->excludeId);
        }

        if ($query->exists()) {
            $fail('The ABN has already been taken.');
        }
    }
}
