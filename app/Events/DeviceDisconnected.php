<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast when a tracked device goes offline.
 *
 * Why this exists:
 * - Parent can see disconnections in real time without refreshing dashboard.
 */
class DeviceDisconnected implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $userId,
        public int $deviceId,
        public string $deviceName,
        public string $macAddress
    ) {}

    // Restrict event stream to the parent who owns the device.
    public function broadcastOn(): array
    {
        return [new PrivateChannel("user.{$this->userId}")];
    }

    // Stable alias for Echo listeners.
    public function broadcastAs(): string
    {
        return 'device.disconnected';
    }

    // Include enough data for direct UI rendering.
    public function broadcastWith(): array
    {
        return [
            'type' => 'device_disconnected',
            'user_id' => $this->userId,
            'device_id' => $this->deviceId,
            'device_name' => $this->deviceName,
            'mac_address' => $this->macAddress,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}

