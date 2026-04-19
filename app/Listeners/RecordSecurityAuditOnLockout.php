<?php

namespace App\Listeners;

use App\Models\SecurityAuditEvent;
use App\Services\SecurityAuditLogger;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\Request;

class RecordSecurityAuditOnLockout
{
    public function __construct(
        private SecurityAuditLogger $logger,
    ) {}

    public function handle(Lockout $event): void
    {
        $request = $event->request;
        if (! $request instanceof Request) {
            return;
        }

        $email = (string) $request->input('email', '');

        $this->logger->record(
            SecurityAuditEvent::EVENT_LOCKOUT,
            $request,
            null,
            $email !== '' ? $email : null,
            $request->route()?->getName(),
        );
    }
}
