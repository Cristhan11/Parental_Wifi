<?php

namespace App\Services;

use App\Models\SecurityAuditEvent;
use App\Models\User;
use App\Support\RequestSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class SecurityAuditLogger
{
    public function record(
        string $event,
        Request $request,
        ?int $userId = null,
        ?string $attemptedIdentifier = null,
        ?string $routeName = null,
        ?array $metadata = null,
    ): ?SecurityAuditEvent {
        try {
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
        } catch (Throwable $e) {
            Log::warning('security_audit.write_failed', [
                'event' => $event,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
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

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function recordPasswordChanged(Request $request, User $subjectUser, ?string $routeName = null, ?array $metadata = null): ?SecurityAuditEvent
    {
        return $this->record(
            SecurityAuditEvent::EVENT_PASSWORD_CHANGED,
            $request,
            $subjectUser->getKey(),
            null,
            $routeName ?? $request->route()?->getName(),
            $metadata,
        );
    }
}
