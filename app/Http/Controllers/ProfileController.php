<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Mail\ProfileEmailChangeCodeMail;
use App\Models\ReportingRecipient;
use App\Models\SecurityAuditEvent;
use App\Services\PiTailscaleAuthLinkService;
use App\Services\SecurityAuditLogger;
use App\Services\TailscaleDashboardUrlResolver;
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
        $user = $request->user();
        $resolver = app(TailscaleDashboardUrlResolver::class);
        $remoteDashboardUrl = $resolver->resolve() ?? config('reporting.email_dashboard_url');
        $remoteDashboardUrl = is_string($remoteDashboardUrl) && $remoteDashboardUrl !== '' ? $remoteDashboardUrl : null;

        return view('profile.edit', [
            'user' => $user,
            'remote_dashboard_url' => $remoteDashboardUrl,
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

        $request->validate([
            'force_reauth' => ['sometimes', 'boolean'],
            'sync_tailscale_with_dashboard' => ['sometimes', 'boolean'],
            'status_only' => ['sometimes', 'boolean'],
            'target_email' => ['sometimes', 'nullable', 'string', 'email'],
        ]);

        $statusOnly = $request->boolean('status_only');
        $forceReauth = ! $statusOnly && $request->boolean('force_reauth');
        $syncTailscale = ! $statusOnly && $request->boolean('sync_tailscale_with_dashboard') && ! $forceReauth;

        // The email-change flow passes target_email = the parent's *new* address before the profile
        // is saved. Trust it only if it matches the address they just confirmed via the 6-digit code
        // (ProfileEmailChangeSession::verifiedEmail). Anything else falls back to the saved email so a
        // malicious or stale client cannot point the Pi at an unrelated account.
        $targetEmailRequested = trim((string) $request->input('target_email', ''));
        $sessionVerified = (string) (ProfileEmailChangeSession::verifiedEmail($request) ?? '');
        $useTargetEmail = $targetEmailRequested !== ''
            && $sessionVerified !== ''
            && strcasecmp(ProfileEmailChangeSession::normalizeEmail($targetEmailRequested), $sessionVerified) === 0;

        if ($useTargetEmail) {
            $dashboardEmail = $targetEmailRequested;
        } elseif ($syncTailscale || $statusOnly || $forceReauth) {
            // Pass the saved email even on the force_reauth path so the Pi knows which account the
            // parent expects after the relogin and can return matches_dashboard=true once they finish.
            $dashboardEmail = trim((string) $user->email);
        } else {
            $dashboardEmail = null;
        }
        if ($dashboardEmail === '') {
            $dashboardEmail = null;
        }

        $result = $service->fetchAuthLink($forceReauth, $dashboardEmail, $statusOnly);
        $maskedUrl = $service->maskAuthUrl($result['auth_url']);

        // After any Tailscale state change (logout + new login, or successful action_required URL
        // issued), the Pi's Tailscale IPv4 may differ from what we cached for reporting emails.
        // Bust the cache so the next digest re-detects via `tailscale ip -4`.
        if (! $statusOnly && in_array($result['status'] ?? '', ['action_required', 'already_authenticated'], true)) {
            app(TailscaleDashboardUrlResolver::class)->forget();
        }

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
                'force_reauth' => $forceReauth,
                'sync_tailscale_with_dashboard' => $syncTailscale,
                'status_only' => $statusOnly,
                'used_target_email' => $useTargetEmail,
            ],
        );

        $payload = [
            'status' => $result['status'],
            'ok' => $result['ok'],
            'message' => $result['message'],
            'auth_url' => $result['auth_url'],
            'expires_at' => $result['expires_at'],
            'force_reauth' => $forceReauth,
            'sync_tailscale_with_dashboard' => $syncTailscale,
            'status_only' => $statusOnly,
            'used_target_email' => $useTargetEmail,
            'signed_in_as' => $result['signed_in_as'] ?? null,
            'matches_dashboard' => $result['matches_dashboard'] ?? null,
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
