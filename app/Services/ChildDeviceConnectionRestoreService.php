<?php

namespace App\Services;

use App\Models\Device;
use Illuminate\Support\Facades\Log;

/**
 * Restores captive portal / network access after a device row is created or approved:
 * whitelisted parent/guest (network whitelist + NoDogSplash auth), or child-style
 * active/blocked with remaining time (same primitives as quiz/video grants).
 */
class ChildDeviceConnectionRestoreService
{
    public function __construct(
        protected NetworkService $networkService,
        protected NoDogSplashService $noDogSplashService,
    ) {}

    /**
     * After provisioning a device record, apply the right network + captive steps.
     * Whitelisted devices (typical parent/guest accounts) skip time-based restore but still
     * need whitelist rules and ndsctl auth to leave the splash.
     */
    public function tryRestoreAfterDeviceProvisioned(?Device $device): void
    {
        if (! $device) {
            return;
        }

        $device->refresh();

        if ($device->isWhitelisted()) {
            try {
                $this->networkService->whitelistDevice($device);
            } catch (\Exception $e) {
                Log::debug('whitelistDevice after provision failed (non-fatal)', [
                    'device_id' => $device->id,
                    'mac_address' => $device->mac_address,
                    'error' => $e->getMessage(),
                ]);
            }

            try {
                $this->allowDeviceThroughWithRetries($device);
            } catch (\Exception $e) {
                Log::debug('allowDeviceThrough after whitelist provision failed (non-fatal)', [
                    'device_id' => $device->id,
                    'mac_address' => $device->mac_address,
                    'error' => $e->getMessage(),
                ]);
            }

            return;
        }

        $this->tryRestoreIfHasRemainingTime($device);
    }

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
            $authenticated = $this->allowDeviceThroughWithRetries($device);
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

    /**
     * ndsctl auth only works once the client appears in NoDogSplash's client list; after
     * registration approval there can be a short race. Retry a few times before giving up.
     */
    private function allowDeviceThroughWithRetries(Device $device, int $maxAttempts = 6, int $sleepSeconds = 2): bool
    {
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $device->refresh();

            try {
                if ($this->noDogSplashService->allowDeviceThrough($device)) {
                    if ($attempt > 1) {
                        Log::info('NoDogSplash allowDeviceThrough succeeded after retry', [
                            'device_id' => $device->id,
                            'mac_address' => $device->mac_address,
                            'attempt' => $attempt,
                        ]);
                    }

                    return true;
                }
            } catch (\Exception $e) {
                Log::debug('allowDeviceThrough attempt failed', [
                    'device_id' => $device->id ?? null,
                    'mac_address' => $device->mac_address ?? null,
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                ]);
            }

            if ($attempt < $maxAttempts) {
                sleep($sleepSeconds);
            }
        }

        return false;
    }
}
