<?php

namespace App\Listeners;

use App\Models\SecurityAuditEvent;
use App\Services\SecurityAuditLogger;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;

class RecordSecurityAuditOnLogin
{
    public function __construct(
        private SecurityAuditLogger $logger,
    ) {}

    public function handle(Login $event): void
    {
        if ($event->guard !== 'web') {
            return;
        }

        $request = request();
        if (! $request instanceof Request) {
            return;
        }

        $this->logger->record(
            SecurityAuditEvent::EVENT_LOGIN_SUCCESS,
            $request,
            $event->user->getAuthIdentifier(),
            null,
            $request->route()?->getName(),
            ['remember' => $event->remember],
        );
    }
}
