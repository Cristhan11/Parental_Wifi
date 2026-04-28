<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ReportingRecipient;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class VerifyEmailCodeController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'regex:/^\d{6}$/'],
        ], [
            'code.regex' => 'The code must be exactly 6 digits.',
        ]);

        /** @var User $user */
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return $this->redirectAfterVerification($user);
        }

        if ($user->email_verification_code_hash === null || $user->email_verification_code_expires_at === null) {
            throw ValidationException::withMessages([
                'code' => 'No active verification code. Please request a new code.',
            ]);
        }

        if ($user->email_verification_code_expires_at->isPast()) {
            throw ValidationException::withMessages([
                'code' => 'This code has expired. Please request a new code.',
            ]);
        }

        if (! Hash::check($request->input('code'), $user->email_verification_code_hash)) {
            throw ValidationException::withMessages([
                'code' => 'That code is not valid. Check the email and try again.',
            ]);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        ReportingRecipient::firstOrCreate(
            ['user_id' => $user->id, 'email' => $user->email],
            ['label' => 'Owner verified email', 'is_enabled' => true]
        );

        $user->forceFill([
            'email_verification_code_hash' => null,
            'email_verification_code_expires_at' => null,
        ])->save();

        return $this->redirectAfterVerification($user->fresh());
    }

    private function redirectAfterVerification(User $user): RedirectResponse
    {
        if ($user->canAccessParentDashboard()) {
            return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
        }

        if ($user->hasParentCapability()) {
            return redirect()
                ->route('registration.pending-approval')
                ->with('status', 'Your email is verified. A Parent Owner will approve your account next.');
        }

        return redirect()->intended(route('admin.dashboard', absolute: false).'?verified=1');
    }
}
