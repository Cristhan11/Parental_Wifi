<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Optional one-line copy for {@see \App\Http\Middleware\AuditSensitiveAction} after a successful POST/PUT/PATCH/DELETE.
 */
final class AuditRequestSummary
{
    public const ATTRIBUTE = 'parent_audit_log_summary';

    public static function set(Request $request, string $message): void
    {
        $request->attributes->set(self::ATTRIBUTE, $message);
    }
}
