<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityAuditEvent extends Model
{
    public const EVENT_LOGIN_SUCCESS = 'login_success';

    public const EVENT_LOGIN_FAILURE = 'login_failure';

    public const EVENT_LOGOUT = 'logout';

    public const EVENT_LOCKOUT = 'lockout';

    public const EVENT_SENSITIVE_ACTION = 'sensitive_action';

    protected $fillable = [
        'event',
        'user_id',
        'attempted_identifier',
        'ip_address',
        'user_agent',
        'is_remote',
        'route_name',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_remote' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
