<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks authenticated users with `force_password_change = true` from accessing protected pages
 * until they pick a new password.
 *
 * - Admin-capable users are redirected to the owner-onboarding flow (existing behavior).
 * - Parent users are redirected to the dedicated force-password-change page.
 *
 * The force-change route itself is exempt so the user can actually complete the change.
 */
class EnsurePasswordChanged
{
    /**
     * Route names that must remain reachable while a forced password change is pending.
     *
     * @var list<string>
     */
    private const EXEMPT_ROUTE_NAMES = [
        'logout',
        'owner.onboarding.edit',
        'owner.onboarding.update',
        'password.force-change',
        'password.force-change.update',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->force_password_change) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        if ($routeName !== null && in_array($routeName, self::EXEMPT_ROUTE_NAMES, true)) {
            return $next($request);
        }

        if ($user->hasAdminCapability()) {
            return redirect()->route('owner.onboarding.edit');
        }

        return redirect()->route('password.force-change');
    }
}
