<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class OwnerOnboardingController extends Controller
{
    public function edit(Request $request): View
    {
        abort_unless($request->user()->hasAdminCapability(), 403);

        return view('auth.owner-onboarding');
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasAdminCapability(), 403);

        $validated = $request->validate([
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $user->forceFill([
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $user->hasParentCapability() ? $user->role : \App\Models\User::ROLE_PARENT_ADMIN,
            'approved_at' => $user->approved_at ?? now(),
            'rejected_at' => null,
            'approval_rejection_note' => null,
            'requires_email_setup' => false,
            'force_password_change' => false,
            'email_verified_at' => null,
        ])->save();

        $user->sendEmailVerificationNotification();

        return redirect()
            ->route('verification.notice')
            ->with('status', 'Owner setup completed. Please verify your email to continue.');
    }
}
