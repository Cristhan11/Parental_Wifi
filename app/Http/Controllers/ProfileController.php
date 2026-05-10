<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Mail\ProfileEmailChangeCodeMail;
use App\Models\ReportingRecipient;
use App\Models\SecurityAuditEvent;
use App\Services\PiTailscaleAuthLinkService;
use App\Services\SecurityAuditLogger;
use App\Support\Auth\ProfileEmailChangeSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $previousAccountEmail = $user->email;

        $user->fill($request->validated());
        $emailChanged = $user->isDirty('email');

        if ($emailChanged) {
            $user->email_verified_at = null;
            $user->email_verification_code_hash = null;
            $user->email_verification_code_expires_at = null;
        }

        $user->save();

        if ($emailChanged) {
            ProfileEmailChangeSession::forgetAll($request);

            // Remove reporting row for the old verified address so extra recipients cannot be confused with the
            // account identity; {@see VerifyEmailCodeController} re-adds {@see ReportingRecipient::LABEL_OWNER_VERIFIED_EMAIL}.
            ReportingRecipient::query()
                ->where('user_id', $user->id)
                ->where('email', $previousAccountEmail)
                ->delete();

            $user->sendEmailVerificationNotification();

            return Redirect::route('verification.notice')->with('status', 'verification-code-sent');
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->canDeleteOwnAccount()) {
            return Redirect::route('profile.edit')->with(
                'profile_delete_blocked',
                __('Household operator / Parent Owner accounts cannot be deleted from profile settings.')
            );
        }

        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * Request a fresh Tailscale sign-in URL from the Pi local helper service.
     */
    public function tailscaleAuthLink(
        Request $request,
        PiTailscaleAuthLinkService $service,
        SecurityAuditLogger $auditLogger,
    ): RedirectResponse|JsonResponse {
        $user = $request->user();
        if (! $user || ! $user->hasParentCapability()) {
            abort(403, 'Access denied.');
        }

        $result = $service->fetchAuthLink();
        $maskedUrl = $service->maskAuthUrl($result['auth_url']);

        $auditLogger->record(
            SecurityAuditEvent::EVENT_TAILSCALE_AUTH_LINK_REQUEST,
            $request,
            $user->id,
            null,
            (string) $request->route()?->getName(),
            [
                'status' => $result['status'],
                'ok' => $result['ok'],
                'auth_url_masked' => $maskedUrl,
            ],
        );

        $payload = [
            'status' => $result['status'],
            'ok' => $result['ok'],
            'message' => $result['message'],
            'auth_url' => $result['auth_url'],
            'expires_at' => $result['expires_at'],
        ];

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json($payload);
        }

        return Redirect::route('profile.edit')->with('tailscale_auth_link', $payload);
    }

    /**
     * Send a 6-digit confirmation code to the proposed new profile email (session-backed).
     */
    public function sendProfileEmailChangeCode(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(403);
        }

        $validated = $request->validate([
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
        ]);

        $normalized = ProfileEmailChangeSession::normalizeEmail($validated['email']);
        $current = ProfileEmailChangeSession::normalizeEmail((string) $user->email);

        if ($normalized === $current) {
            throw ValidationException::withMessages([
                'email' => __('That is already your current sign-in email.'),
            ]);
        }

        if (ProfileEmailChangeSession::verifiedEmail($request) === $normalized) {
            return response()->json([
                'ok' => true,
                'already_verified' => true,
            ]);
        }

        $verified = ProfileEmailChangeSession::verifiedEmail($request);
        if ($verified !== null && $verified !== $normalized) {
            ProfileEmailChangeSession::forgetVerified($request);
        }

        ProfileEmailChangeSession::forgetPending($request);

        $code = (string) random_int(100000, 999999);
        ProfileEmailChangeSession::putPending(
            $request,
            $normalized,
            Hash::make($code),
            now()->addMinutes(60)->getTimestamp(),
        );

        Mail::to($normalized)->send(new ProfileEmailChangeCodeMail($user, $normalized, $code));

        return response()->json([
            'ok' => true,
            'already_verified' => false,
        ]);
    }

    /**
     * Confirm the pending profile email change code (session-backed).
     */
    public function verifyProfileEmailChangeCode(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(403);
        }

        $request->validate([
            'code' => ['required', 'string', 'regex:/^\d{6}$/'],
        ], [
            'code.regex' => __('The confirmation number must be exactly 6 digits.'),
        ]);

        $pending = ProfileEmailChangeSession::pending($request);
        if ($pending === null) {
            throw ValidationException::withMessages([
                'code' => __('No active confirmation code. Request a new code from the profile page.'),
            ]);
        }

        if (! Hash::check((string) $request->input('code'), $pending['code_hash'])) {
            throw ValidationException::withMessages([
                'code' => __('That confirmation number is not valid. Check the message and try again.'),
            ]);
        }

        ProfileEmailChangeSession::putVerified($request, $pending['email']);

        return response()->json([
            'ok' => true,
            'email' => $pending['email'],
        ]);
    }
}
