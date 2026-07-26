<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorEnrolled
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return redirect()->route('login');
        }

        if ($user->hasFullyEnabledTwoFactor()) {
            return $next($request);
        }

        $grace = max(0, (int) config('admin.two_factor_grace_logins', 3));
        $exceeded = ((int) $user->logins_without_two_factor_count) > $grace;

        if (! $exceeded) {
            return $next($request);
        }

        $route = $request->route()?->getName();

        $allowed = [
            'two-factor.setup',
            'two-factor.enable',
            'logout',
            'profile.edit',
            'profile.update',
            'password.confirm',
            'password.confirm.store',
        ];

        // Temporary: primary admin may still reach user-management routes from
        // grace-period redirects / future 2FA wrapping without being blocked here.
        // Admin routes currently omit 2fa.enrolled; remove this when enforcing admin 2FA.
        if ($user->isPrimaryAdministrator()) {
            $allowed = array_merge($allowed, [
                'admin.users.index',
                'admin.users.workspace',
                'admin.users.form.create',
                'admin.users.form.password',
                'admin.users.create',
                'admin.users.store',
                'admin.users.activate',
                'admin.users.deactivate',
                'admin.users.password',
                'admin.users.destroy',
            ]);
        }

        if ($route !== null && in_array($route, $allowed, true)) {
            return $next($request);
        }

        return redirect()->route('two-factor.setup')
            ->with('status', __('You must enable two-factor authentication to continue. You have exceeded the allowed logins without 2FA.'));
    }
}
