<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Auth\ForgotPasswordSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ForgotPasswordVerifyCodeController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if (ForgotPasswordSession::pendingEmail($request) === null) {
            return redirect()
                ->route('password.request')
                ->with('status', 'Enter your email on Forgot password to receive a confirmation code.');
        }

        return view('auth.forgot-password-verify');
    }

    public function store(Request $request): RedirectResponse
    {
        $pendingEmail = ForgotPasswordSession::pendingEmail($request);
        if ($pendingEmail === null) {
            return redirect()
                ->route('password.request')
                ->with('status', 'Request a confirmation code again using your email on Forgot password.');
        }

        $request->validate([
            'code' => ['required', 'string', 'regex:/^\d{6}$/'],
        ], [
            'code.regex' => 'The confirmation number must be exactly 6 digits.',
        ]);

        $user = User::query()->where('email', $pendingEmail)->first();

        if (! $user || ! $user->canReceiveForgotPasswordResetCode()) {
            throw ValidationException::withMessages([
                'code' => 'That confirmation number is not valid. Check the message and try again.',
            ]);
        }

        if ($user->password_reset_code_hash === null || $user->password_reset_code_expires_at === null) {
            throw ValidationException::withMessages([
                'code' => 'No active reset code for this account. Request a new code from Forgot password.',
            ]);
        }

        if ($user->password_reset_code_expires_at->isPast()) {
            throw ValidationException::withMessages([
                'code' => 'This code has expired. Request a new code from Forgot password.',
            ]);
        }

        if (! Hash::check((string) $request->input('code'), $user->password_reset_code_hash)) {
            throw ValidationException::withMessages([
                'code' => 'That confirmation number is not valid. Check the message and try again.',
            ]);
        }

        $user->forceFill([
            'password_reset_code_hash' => null,
            'password_reset_code_expires_at' => null,
        ])->save();

        ForgotPasswordSession::forgetPendingEmail($request);
        ForgotPasswordSession::put($request, $user->id);

        return redirect()
            ->route('password.forgot.new')
            ->with('status', 'Confirmation number accepted. Choose a new password below.');
    }
}
