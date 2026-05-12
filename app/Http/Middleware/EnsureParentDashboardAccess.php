<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Allows the parent dashboard only when the user has parent capability, verified email,
 * and admin approval. Pure admin users are redirected to the admin home.
 */
class EnsureParentDashboardAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        $user->upgradeLegacyOwnerToParentAdminIfEligible();
        $user = $user->fresh();

        if ($user->hasAdminCapability() && ($user->requires_email_setup || $user->force_password_change)) {
            return redirect()->route('owner.onboarding.edit');
        }

        if (! $user->hasAdminCapability() && $user->force_password_change) {
            return redirect()->route('password.force-change');
        }

        if ($user->canAccessParentDashboard()) {
            return $next($request);
        }

        if ($user->hasAdminCapability() && ! $user->hasParentCapability()) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->hasParentCapability()) {
            if ($user->rejected_at !== null) {
                return redirect()->route('registration.account-rejected');
            }

            if (! $user->hasVerifiedEmail()) {
                return redirect()->route('verification.notice');
            }

            return redirect()->route('registration.pending-approval');
        }

        abort(403, 'Access denied.');
    }
}
