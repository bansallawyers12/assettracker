<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class PasswordSecurity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only apply to password-related requests
        if ($this->isPasswordRelatedRequest($request)) {
            $this->validatePasswordStrength($request);
            $this->checkPasswordAge($request);
        }

        return $next($request);
    }

    /**
     * Check if the request is related to password operations.
     */
    protected function isPasswordRelatedRequest(Request $request): bool
    {
        $passwordRoutes = [
            'password.update',
            'password.confirm',
            'register',
            'password.reset',
        ];

        return in_array($request->route()?->getName(), $passwordRoutes) ||
               $request->has('password') ||
               $request->has('current_password') ||
               $request->has('password_confirmation');
    }

    /**
     * Validate password strength.
     */
    protected function validatePasswordStrength(Request $request): void
    {
        if (!$request->has('password')) {
            return;
        }

        $password = $request->input('password');
        $rules = $this->getPasswordRules();

        $validator = Validator::make(['password' => $password], ['password' => $rules]);

        if ($validator->fails()) {
            abort(422, 'Password does not meet security requirements: ' . implode(', ', $validator->errors()->all()));
        }
    }

    /**
     * Get password validation rules.
     */
    protected function getPasswordRules(): array
    {
        $rules = ['required', 'string', 'min:' . config('security.passwords.min_length', 12)];

        if (config('security.passwords.require_uppercase', true)) {
            $rules[] = 'regex:/[A-Z]/';
        }

        if (config('security.passwords.require_lowercase', true)) {
            $rules[] = 'regex:/[a-z]/';
        }

        if (config('security.passwords.require_numbers', true)) {
            $rules[] = 'regex:/[0-9]/';
        }

        if (config('security.passwords.require_special_chars', true)) {
            $rules[] = 'regex:/[^A-Za-z0-9]/';
        }

        return $rules;
    }

    /**
     * Check if password needs to be changed due to age.
     */
    protected function checkPasswordAge(Request $request): void
    {
        if (!$request->user()) {
            return;
        }

        $user = $request->user();
        $maxAge = config('security.passwords.max_age_days', 90);

        if ($user->password_changed_at && 
            $user->password_changed_at->diffInDays(now()) > $maxAge) {
            
            // Force password change
            if ($request->route()?->getName() !== 'password.change') {
                abort(403, 'Password has expired. Please change your password.');
            }
        }
    }
}
