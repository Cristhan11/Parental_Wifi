<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

/**
 * Registered User Controller
 * 
 * Purpose: Handles user registration functionality
 * 
 * NOTE: Public registration routes have been removed for security.
 * This controller is kept for future use in the dashboard's user management system,
 * where existing admins/parents can create new accounts.
 * 
 * Current Status:
 * - Not accessible via public routes (registration routes removed)
 * - Can be reused in dashboard user management feature
 * - Methods can be called from authenticated admin/parent controllers
 * 
 * This controller manages:
 * - Displaying the registration form (for future dashboard use)
 * - Processing registration form submissions
 * - Creating new user accounts with role assignment
 * - Automatically logging in users after registration (optional for dashboard use)
 * 
 * Original Flow (now disabled):
 * 1. User visits /register → create() method shows registration form
 * 2. User submits form → store() method processes registration
 * 3. User is created in database → automatically logged in → redirected to dashboard
 */
class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     * 
     * This method is called when user visits /register (GET request)
     * Simply returns the registration form view - no processing needed
     * 
     * @return View The registration form view
     * 
     * Usage:
     * Route: GET /register
     * View: resources/views/auth/register.blade.php
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     * 
     * This method is called when user submits the registration form (POST request)
     * 
     * Process:
     * 1. Validate form input (name, email, password, role)
     * 2. Create new user in database
     * 3. Hash password for security (never store plain text)
     * 4. Assign role (parent or admin)
     * 5. Fire Registered event (for email verification, etc.)
     * 6. Automatically log user in
     * 7. Redirect to dashboard
     *
     * @param Request $request The HTTP request containing form data
     * @return RedirectResponse Redirects to dashboard after successful registration
     * @throws \Illuminate\Validation\ValidationException If validation fails
     * 
     * Usage:
     * Route: POST /register
     * Form fields: name, email, password, password_confirmation, role
     */
    public function store(Request $request): RedirectResponse
    {
        // STEP 1: Validate form input
        // This ensures:
        // - name is required, is a string, max 255 characters
        // - email is required, is valid email format, is unique (not already in database)
        // - role must be either 'parent' or 'admin'
        // - password is required, matches password_confirmation, meets strength requirements
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'role' => ['required', 'string', 'in:parent,admin'], // Must be exactly 'parent' or 'admin'
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // STEP 2: Create new user in database
        // User::create() saves to 'users' table
        // Hash::make() converts plain password to secure hash (one-way encryption)
        // Role is taken from form, defaults to 'parent' if somehow not provided
        $user = User::create([
            'name' => $request->name,                                    // User's full name
            'email' => $request->email,                                  // User's email (unique)
            'password' => Hash::make($request->password),                // Hashed password (secure)
            'role' => $request->role ?? 'parent',                       // Role: 'parent' or 'admin'
        ]);

        // STEP 3: Fire Registered event
        // This event can trigger:
        // - Email verification email
        // - Welcome email
        // - Logging/analytics
        event(new Registered($user));

        // STEP 4: Automatically log user in
        // Auth::login() creates a session for the user
        // User is now "logged in" and can access protected pages
        Auth::login($user);

        // STEP 5: Redirect to dashboard
        // After successful registration, user goes to dashboard
        // absolute: false means relative URL (not full URL)
        return redirect(route('dashboard', absolute: false));
    }
}
