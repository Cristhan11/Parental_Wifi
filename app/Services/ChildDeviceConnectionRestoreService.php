<?php

namespace App\Services;

use App\Models\Device;
use Illuminate\Support\Facades\Log;

/**
 * Restores captive portal / network access for a child device that still has
 * remaining internet time (same primitives as quiz/video grants and
 * CheckTimeExpiration's "devices with time" path).
 */
class ChildDeviceConnectionRestoreService
{
    public function __construct(
        protected NetworkService $networkService,
        protected NoDogSplashService $noDogSplashService,
    ) {}

    /**
     * If the device is non-whitelisted, has remaining_time_minutes > 0, and status is
     * active or blocked: unblock at network layer, authenticate in NoDogSplash, and set
     * status to active when it was blocked. Idempotent.
     */
    public function tryRestoreIfHasRemainingTime(?Device $device): void
    {
        if (! $device) {
            return;
        }

        $device->refresh();

        if ($device->isWhitelisted()) {
            return;
        }

        if (! in_array($device->status, ['active', 'blocked'], true)) {
            return;
        }

        if ((int) ($device->remaining_time_minutes ?? 0) <= 0) {
            return;
        }

        try {
            $unblocked = $this->networkService->unblockDevice($device);
            if ($unblocked) {
                Log::debug('Unblocked device with time remaining at network level (immediate restore)', [
                    'device_id' => $device->id,
                    'device_name' => $device->name,
                    'mac_address' => $device->mac_address,
                    'remaining_time_minutes' => $device->remaining_time_minutes,
                ]);
            }
        } catch (\Exception $e) {
            Log::debug('Could not unblock device at network level (immediate restore)', [
                'device_id' => $device->id ?? 'unknown',
                'mac_address' => $device->mac_address ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $authenticated = $this->noDogSplashService->allowDeviceThrough($device);
            if ($authenticated) {
                Log::debug('Authenticated device with time remaining (immediate restore)', [
                    'device_id' => $device->id,
                    'device_name' => $device->name,
                    'mac_address' => $device->mac_address,
                    'remaining_time_minutes' => $device->remaining_time_minutes,
                ]);
            }
        } catch (\Exception $e) {
            Log::debug('Could not authenticate device with time (immediate restore)', [
                'device_id' => $device->id ?? 'unknown',
                'mac_address' => $device->mac_address ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            // Network unblock may have succeeded while NoDogSplash failed; keep DB in sync
            // with "has quota" so /accounts and dashboards do not show blocked incorrectly.
            $device->refresh();
            if ($device->status === 'blocked' && (int) ($device->remaining_time_minutes ?? 0) > 0) {
                $device->update(['status' => 'active']);
                Log::debug('Updated device status to active after portal auth failed (network may already allow traffic)', [
                    'device_id' => $device->id,
                    'device_name' => $device->name,
                    'mac_address' => $device->mac_address,
                ]);
            }

            return;
        }

        if ($device->status === 'blocked') {
            $device->update(['status' => 'active']);
            Log::debug('Updated device status from blocked to active (immediate restore)', [
                'device_id' => $device->id,
                'device_name' => $device->name,
                'mac_address' => $device->mac_address,
            ]);
        }
    }
}
