<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Admin\AdminParentAccountController;
use App\Http\Controllers\Controller;
use App\Services\SecurityAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Forced password change after an administrator reset a parent account to the default password.
 *
 * Reached when an authenticated user has `force_password_change = true` but is NOT an admin-capable
 * account (admins use the owner-onboarding flow). Once a new, non-default password is set the
 * flag is cleared and the user is redirected to their dashboard.
 */
class ForcePasswordChangeController extends Controller
{
    public function show(Request $request): RedirectResponse|View
    {
        $user = $request->user();

        if ($user === null || ! $user->force_password_change) {
            return redirect()->route('dashboard');
        }

        if ($user->hasAdminCapability()) {
            return redirect()->route('owner.onboarding.edit');
        }

        return view('auth.force-password-change');
    }

    public function update(Request $request, SecurityAuditLogger $auditLogger): RedirectResponse
    {
        $user = $request->user();

        if ($user === null || ! $user->force_password_change) {
            return redirect()->route('dashboard');
        }

        if ($user->hasAdminCapability()) {
            return redirect()->route('owner.onboarding.edit');
        }

        $validated = $request->validate([
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        if ($validated['password'] === AdminParentAccountController::DEFAULT_PARENT_RESET_PASSWORD) {
            throw ValidationException::withMessages([
                'password' => 'Choose a password different from the default reset password.',
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'force_password_change' => false,
        ])->save();

        $auditLogger->recordPasswordChanged($request, $user->fresh(), 'auth.force-password-change', [
            'via' => 'force_password_change',
        ]);

        return redirect()->route('dashboard')->with('status', 'Password updated. You are now signed in with your new password.');
    }
}
