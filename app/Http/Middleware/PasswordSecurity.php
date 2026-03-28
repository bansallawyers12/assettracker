<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
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
     * Routes where a new password is being set — strength rules apply here.
     */
    protected function isPasswordCreationRequest(Request $request): bool
    {
        $creationRoutes = [
            'admin.users.store',
            'password.update',
            'password.reset',
            'password.store',
        ];

        return in_array($request->route()?->getName(), $creationRoutes);
    }

    /**
     * Check if the request is related to password operations (for age-check purposes).
     */
    protected function isPasswordRelatedRequest(Request $request): bool
    {
        return $this->isPasswordCreationRequest($request) ||
               $request->has('current_password') ||
               $request->has('password_confirmation');
    }

    /**
     * Validate password strength.
     */
    protected function validatePasswordStrength(Request $request): void
    {
        if (!$request->has('password') || !$this->isPasswordCreationRequest($request)) {
            return;
        }

        $password = $request->input('password');
        $rules = $this->getPasswordRules();

        $validator = Validator::make(['password' => $password], ['password' => $rules]);

        if ($validator->fails()) {
            $exception = new ValidationException($validator);
            if ($request->route()?->getName() === 'password.update') {
                $exception->errorBag('updatePassword');
            }
            throw $exception;
        }
    }

    /**
     * Get password validation rules (aligned with security.passwords config).
     */
    protected function getPasswordRules(): array
    {
        return ['required', 'string', $this->buildPasswordRule()];
    }

    /**
     * Laravel Password rule so failure messages stay specific (length, mixed case, numbers, symbols).
     */
    protected function buildPasswordRule(): Password
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

            // Only exempt the profile edit / password update routes to avoid redirect loops
            $exemptRoutes = ['profile.edit', 'password.update', 'logout'];
            if (!in_array($request->route()?->getName(), $exemptRoutes)) {
                redirect()->route('profile.edit')
                    ->with('status', 'password-expired')
                    ->send();
                exit;
            }
        }
    }
}
