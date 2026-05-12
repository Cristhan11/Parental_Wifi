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
     * @param  string|null  $dashboardEmail  When non-null, Pi compares Tailscale login to this email (slower; optional profile action).
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
        $baseUrl = $this->normalizePiAgentBaseUrl(rtrim((string) config('pi_agent.base_url'), '/'));
        $token = (string) config('pi_agent.token');
        $needsExtendedWait = $forceReauth || ($dashboardEmail !== null && $dashboardEmail !== '');
        $timeout = $needsExtendedWait
            ? max(1, (int) config('pi_agent.timeout_seconds', 240))
            : max(1, (int) config('pi_agent.quick_timeout_seconds', 90));

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

        // PHP (max_execution_time) and nginx (fastcgi_read_timeout) often default to ~60s and end
        // the request before Guzzle's PI_AGENT_TIMEOUT_SECONDS elapses, which surfaces as
        // ConnectionException. Extend PHP's cap for this single outbound wait to the Pi agent.
        $wallClockCap = min(600, $timeout + 60);
        if (function_exists('set_time_limit')) {
            @set_time_limit($wallClockCap);
        }
        @ini_set('max_execution_time', (string) $wallClockCap);

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->withOptions([
                    // Guzzle honors HTTP_PROXY/HTTPS_PROXY from the environment; loopback must not go through a proxy.
                    'proxy' => false,
                ])
                ->withHeaders(['X-Pi-Agent-Token' => $token])
                ->timeout($timeout)
                ->post($baseUrl.'/v1/tailscale/auth-link', $body);
        } catch (ConnectionException) {
            return [
                'ok' => false,
                'status' => 'unavailable',
                'auth_url' => null,
                'expires_at' => null,
                'message' => 'Pi helper service is unavailable. Confirm the Pi agent is running, PI_AGENT_* in .env, and php artisan config:clear. If only the slow “match dashboard email” action fails, raise PI_AGENT_TIMEOUT_SECONDS and nginx/PHP limits; the main sign-in link uses a shorter timeout (PI_AGENT_QUICK_TIMEOUT_SECONDS).',
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

    /**
     * The Pi agent binds 127.0.0.1 only. If PI_AGENT_BASE_URL uses "localhost", PHP may resolve it to ::1
     * (IPv6) while nothing is listening there, which surfaces as ConnectionException from Laravel Http.
     */
    private function normalizePiAgentBaseUrl(string $baseUrl): string
    {
        if ($baseUrl === '') {
            return '';
        }

        $parts = parse_url($baseUrl);
        if (! is_array($parts) || ! isset($parts['host'])) {
            return $baseUrl;
        }

        if (strcasecmp((string) $parts['host'], 'localhost') !== 0) {
            return $baseUrl;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? 'http'));
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $path = (string) ($parts['path'] ?? '');
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#'.$parts['fragment'] : '';

        return $scheme.'://127.0.0.1'.$port.$path.$query.$fragment;
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
        if ($scheme !== 'https' || ! $this->isAllowedTailscaleLoginHost($host)) {
            return null;
        }

        return $authUrl;
    }

    /** Tailscale may use regional hosts such as login.us.tailscale.com. */
    private function isAllowedTailscaleLoginHost(string $host): bool
    {
        if ($host === 'login.tailscale.com') {
            return true;
        }

        return (bool) preg_match('/^login(?:\.[a-z0-9-]+)?\.tailscale\.com$/', $host);
    }

    private function defaultMessageForStatus(string $status): string
    {
        return match ($status) {
            'already_authenticated' => 'Raspberry Pi is already signed in to Tailscale.',
            'action_required' => 'Open the link to complete Tailscale sign-in for the Raspberry Pi.',
            'unavailable' => 'Pi helper service is unavailable. Confirm the Pi agent is running, PI_AGENT_* in .env, and php artisan config:clear. If only the slow “match dashboard email” action fails, raise PI_AGENT_TIMEOUT_SECONDS and nginx/PHP limits; the main sign-in link uses a shorter timeout (PI_AGENT_QUICK_TIMEOUT_SECONDS).',
            default => 'Tailscale sign-in status is unavailable right now.',
        };
    }
}
