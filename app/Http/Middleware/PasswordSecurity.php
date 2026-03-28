<?php

namespace App\Http\Middleware;

use App\Support\PasswordPolicy;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
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
        if ($this->isPasswordCreationRequest($request)) {
            $this->validatePasswordStrength($request);
        }

        if ($redirect = $this->passwordExpiredRedirect($request)) {
            return $redirect;
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
            'password.store',
        ];

        return in_array($request->route()?->getName(), $creationRoutes);
    }

    /**
     * Validate password strength.
     */
    protected function validatePasswordStrength(Request $request): void
    {
        if (! $request->filled('password')) {
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
     * @return list<string|\Illuminate\Validation\Rules\Password>
     */
    protected function getPasswordRules(): array
    {
        return ['required', 'string', PasswordPolicy::rule()];
    }

    /**
     * Force password change when policy max age is exceeded (every request, not only password forms).
     */
    protected function passwordExpiredRedirect(Request $request): ?RedirectResponse
    {
        $user = $request->user();
        if (! $user) {
            return null;
        }

        $maxAge = (int) config('security.passwords.max_age_days', 90);
        if ($maxAge <= 0) {
            return null;
        }

        if (! $user->password_changed_at) {
            return null;
        }

        if ($user->password_changed_at->diffInDays(now()) <= $maxAge) {
            return null;
        }

        $routeName = $request->route()?->getName();
        $exemptRoutes = ['profile.edit', 'password.update', 'logout'];
        if (in_array($routeName, $exemptRoutes, true)) {
            return null;
        }

        return redirect()->route('profile.edit')
            ->with('status', 'password-expired');
    }
}
