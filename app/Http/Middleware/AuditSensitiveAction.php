<?php

namespace App\Http\Middleware;

use App\Services\SecurityAuditLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditSensitiveAction
{
    /**
     * Routes already covered by auth security listeners or high-frequency noise.
     *
     * @var list<string>
     */
    private const SKIP_ROUTE_NAMES = [
        'logout',
        'verification.send',
        'verification.verify',
    ];

    public function __construct(
        private SecurityAuditLogger $logger,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $response;
        }

        if ($request->user() === null) {
            return $response;
        }

        $routeName = $request->route()?->getName();
        if ($routeName !== null && in_array($routeName, self::SKIP_ROUTE_NAMES, true)) {
            return $response;
        }

        if ($response->getStatusCode() >= 400) {
            return $response;
        }

        $this->logger->recordSensitiveAction($request, [
            'method' => $request->method(),
        ]);

        return $response;
    }
}
