<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Parent UI feedback for dnsmasq / household policy apply lifecycle.
 */
class PolicyApplyStatus implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  'applying'|'applied'|'failed'|'retry'  $state
     */
    public function __construct(
        public int $userId,
        public string $state,
        public string $message,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('user.'.$this->userId)];
    }

    public function broadcastAs(): string
    {
        return 'policy.apply.status';
    }

    public function broadcastWith(): array
    {
        return [
            'type' => 'policy_apply_status',
            'user_id' => $this->userId,
            'state' => $this->state,
            'message' => $this->message,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
