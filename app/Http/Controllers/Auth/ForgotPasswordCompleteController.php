<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SecurityAuditLogger;
use App\Support\Auth\ForgotPasswordSession;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class ForgotPasswordCompleteController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $userId = ForgotPasswordSession::validUserId($request);
        if ($userId === null) {
            ForgotPasswordSession::forget($request);

            return redirect()
                ->route('password.request')
                ->with('status', 'That password reset session expired. Go back to Forgot password and request a new confirmation code.');
        }

        $user = User::query()->find($userId);
        if ($user === null || ! $user->canReceiveForgotPasswordResetCode()) {
            ForgotPasswordSession::forget($request);

            return redirect()
                ->route('password.request')
                ->with('status', 'Start again from Forgot password.');
        }

        return view('auth.forgot-password-new', [
            'userEmail' => $user->email,
        ]);
    }

    public function store(Request $request, SecurityAuditLogger $auditLogger): RedirectResponse
    {
        $userId = ForgotPasswordSession::validUserId($request);
        if ($userId === null) {
            ForgotPasswordSession::forget($request);

            return redirect()
                ->route('password.request')
                ->with('status', 'That password reset session expired. Go back to Forgot password and request a new confirmation code.');
        }

        $user = User::query()->find($userId);
        if ($user === null || ! $user->canReceiveForgotPasswordResetCode()) {
            ForgotPasswordSession::forget($request);

            return redirect()
                ->route('password.request')
                ->with('status', 'Start again from Forgot password.');
        }

        $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user->forceFill([
            'password' => Hash::make($request->password),
            'remember_token' => Str::random(60),
            'password_reset_code_hash' => null,
            'password_reset_code_expires_at' => null,
        ])->save();

        event(new PasswordReset($user));

        $auditLogger->recordPasswordChanged($request, $user->fresh(), 'password.forgot.new.store', [
            'via' => 'forgot_password_code',
        ]);

        ForgotPasswordSession::forget($request);

        return redirect()
            ->route('login')
            ->with('status', 'Your password has been reset. You can sign in now.');
    }
}
