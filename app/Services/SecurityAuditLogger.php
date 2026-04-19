<?php

namespace App\Services;

use App\Models\SecurityAuditEvent;
use App\Support\RequestSource;
use Illuminate\Http\Request;

class SecurityAuditLogger
{
    public function record(
        string $event,
        Request $request,
        ?int $userId = null,
        ?string $attemptedIdentifier = null,
        ?string $routeName = null,
        ?array $metadata = null,
    ): SecurityAuditEvent {
        return SecurityAuditEvent::query()->create([
            'event' => $event,
            'user_id' => $userId,
            'attempted_identifier' => $attemptedIdentifier,
            'ip_address' => $request->ip() ?? '0.0.0.0',
            'user_agent' => $request->userAgent(),
            'is_remote' => RequestSource::isRemote($request),
            'route_name' => $routeName,
            'metadata' => $metadata,
        ]);
    }

    public function recordSensitiveAction(Request $request, ?array $metadata = null): ?SecurityAuditEvent
    {
        $user = $request->user();
        if ($user === null) {
            return null;
        }

        return $this->record(
            SecurityAuditEvent::EVENT_SENSITIVE_ACTION,
            $request,
            $user->getAuthIdentifier(),
            null,
            $request->route()?->getName(),
            $metadata,
        );
    }
}
