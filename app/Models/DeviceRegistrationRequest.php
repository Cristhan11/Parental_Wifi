<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceRegistrationRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_name',
        'mac_address',
        'hostname',
        'ip_address',
        'request_source',
        'fingerprint',
        'status',
        'assigned_role',
        'seen_on_home_wifi',
        'requests_count',
        'last_requested_at',
    ];

    protected function casts(): array
    {
        return [
            'seen_on_home_wifi' => 'boolean',
            'last_requested_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
