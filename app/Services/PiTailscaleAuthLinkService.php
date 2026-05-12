<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

class PiTailscaleAuthLinkService
{
    /**
     * Request current auth-link status from Pi local agent.
     *
     * @param  bool  $forceReauth  When true, the Pi signs out of Tailscale first, then returns a fresh sign-in URL (email-change flow).
     * @param  string|null  $dashboardEmail  When non-null, Pi compares Tailscale login to this email; mismatch triggers logout + sign-in URL.
     * @return array{
     *   ok: bool,
     *   status: string,
     *   auth_url: string|null,
     *   expires_at: string|null,
     *   message: string
     * }
     */
    public function fetchAuthLink(bool $forceReauth = false, ?string $dashboardEmail = null): array
    {
        $baseUrl = rtrim((string) config('pi_agent.base_url'), '/');
        $token = (string) config('pi_agent.token');
        $timeout = max(1, (int) config('pi_agent.timeout_seconds', 8));

        if ($baseUrl === '' || $token === '') {
            return [
                'ok' => false,
                'status' => 'error',
                'auth_url' => null,
                'expires_at' => null,
                'message' => 'Pi helper service is not configured.',
            ];
        }

        $body = [
            'force_reauth' => $forceReauth,
        ];
        if ($dashboardEmail !== null && $dashboardEmail !== '') {
            $body['dashboard_email'] = $dashboardEmail;
        }

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->withHeaders(['X-Pi-Agent-Token' => $token])
                ->timeout($timeout)
                ->retry(1, 200)
                ->post($baseUrl.'/v1/tailscale/auth-link', $body);
        } catch (ConnectionException) {
            return [
                'ok' => false,
                'status' => 'unavailable',
                'auth_url' => null,
                'expires_at' => null,
                'message' => 'Pi helper service is unavailable.',
            ];
        } catch (Throwable) {
            return [
                'ok' => false,
                'status' => 'error',
                'auth_url' => null,
                'expires_at' => null,
                'message' => 'Failed to request Tailscale sign-in link.',
            ];
        }

        if (! $response->ok()) {
            return [
                'ok' => false,
                'status' => 'error',
                'auth_url' => null,
                'expires_at' => null,
                'message' => 'Pi helper service rejected the request.',
            ];
        }

        $payload = $response->json();
        $status = is_array($payload) ? (string) ($payload['status'] ?? '') : '';
        $message = is_array($payload) ? (string) ($payload['message'] ?? '') : '';
        $authUrl = is_array($payload) && isset($payload['auth_url']) ? (string) $payload['auth_url'] : null;
        $expiresAt = is_array($payload) && isset($payload['expires_at']) ? (string) $payload['expires_at'] : null;

        $allowedStatuses = ['already_authenticated', 'action_required', 'unavailable', 'error'];
        if (! in_array($status, $allowedStatuses, true)) {
            return [
                'ok' => false,
                'status' => 'error',
                'auth_url' => null,
                'expires_at' => null,
                'message' => 'Pi helper returned an invalid response.',
            ];
        }

        $validAuthUrl = $this->sanitizeAuthUrl($authUrl);

        return [
            'ok' => true,
            'status' => $status,
            'auth_url' => $status === 'action_required' ? $validAuthUrl : null,
            'expires_at' => $expiresAt !== '' ? $expiresAt : null,
            'message' => $message !== '' ? $message : $this->defaultMessageForStatus($status),
        ];
    }

    public function maskAuthUrl(?string $authUrl): ?string
    {
        if (! is_string($authUrl) || $authUrl === '') {
            return null;
        }

        $parts = parse_url($authUrl);
        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        $masked = $parts['scheme'].'://'.$parts['host'];
        if (isset($parts['path'])) {
            $masked .= $parts['path'];
        }

        return $masked.'?[masked]';
    }

    private function sanitizeAuthUrl(?string $authUrl): ?string
    {
        if (! is_string($authUrl) || $authUrl === '') {
            return null;
        }

        if (! filter_var($authUrl, FILTER_VALIDATE_URL)) {
            return null;
        }

        $parts = parse_url($authUrl);
        if (! is_array($parts)) {
            return null;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        if ($scheme !== 'https' || $host !== 'login.tailscale.com') {
            return null;
        }

        return $authUrl;
    }

    private function defaultMessageForStatus(string $status): string
    {
        return match ($status) {
            'already_authenticated' => 'Raspberry Pi is already signed in to Tailscale.',
            'action_required' => 'Open the link to complete Tailscale sign-in for the Raspberry Pi.',
            'unavailable' => 'Pi helper service is unavailable.',
            default => 'Tailscale sign-in status is unavailable right now.',
        };
    }
}
