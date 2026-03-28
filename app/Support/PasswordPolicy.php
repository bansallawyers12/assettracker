<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

/**
 * Single source of truth for application password complexity (config/security.php passwords.*).
 */
final class PasswordPolicy
{
    public static function rule(): Password
    {
        $rule = Password::min((int) config('security.passwords.min_length', 12));

        $reqUpper = config('security.passwords.require_uppercase', true);
        $reqLower = config('security.passwords.require_lowercase', true);

        if ($reqUpper && $reqLower) {
            $rule = $rule->mixedCase();
        } else {
            $extra = [];
            if ($reqUpper) {
                $extra[] = 'regex:/[A-Z]/';
            }
            if ($reqLower) {
                $extra[] = 'regex:/[a-z]/';
            }
            if ($extra !== []) {
                $rule = $rule->rules($extra);
            }
        }

        if (config('security.passwords.require_numbers', true)) {
            $rule = $rule->numbers();
        }

        if (config('security.passwords.require_special_chars', true)) {
            $rule = $rule->symbols();
        }

        return $rule;
    }
}
