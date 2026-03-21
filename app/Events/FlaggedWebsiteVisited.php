<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast when a device visits a flagged website.
 *
 * Why this exists:
 * - Flagged visits are warning-level events parents should see in real time (WebSockets).
 * - Also drives {@see \App\Listeners\SendImmediateFlaggedWebsiteAlert} for email when enabled.
 */
class FlaggedWebsiteVisited implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $userId,
        public int $deviceId,
        public string $deviceName,
        public ?string $url,
        public ?string $domain
    ) {}

    // Send only to the parent tied to the device.
    public function broadcastOn(): array
    {
        return [new PrivateChannel("user.{$this->userId}")];
    }

    // Alias used by Echo subscription in dashboard.
    public function broadcastAs(): string
    {
        return 'website.flagged_visited';
    }

    // Payload is normalized so UI can use domain first, then fallback to URL.
    public function broadcastWith(): array
    {
        return [
            'type' => 'flagged_website_visited',
            'user_id' => $this->userId,
            'device_id' => $this->deviceId,
            'device_name' => $this->deviceName,
            'url' => $this->url,
            'domain' => $this->domain,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}

