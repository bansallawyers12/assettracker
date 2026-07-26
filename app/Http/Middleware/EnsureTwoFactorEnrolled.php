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
        ];

        // Temporary: primary admin may manage users after grace without 2FA.
        // Remove this exception when enforcing admin 2FA properly.
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
