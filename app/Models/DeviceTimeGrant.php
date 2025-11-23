<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Device Time Grant Model
 * 
 * Tracks when additional internet time is granted to a device
 * after successfully completing a quiz or watching a video.
 */
class DeviceTimeGrant extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'device_id',
        'minutes_granted',
        'source',
        'source_id',
        'granted_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'granted_at' => 'datetime',
        ];
    }

    /**
     * Get the device that received this time grant.
     * 
     * Relationship: belongsTo - One time grant belongs to one device
     * 
     * Usage Example:
     * $grant = DeviceTimeGrant::find(1);
     * $device = $grant->device; // Gets the Device model
     * echo $device->name; // "John's iPhone"
     * echo $grant->minutes_granted; // 15
     * echo $grant->source; // "quiz" or "video"
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}

