<?php

namespace App\Observers;

use App\Models\Device;
use App\Models\User;
use App\Services\DomainBlockingService;
use Illuminate\Support\Facades\Log;

/**
 * Keeps dnsmasq DHCP DNS bypass (split DNS) in sync when device role/status/MAC changes.
 */
class DeviceObserver
{
    public function __construct(
        protected DomainBlockingService $domainBlockingService
    ) {}

    public function created(Device $device): void
    {
        $this->syncForUser($device->user_id);
    }

    public function updated(Device $device): void
    {
        $this->syncForUser($device->user_id);
        if ($device->wasChanged('user_id') && $device->getOriginal('user_id')) {
            $this->syncForUser((int) $device->getOriginal('user_id'));
        }
    }

    public function deleted(Device $device): void
    {
        $this->syncForUser($device->user_id);
    }

    protected function syncForUser(?int $userId): void
    {
        if (! $userId) {
            return;
        }

        $user = User::query()->find($userId);
        if (! $user) {
            return;
        }

        try {
            $ok = $this->domainBlockingService->syncDnsmasqDhcpDnsBypassForUser($user);
            if (! $ok) {
                Log::warning('DHCP DNS bypass sync returned failure', ['user_id' => $userId]);
            }
        } catch (\Throwable $e) {
            Log::warning('DHCP DNS bypass sync exception', [
                'user_id' => $userId,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
