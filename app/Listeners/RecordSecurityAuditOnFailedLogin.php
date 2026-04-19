<?php

namespace App\Listeners;

use App\Models\SecurityAuditEvent;
use App\Services\SecurityAuditLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Http\Request;

class RecordSecurityAuditOnFailedLogin
{
    public function __construct(
        private SecurityAuditLogger $logger,
    ) {}

    public function handle(Failed $event): void
    {
        if ($event->guard !== 'web') {
            return;
        }

        $request = request();
        if (! $request instanceof Request) {
            return;
        }

        $email = isset($event->credentials['email'])
            ? (string) $event->credentials['email']
            : null;

        $this->logger->record(
            SecurityAuditEvent::EVENT_LOGIN_FAILURE,
            $request,
            $event->user?->getAuthIdentifier(),
            $email !== '' ? $email : null,
            $request->route()?->getName(),
        );
    }
}
