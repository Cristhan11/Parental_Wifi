<?php

namespace App\Listeners;

use App\Models\SecurityAuditEvent;
use App\Services\SecurityAuditLogger;
use Illuminate\Auth\Events\Logout;
use Illuminate\Http\Request;

class RecordSecurityAuditOnLogout
{
    public function __construct(
        private SecurityAuditLogger $logger,
    ) {}

    public function handle(Logout $event): void
    {
        if ($event->guard !== 'web') {
            return;
        }

        $request = request();
        if (! $request instanceof Request) {
            return;
        }

        $this->logger->record(
            SecurityAuditEvent::EVENT_LOGOUT,
            $request,
            $event->user->getAuthIdentifier(),
            null,
            $request->route()?->getName(),
        );
    }
}
