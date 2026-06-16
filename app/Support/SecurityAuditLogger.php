<?php

namespace App\Support;

use App\Models\BankAccount;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class SecurityAuditLogger
{
    public static function bankAccountNumberViewed(?User $user, BankAccount $account, string $context): void
    {
        if ($user === null || ! config('security.audit.enabled') || ! config('security.audit.log_sensitive_operations')) {
            return;
        }

        Log::channel('security')->info('bank_account_number_viewed', [
            'user_id' => $user->id,
            'bank_account_id' => $account->id,
            'account_name' => $account->account_name,
            'context' => substr($context, 0, 64),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
