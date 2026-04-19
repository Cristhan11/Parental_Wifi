<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Use the browser's current host (and scheme) for generated URLs so the same
 * app works on LAN IP, Tailscale 100.x, MagicDNS, etc. without changing APP_URL.
 */
class ForceRootUrlFromRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        URL::forceRootUrl($request->getSchemeAndHttpHost());

        return $next($request);
    }
}
