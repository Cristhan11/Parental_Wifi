<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Blocked Website Model
 * 
 * Stores websites that parents want to block for specific devices.
 */
class BlockedWebsite extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'device_id',
        'url',
        'domain',
        'reason',
    ];

    /**
     * Get the device this blocked website belongs to.
     * 
     * Relationship: belongsTo - One blocked website belongs to one device
     * 
     * Usage Example:
     * $blocked = BlockedWebsite::find(1);
     * $device = $blocked->device; // Gets the Device this site is blocked for
     * echo "Website '{$blocked->url}' is blocked for device: {$device->name}";
     * 
     * // When child tries to access this site, block them and log access attempt
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}

