<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast when additional minutes are granted to a device.
 *
 * Why this exists:
 * - Parents can instantly see successful quiz/video rewards and new remaining time.
 */
class TimeGranted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $userId,
        public int $deviceId,
        public string $deviceName,
        public int $minutesGranted,
        public int $remainingMinutes,
        public string $source,
        public bool $isConnected = false,
        public ?string $activeSessionStartedAt = null,
        public ?string $activeSessionBillingAnchorAt = null,
        public ?string $ipAddress = null,
    ) {}

    // Parent-specific private channel.
    public function broadcastOn(): array
    {
        return [new PrivateChannel("user.{$this->userId}")];
    }

    // Event alias consumed by Echo on dashboard.
    public function broadcastAs(): string
    {
        return 'time.granted';
    }

    // Include grant source to explain why time changed.
    public function broadcastWith(): array
    {
        return [
            'type' => 'time_granted',
            'user_id' => $this->userId,
            'device_id' => $this->deviceId,
            'device_name' => $this->deviceName,
            'minutes_granted' => $this->minutesGranted,
            'remaining_minutes' => $this->remainingMinutes,
            'source' => $this->source,
            'is_connected' => $this->isConnected,
            'active_session_started_at' => $this->activeSessionStartedAt,
            'active_session_billing_anchor_at' => $this->activeSessionBillingAnchorAt,
            'ip_address' => $this->ipAddress,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}

