<?php

namespace App\Support\Auth;

use Illuminate\Http\Request;

final class ForgotPasswordSession
{
    public const SESSION_KEY = 'forgot_password_reset';

    /** Email address for the in-progress forgot-password flow (code step without retyping email). */
    public const PENDING_EMAIL_KEY = 'forgot_password_pending_email';

    public static function putPendingEmail(Request $request, string $email): void
    {
        $request->session()->put(self::PENDING_EMAIL_KEY, $email);
    }

    public static function pendingEmail(Request $request): ?string
    {
        $value = $request->session()->get(self::PENDING_EMAIL_KEY);

        return is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : null;
    }

    public static function forgetPendingEmail(Request $request): void
    {
        $request->session()->forget(self::PENDING_EMAIL_KEY);
    }

    public static function put(Request $request, int $userId): void
    {
        $request->session()->put(self::SESSION_KEY, [
            'user_id' => $userId,
            'expires_at' => now()->addMinutes(30)->getTimestamp(),
        ]);
    }

    public static function validUserId(Request $request): ?int
    {
        $payload = $request->session()->get(self::SESSION_KEY);
        if (! is_array($payload) || ! isset($payload['user_id'], $payload['expires_at'])) {
            return null;
        }
        if ($payload['expires_at'] < now()->getTimestamp()) {
            return null;
        }

        return (int) $payload['user_id'];
    }

    public static function forget(Request $request): void
    {
        $request->session()->forget(self::SESSION_KEY);
    }
}
