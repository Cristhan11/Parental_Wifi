<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensure User Is Parent Middleware
 *
 * Purpose: Restricts access to routes to users with 'parent' role only.
 *
 * How It Works:
 * 1. Checks if user is logged in
 * 2. If not logged in → redirects to login page
 * 3. If logged in but not a parent → shows 403 error (Access Denied)
 * 4. If logged in and is a parent → allows request to continue
 *
 * Usage in Routes:
 * Route::get('/devices', ...)->middleware('role.parent');
 *
 * This middleware is registered in bootstrap/app.php with alias 'role.parent'
 *
 * Example Flow:
 * - Parent user visits /devices → ✅ Allowed (continues to controller)
 * - Admin user visits /devices → ❌ Blocked (403 error)
 * - Guest visits /devices → ❌ Redirected to login
 */
class EnsureUserIsParent
{
    /**
     * Handle an incoming request.
     *
     * This method is called automatically by Laravel when middleware is applied to a route
     *
     * Process:
     * 1. Check if user is authenticated (logged in)
     * 2. Check if user has 'parent' role
     * 3. Allow or block the request accordingly
     *
     * @param  Request  $request  The incoming HTTP request
     * @param  Closure  $next  The next middleware/controller in the chain
     * @return Response Either redirect, error, or continue to next handler
     *
     * How It Works:
     * - $next($request) continues to the next handler (controller)
     * - return redirect() stops execution and redirects
     * - abort() stops execution and shows error page
     */
    public function handle(Request $request, Closure $next): Response
    {
        // STEP 1: Check if user is logged in
        // auth()->check() returns true if user is authenticated, false if not
        // If user is not logged in, redirect them to login page
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        // STEP 2: Check if user has 'parent' role
        // auth()->user() gets the currently logged in user
        // isParent() is a method in User model that checks if role === 'parent'
        // If user is not a parent, show 403 Forbidden error
        if (! auth()->user()->hasParentCapability()) {
            abort(403, 'Access denied. Parent role required.');
        }

        // STEP 3: User is logged in AND is a parent
        // Allow request to continue to the controller
        // $next($request) passes the request to the next handler (the controller)
        return $next($request);
    }
}
