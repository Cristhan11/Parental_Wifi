<?php

namespace App\Support\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class ProfileEmailChangeSession
{
    private const PENDING = 'profile_email_change_pending';

    private const VERIFIED = 'profile_email_change_verified';

    public static function normalizeEmail(string $email): string
    {
        return Str::lower(trim($email));
    }

    /**
     * @return array{email: string, code_hash: string, expires_at: int}|null
     */
    public static function pending(Request $request): ?array
    {
        $payload = $request->session()->get(self::PENDING);
        if (! is_array($payload) || ! isset($payload['email'], $payload['code_hash'], $payload['expires_at'])) {
            return null;
        }
        if (! is_string($payload['email']) || ! is_string($payload['code_hash']) || ! is_int($payload['expires_at'])) {
            return null;
        }
        if ($payload['expires_at'] < now()->getTimestamp()) {
            self::forgetPending($request);

            return null;
        }

        return [
            'email' => $payload['email'],
            'code_hash' => $payload['code_hash'],
            'expires_at' => $payload['expires_at'],
        ];
    }

    public static function putPending(Request $request, string $normalizedEmail, string $codeHash, int $expiresAt): void
    {
        $request->session()->put(self::PENDING, [
            'email' => $normalizedEmail,
            'code_hash' => $codeHash,
            'expires_at' => $expiresAt,
        ]);
    }

    public static function forgetPending(Request $request): void
    {
        $request->session()->forget(self::PENDING);
    }

    public static function putVerified(Request $request, string $normalizedEmail): void
    {
        self::forgetPending($request);
        $request->session()->put(self::VERIFIED, [
            'email' => $normalizedEmail,
            'expires_at' => now()->addMinutes(30)->getTimestamp(),
        ]);
    }

    public static function verifiedEmail(Request $request): ?string
    {
        $payload = $request->session()->get(self::VERIFIED);
        if (! is_array($payload) || ! isset($payload['email'], $payload['expires_at'])) {
            return null;
        }
        if (! is_string($payload['email']) || ! is_int($payload['expires_at'])) {
            return null;
        }
        if ($payload['expires_at'] < now()->getTimestamp()) {
            self::forgetVerified($request);

            return null;
        }

        return self::normalizeEmail($payload['email']);
    }

    public static function forgetVerified(Request $request): void
    {
        $request->session()->forget(self::VERIFIED);
    }

    public static function forgetAll(Request $request): void
    {
        self::forgetPending($request);
        self::forgetVerified($request);
    }
}
