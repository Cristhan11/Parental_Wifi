<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\RegistrationStatusController;
use App\Http\Controllers\Auth\VerifyEmailCodeController;
use Illuminate\Support\Facades\Route;

/**
 * Authentication Routes
 *
 * This file defines all authentication-related routes (login, register, password reset, etc.)
 * Created automatically by Laravel Breeze
 *
 * Route Structure:
 * - Route::get() = Display a page (form)
 * - Route::post() = Process form submission
 * - ->name() = Gives route a name (used in redirects, links)
 * - ->middleware() = Code that runs before route handler
 *
 * Middleware Groups:
 * - 'guest' = Only accessible if user is NOT logged in
 * - 'auth' = Only accessible if user IS logged in
 */

// ============================================================================
// GUEST ROUTES - Only accessible to users who are NOT logged in
// ============================================================================
// If a logged-in user tries to access these, they'll be redirected to dashboard
Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store']);

    // LOGIN ROUTES
    // GET /login - Shows login form
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login'); // Can be referenced as route('login')

    // POST /login - Processes login form submission
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    // PASSWORD RESET ROUTES
    // GET /forgot-password - Shows "forgot password" form
    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    // POST /forgot-password - Sends password reset email
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    // GET /reset-password/{token} - Shows password reset form (token from email)
    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    // POST /reset-password - Processes password reset form
    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

// ============================================================================
// AUTHENTICATED ROUTES - Only accessible to users who ARE logged in
// ============================================================================
// If a guest tries to access these, they'll be redirected to login page
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('registration/pending-approval', [RegistrationStatusController::class, 'pendingApproval'])
        ->name('registration.pending-approval');

    Route::get('registration/account-rejected', [RegistrationStatusController::class, 'accountRejected'])
        ->name('registration.account-rejected');
});

Route::middleware('auth')->group(function () {
    // EMAIL VERIFICATION ROUTES
    // GET /verify-email - Shows email verification notice page
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    // POST /verify-email/code - Submit 6-digit code from email
    Route::post('verify-email/code', [VerifyEmailCodeController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('verification.verify');

    // POST /email/verification-notification - Resends verification code email
    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1') // Limit to 6 emails per minute
        ->name('verification.send');

    // PASSWORD CONFIRMATION ROUTES
    // GET /confirm-password - Shows password confirmation form (for sensitive actions)
    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    // POST /confirm-password - Verifies password confirmation
    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    // PASSWORD UPDATE ROUTE
    // PUT /password - Updates user's password (from profile settings)
    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    // LOGOUT ROUTE
    // POST /logout - Logs user out and destroys session
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
