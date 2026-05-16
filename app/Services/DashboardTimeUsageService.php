<?php

namespace App\Services;

use App\Models\Device;
use App\Models\DeviceSession;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Time-usage totals using the same rules as the parent dashboard TIME USAGE card.
 *
 * Digest reporting uses this for overlap windows so manual test emails match the dashboard.
 */
class DashboardTimeUsageService
{
    /**
     * Per-device connected seconds between {@see $periodStart} and {@see $periodEnd} (app timezone aware).
     *
     * @return array<int, int> device_id => seconds
     */
    public function sumUsageSecondsByDevice(
        User $parent,
        CarbonInterface $periodStart,
        CarbonInterface $periodEnd,
    ): array {
        $periodStart = Carbon::instance($periodStart);
        $periodEnd = Carbon::instance($periodEnd);

        $devices = Device::query()
            ->where('user_id', $parent->id)
            ->forDashboardTimeUsage()
            ->get();

        if ($devices->isEmpty()) {
            return [];
        }

        $deviceIds = $devices->pluck('id')->values();

        $endedSessionsByDevice = DeviceSession::query()
            ->whereIn('device_id', $deviceIds)
            ->whereNotNull('ended_at')
            ->where('started_at', '<', $periodEnd)
            ->where('ended_at', '>', $periodStart)
            ->get(['device_id', 'started_at', 'ended_at'])
            ->groupBy('device_id');

        $activeSessionByDevice = DeviceSession::query()
            ->whereIn('device_id', $deviceIds)
            ->whereNull('ended_at')
            ->orderByDesc('started_at')
            ->get(['id', 'device_id', 'started_at', 'last_incremental_bill_at'])
            ->unique('device_id')
            ->keyBy('device_id')
            ->all();

        $totals = [];

        foreach ($devices as $device) {
            $totalSeconds = 0;

            foreach ($endedSessionsByDevice->get($device->id, collect()) as $session) {
                $segStart = $session->started_at->copy()->max($periodStart);
                $segEnd = $session->ended_at->copy()->min($periodEnd);
                if ($segStart->lt($segEnd)) {
                    $totalSeconds += $segStart->diffInSeconds($segEnd);
                }
            }

            $activeSession = $activeSessionByDevice[$device->id] ?? null;
            $remainingMinutes = $this->calculateRemainingMinutes($device, $activeSession);
            $includeActiveUsage = $activeSession
                && ($device->isWhitelisted() || $remainingMinutes > 0);

            if ($includeActiveUsage) {
                $segStart = $activeSession->started_at->copy()->max($periodStart);
                if ($segStart->lt($periodEnd)) {
                    $totalSeconds += $segStart->diffInSeconds($periodEnd);
                }
            }

            $totals[(int) $device->id] = $totalSeconds;
        }

        return $totals;
    }

    /**
     * Today from midnight through now in the application timezone.
     *
     * @return array<int, int>
     */
    public function sumTodayUsageSecondsByDevice(User $parent): array
    {
        $now = now();
        $startOfDay = $now->copy()->startOfDay();

        return $this->sumUsageSecondsByDevice($parent, $startOfDay, $now);
    }

    /**
     * @param  DeviceSession|null  $activeSession
     */
    private function calculateRemainingMinutes(Device $device, $activeSession): int
    {
        if ($device->isWhitelisted()) {
            return 999999;
        }

        $baseRemaining = (int) ($device->remaining_time_minutes ?? 0);
        if (! $activeSession) {
            return max(0, $baseRemaining);
        }

        $poolSeconds = $baseRemaining * 60;
        $anchor = $activeSession->billingAnchor();
        $elapsedSeconds = $anchor->diffInSeconds(now());
        $remainingSeconds = max(0, $poolSeconds - $elapsedSeconds);

        return max(0, (int) floor($remainingSeconds / 60));
    }
}
