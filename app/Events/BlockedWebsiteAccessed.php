<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast when a device attempts to access a blocked website.
 *
 * Why this exists:
 * - Security alerts should appear in parent dashboard immediately (WebSockets).
 * - The same event is consumed by {@see \App\Listeners\SendImmediateBlockedWebsiteAlert} for optional SMTP alerts.
 */
class BlockedWebsiteAccessed implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $userId,
        public int $deviceId,
        public string $deviceName,
        public ?string $url,
        public ?string $domain
    ) {}

    // Scope the event to the owning parent.
    public function broadcastOn(): array
    {
        return [new PrivateChannel("user.{$this->userId}")];
    }

    // Alias expected by frontend notification listener.
    public function broadcastAs(): string
    {
        return 'website.blocked_accessed';
    }

    // url/domain may be null depending on how the attempt was captured.
    public function broadcastWith(): array
    {
        return [
            'type' => 'blocked_website_accessed',
            'user_id' => $this->userId,
            'device_id' => $this->deviceId,
            'device_name' => $this->deviceName,
            'url' => $this->url,
            'domain' => $this->domain,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}

