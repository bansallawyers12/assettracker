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
            'two-factor.manage',
            'logout',
            'profile.edit',
            'profile.update',
        ];

        if ($user->isPrimaryAdministrator()) {
            $allowed[] = 'admin.users.create';
            $allowed[] = 'admin.users.store';
        }

        if ($route !== null && in_array($route, $allowed, true)) {
            return $next($request);
        }

        return redirect()->route('two-factor.setup')
            ->with('status', __('You must enable two-factor authentication to continue. You have exceeded the allowed logins without 2FA.'));
    }
}
