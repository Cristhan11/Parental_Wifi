<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Auth\ForgotPasswordSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(Request $request): View
    {
        ForgotPasswordSession::forgetPendingEmail($request);

        return view('auth.forgot-password');
    }

    /**
     * Email a confirmation number for eligible accounts (same response whether or not the email exists).
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        ForgotPasswordSession::forget($request);

        $user = User::query()->where('email', $validated['email'])->first();

        if ($user && $user->canReceiveForgotPasswordResetCode()) {
            $user->sendPasswordResetCodeNotification();
            ForgotPasswordSession::putPendingEmail($request, $user->email);

            return redirect()
                ->route('password.forgot.verify')
                ->with(
                    'status',
                    'We sent a confirmation number to your email. Enter it below to continue.'
                );
        }

        ForgotPasswordSession::forgetPendingEmail($request);

        return back()->with(
            'status',
            'If that email is registered for an account that can reset online, we sent a message with a confirmation number. Check your inbox, then enter the number on the next step to set a new password.'
        );
    }
}
