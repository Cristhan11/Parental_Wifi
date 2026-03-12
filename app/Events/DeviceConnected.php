<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast when a tracked device comes online.
 *
 * Why this exists:
 * - Parent dashboard needs instant feedback when a child device reconnects.
 * - Polling every few seconds is unnecessary load compared to a push event.
 */
class DeviceConnected implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $userId,
        public int $deviceId,
        public string $deviceName,
        public string $macAddress,
        public ?string $ipAddress
    ) {}

    // Send only to the owning parent via a private user channel.
    public function broadcastOn(): array
    {
        return [new PrivateChannel("user.{$this->userId}")];
    }

    // Frontend listens using this event alias (e.g. .device.connected).
    public function broadcastAs(): string
    {
        return 'device.connected';
    }

    // Keep payload explicit so frontend does not need extra lookups.
    public function broadcastWith(): array
    {
        return [
            'type' => 'device_connected',
            'user_id' => $this->userId,
            'device_id' => $this->deviceId,
            'device_name' => $this->deviceName,
            'mac_address' => $this->macAddress,
            'ip_address' => $this->ipAddress,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}

