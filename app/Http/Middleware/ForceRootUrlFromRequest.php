<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Use the browser's current host (and scheme) for generated URLs so the same
 * app works on LAN IP, Tailscale 100.x, MagicDNS, etc. without changing APP_URL.
 *
 * When nginx/php-fpm passes a fixed LAN Host (e.g. captive AP IP) but the TCP
 * connection arrived on the Pi's Tailscale address (SERVER_ADDR in 100.64.0.0/10),
 * prefer SERVER_ADDR so redirects stay on the tailnet.
 */
class ForceRootUrlFromRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        URL::forceRootUrl($this->resolvePublicRoot($request));

        return $next($request);
    }

    private function resolvePublicRoot(Request $request): string
    {
        $scheme = $request->getScheme();
        $port = $request->getPort();
        $host = $request->getHost();
        $listen = (string) $request->server('SERVER_ADDR', '');

        if ($this->isTailscaleIpv4($listen) && ! $this->isTailscaleIpv4($host)) {
            $host = $listen;
        }

        $root = $scheme.'://'.$host;
        if (! (($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443))) {
            $root .= ':'.$port;
        }

        return $root;
    }

    private function isTailscaleIpv4(string $ip): bool
    {
        if ($ip === '' || ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }

        if (! str_starts_with($ip, '100.')) {
            return false;
        }

        $long = ip2long($ip);
        if ($long === false) {
            return false;
        }

        $cgNatStart = ip2long('100.64.0.0');
        $cgNatEnd = ip2long('100.127.255.255');

        return $long >= $cgNatStart && $long <= $cgNatEnd;
    }
}
