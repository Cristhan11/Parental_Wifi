<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast when a child device has exhausted its internet time.
 *
 * Why this exists:
 * - Parent needs immediate visibility that cutoff / portal redirect was triggered.
 */
class TimeExpired implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $userId,
        public int $deviceId,
        public string $deviceName,
        public string $macAddress
    ) {}

    // Use private per-user channel for parent-only notifications.
    public function broadcastOn(): array
    {
        return [new PrivateChannel("user.{$this->userId}")];
    }

    // Alias used by frontend listener.
    public function broadcastAs(): string
    {
        return 'time.expired';
    }

    // Payload intentionally minimal but sufficient for notification content.
    public function broadcastWith(): array
    {
        return [
            'type' => 'time_expired',
            'user_id' => $this->userId,
            'device_id' => $this->deviceId,
            'device_name' => $this->deviceName,
            'mac_address' => $this->macAddress,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}

