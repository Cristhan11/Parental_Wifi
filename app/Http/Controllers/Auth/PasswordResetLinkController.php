<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ParentPasswordResetRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Queue an admin-led default password reset for eligible parent accounts (no email link).
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();

        if ($user && $user->hasAdminCapability() && $user->hasVerifiedEmail()) {
            Password::sendResetLink(['email' => $validated['email']]);
        } elseif ($user?->isEligibleForSelfServicePasswordResetRequest()) {
            $alreadyPending = ParentPasswordResetRequest::query()
                ->where('user_id', $user->id)
                ->pending()
                ->exists();

            if (! $alreadyPending) {
                ParentPasswordResetRequest::create([
                    'user_id' => $user->id,
                ]);
            }
        }

        return back()->with(
            'status',
            'If that email is registered for a parent account, Parent Owners have been notified. After they reset your password, sign in with the credentials they give you and change your password under profile settings.'
        );
    }
}
