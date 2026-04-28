<?php

namespace App\Observers;

use App\Models\Device;
use App\PolicyApplyFlags;
use App\Services\PolicyApplyDebouncer;

/**
 * Queues debounced DHCP DNS bypass updates when device identity or role changes.
 * Block list regeneration is triggered from blocked-website changes only (lighter on the Pi).
 */
class DeviceObserver
{
    public function __construct(
        protected PolicyApplyDebouncer $policyApplyDebouncer
    ) {}

    public function created(Device $device): void
    {
        $this->requestBypassForUser($device->user_id);
    }

    public function updated(Device $device): void
    {
        if ($this->dhcpRelevantChanged($device)) {
            $this->requestBypassForUser($device->user_id);
        }

        if ($device->wasChanged('user_id') && $device->getOriginal('user_id')) {
            $this->requestBypassForUser((int) $device->getOriginal('user_id'));
        }
    }

    public function deleted(Device $device): void
    {
        $this->requestBypassForUser($device->user_id);
    }

    protected function dhcpRelevantChanged(Device $device): bool
    {
        foreach (['mac_address', 'role', 'status', 'user_id'] as $attr) {
            if ($device->wasChanged($attr)) {
                return true;
            }
        }

        return false;
    }

    protected function requestBypassForUser(?int $userId): void
    {
        if (! $userId) {
            return;
        }

        $this->policyApplyDebouncer->requestApply($userId, PolicyApplyFlags::DhcpBypass);
    }
}
