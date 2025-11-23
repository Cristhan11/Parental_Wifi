<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensure User Is Admin Middleware
 * 
 * Purpose: Restricts access to routes to users with 'admin' role only.
 * 
 * How It Works:
 * 1. Checks if user is logged in
 * 2. If not logged in → redirects to login page
 * 3. If logged in but not an admin → shows 403 error (Access Denied)
 * 4. If logged in and is an admin → allows request to continue
 * 
 * Usage in Routes:
 * Route::get('/admin/settings', ...)->middleware('role.admin');
 * 
 * This middleware is registered in bootstrap/app.php with alias 'role.admin'
 * 
 * Example Flow:
 * - Admin user visits /admin/settings → ✅ Allowed (continues to controller)
 * - Parent user visits /admin/settings → ❌ Blocked (403 error)
 * - Guest visits /admin/settings → ❌ Redirected to login
 */
class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     * 
     * This method is called automatically by Laravel when middleware is applied to a route
     * 
     * Process:
     * 1. Check if user is authenticated (logged in)
     * 2. Check if user has 'admin' role
     * 3. Allow or block the request accordingly
     *
     * @param Request $request The incoming HTTP request
     * @param Closure $next The next middleware/controller in the chain
     * @return Response Either redirect, error, or continue to next handler
     */
    public function handle(Request $request, Closure $next): Response
    {
        // STEP 1: Check if user is logged in
        // auth()->check() returns true if user is authenticated
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        // STEP 2: Check if user has 'admin' role
        // auth()->user() gets the currently logged in user
        // isAdmin() is a method in User model that checks if role === 'admin'
        if (!auth()->user()->isAdmin()) {
            // Show 403 Forbidden error if user is not an admin
            abort(403, 'Access denied. Admin role required.');
        }

        // STEP 3: User is logged in AND is an admin
        // Allow request to continue to the controller
        return $next($request);
    }
}

