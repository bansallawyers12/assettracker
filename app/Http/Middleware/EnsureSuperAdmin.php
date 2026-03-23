<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || strcasecmp((string) $user->email, config('admin.email')) !== 0) {
            abort(Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
