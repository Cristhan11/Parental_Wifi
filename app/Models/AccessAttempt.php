<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Access Attempt Model
 * 
 * Logs security events when children try to access blocked websites
 * or visit flagged websites.
 */
class AccessAttempt extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'device_id',
        'type',
        'url',
        'domain',
        'ip_address',
        'attempted_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attempted_at' => 'datetime',
        ];
    }

    /**
     * Get the device that made this access attempt.
     * 
     * Relationship: belongsTo - One access attempt belongs to one device
     * 
     * Usage Example:
     * $attempt = AccessAttempt::find(1);
     * $device = $attempt->device; // Gets the Device that made this attempt
     * echo "Device '{$device->name}' attempted to access '{$attempt->url}'";
     * echo "Type: {$attempt->type}"; // "blocked_website" or "flagged_website"
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    /**
     * Check if this is a blocked website attempt.
     * 
     * Returns true if type is 'blocked_website' (access was denied)
     *
     * @return bool True if blocked website attempt, false otherwise
     * 
     * Usage Example:
     * $attempt = AccessAttempt::find(1);
     * if ($attempt->isBlockedWebsiteAttempt()) {
     *     echo "Child tried to access blocked site: {$attempt->url}";
     *     echo "Access was DENIED";
     *     // Notify parent via WebSocket
     *     broadcast(new BlockedWebsiteAccessed($attempt));
     * }
     */
    public function isBlockedWebsiteAttempt(): bool
    {
        return $this->type === 'blocked_website';
    }

    /**
     * Check if this is a flagged website visit.
     * 
     * Returns true if type is 'flagged_website' (access was allowed but logged)
     *
     * @return bool True if flagged website visit, false otherwise
     * 
     * Usage Example:
     * $attempt = AccessAttempt::find(1);
     * if ($attempt->isFlaggedWebsiteVisit()) {
     *     echo "Child visited flagged site: {$attempt->url}";
     *     echo "Access was ALLOWED but logged for parent review";
     *     // Notify parent via WebSocket
     *     broadcast(new FlaggedWebsiteVisited($attempt));
     * }
     */
    public function isFlaggedWebsiteVisit(): bool
    {
        return $this->type === 'flagged_website';
    }
}

