<?php

namespace App\Models;

use App\Events\BlockedWebsiteAccessed;
use App\Events\FlaggedWebsiteVisited;
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
     * Realtime bridge for security events.
     *
     * Why in model hook:
     * - Any creation path (job/service/controller/script import) triggers here.
     * - Ensures websocket alerts are consistent no matter where attempts are created.
     */
    protected static function booted(): void
    {
        static::created(function (AccessAttempt $attempt): void {
            // Load device relation so we can resolve owning parent channel.
            $attempt->loadMissing('device');

            // No parent = no destination channel for private notifications.
            if (!$attempt->device || !$attempt->device->user_id) {
                return;
            }

            if ($attempt->type === 'blocked_website') {
                // High-severity alert: blocked access attempt.
                event(new BlockedWebsiteAccessed(
                    userId: $attempt->device->user_id,
                    deviceId: $attempt->device_id,
                    deviceName: $attempt->device->name,
                    url: $attempt->url,
                    domain: $attempt->domain
                ));
            }

            if ($attempt->type === 'flagged_website') {
                // Warning-level alert: flagged site was visited.
                event(new FlaggedWebsiteVisited(
                    userId: $attempt->device->user_id,
                    deviceId: $attempt->device_id,
                    deviceName: $attempt->device->name,
                    url: $attempt->url,
                    domain: $attempt->domain
                ));
            }
        });
    }

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

