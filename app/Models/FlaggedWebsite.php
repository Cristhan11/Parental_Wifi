<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Flagged Website Model
 * 
 * Stores websites that parents want to monitor/flag (not block).
 */
class FlaggedWebsite extends Model
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
     * Get the device this flagged website belongs to.
     * 
     * Relationship: belongsTo - One flagged website belongs to one device
     * 
     * Usage Example:
     * $flagged = FlaggedWebsite::find(1);
     * $device = $flagged->device; // Gets the Device this site is flagged for
     * echo "Website '{$flagged->url}' is flagged (monitored) for device: {$device->name}";
     * 
     * // When child visits this site, allow access but log it and notify parent
     * // Flagged sites are allowed but monitored, unlike blocked sites
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}

