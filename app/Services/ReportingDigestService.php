<?php

namespace App\Services;

use App\Models\AccessAttempt;
use App\Models\BrowsingLog;
use App\Models\DeviceSession;
use App\Models\DeviceTimeGrant;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class ReportingDigestService
{
    /**
     * Build the locked digest payload for a parent account.
     *
     * Sections:
     * - Family-level: violations_summary, top_visited_domains, time_usage_and_grants (unchanged for logs/UI)
     * - Per-device: devices[] with violations, top domains, usage & grants, bar chart widths
     */
    public function buildDigestPayload(
        User $parent,
        CarbonInterface $periodStartLocal,
        CarbonInterface $periodEndLocal,
        string $timezone
    ): array {
        $periodStartUtc = $periodStartLocal->clone()->setTimezone('UTC');
        $periodEndUtc = $periodEndLocal->clone()->setTimezone('UTC');

        $deviceIds = $parent->devices()->pluck('id');

        $blockedCount = AccessAttempt::query()
            ->whereIn('device_id', $deviceIds)
            ->where('type', 'blocked_website')
            ->whereBetween('attempted_at', [$periodStartUtc, $periodEndUtc])
            ->count();

        $flaggedCount = AccessAttempt::query()
            ->whereIn('device_id', $deviceIds)
            ->where('type', 'flagged_website')
            ->whereBetween('attempted_at', [$periodStartUtc, $periodEndUtc])
            ->count();

        $topDomains = BrowsingLog::query()
            ->whereIn('device_id', $deviceIds)
            ->whereBetween('visited_at', [$periodStartUtc, $periodEndUtc])
            ->whereNotNull('domain')
            ->select('domain', DB::raw('COUNT(*) as visits'))
            ->groupBy('domain')
            ->orderByDesc('visits')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'domain' => (string) $row->domain,
                'visits' => (int) $row->visits,
            ])
            ->values()
            ->all();

        $grantsAggregate = DeviceTimeGrant::query()
            ->whereIn('device_id', $deviceIds)
            ->whereBetween('granted_at', [$periodStartUtc, $periodEndUtc])
            ->selectRaw('COUNT(*) as grants_count, COALESCE(SUM(minutes_granted), 0) as total_granted_minutes')
            ->first();

        $usageSeconds = (int) DeviceSession::query()
            ->whereIn('device_id', $deviceIds)
            ->whereBetween('started_at', [$periodStartUtc, $periodEndUtc])
            ->sum('duration_seconds');

        $activeDeviceIds = collect([
            AccessAttempt::query()
                ->whereIn('device_id', $deviceIds)
                ->whereBetween('attempted_at', [$periodStartUtc, $periodEndUtc])
                ->pluck('device_id')
                ->all(),
            BrowsingLog::query()
                ->whereIn('device_id', $deviceIds)
                ->whereBetween('visited_at', [$periodStartUtc, $periodEndUtc])
                ->pluck('device_id')
                ->all(),
            DeviceTimeGrant::query()
                ->whereIn('device_id', $deviceIds)
                ->whereBetween('granted_at', [$periodStartUtc, $periodEndUtc])
                ->pluck('device_id')
                ->all(),
        ])->flatten()->unique()->values();

        $usageMinutes = (int) round($usageSeconds / 60);
        $grantsCount = (int) ($grantsAggregate->grants_count ?? 0);
        $totalGrantedMinutes = (int) ($grantsAggregate->total_granted_minutes ?? 0);

        $devices = $this->buildPerDevicePayload($parent, $periodStartUtc, $periodEndUtc);

        return [
            'timezone' => $timezone,
            'period_start_local' => $periodStartLocal,
            'period_end_local' => $periodEndLocal,
            'violations_summary' => [
                'blocked_count' => $blockedCount,
                'flagged_count' => $flaggedCount,
            ],
            'top_visited_domains' => $topDomains,
            'time_usage_and_grants' => [
                'total_usage_minutes' => $usageMinutes,
                'grants_count' => $grantsCount,
                'total_granted_minutes' => $totalGrantedMinutes,
            ],
            'active_devices_count' => $activeDeviceIds->count(),
            'registered_devices_count' => $parent->devices()->count(),
            'devices' => $devices,
            'has_activity' => ($blockedCount + $flaggedCount + $grantsCount + $usageMinutes + count($topDomains)) > 0,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildPerDevicePayload(User $parent, CarbonInterface $periodStartUtc, CarbonInterface $periodEndUtc): array
    {
        $devices = $parent->devices()->orderBy('name')->get(['id', 'name']);
        $rows = [];

        foreach ($devices as $device) {
            $did = (int) $device->id;

            $blocked = AccessAttempt::query()
                ->where('device_id', $did)
                ->where('type', 'blocked_website')
                ->whereBetween('attempted_at', [$periodStartUtc, $periodEndUtc])
                ->count();

            $flagged = AccessAttempt::query()
                ->where('device_id', $did)
                ->where('type', 'flagged_website')
                ->whereBetween('attempted_at', [$periodStartUtc, $periodEndUtc])
                ->count();

            $deviceTopDomains = BrowsingLog::query()
                ->where('device_id', $did)
                ->whereBetween('visited_at', [$periodStartUtc, $periodEndUtc])
                ->whereNotNull('domain')
                ->select('domain', DB::raw('COUNT(*) as visits'))
                ->groupBy('domain')
                ->orderByDesc('visits')
                ->limit(5)
                ->get()
                ->map(fn ($row) => [
                    'domain' => (string) $row->domain,
                    'visits' => (int) $row->visits,
                ])
                ->values()
                ->all();

            $grantRow = DeviceTimeGrant::query()
                ->where('device_id', $did)
                ->whereBetween('granted_at', [$periodStartUtc, $periodEndUtc])
                ->selectRaw('COUNT(*) as grants_count, COALESCE(SUM(minutes_granted), 0) as total_granted_minutes')
                ->first();

            $usageSec = (int) DeviceSession::query()
                ->where('device_id', $did)
                ->whereBetween('started_at', [$periodStartUtc, $periodEndUtc])
                ->sum('duration_seconds');

            $usageMin = (int) round($usageSec / 60);
            $gCount = (int) ($grantRow->grants_count ?? 0);
            $gMinutes = (int) ($grantRow->total_granted_minutes ?? 0);

            $rows[] = [
                'id' => $did,
                'name' => (string) $device->name,
                'violations_summary' => [
                    'blocked_count' => $blocked,
                    'flagged_count' => $flagged,
                ],
                'top_visited_domains' => $deviceTopDomains,
                'time_usage_and_grants' => [
                    'total_usage_minutes' => $usageMin,
                    'grants_count' => $gCount,
                    'total_granted_minutes' => $gMinutes,
                ],
            ];
        }

        $maxUsage = max(1, ...array_map(fn (array $r) => $r['time_usage_and_grants']['total_usage_minutes'], $rows) ?: [0]);
        $maxGranted = max(1, ...array_map(fn (array $r) => $r['time_usage_and_grants']['total_granted_minutes'], $rows) ?: [0]);

        foreach ($rows as $i => $row) {
            $u = $row['time_usage_and_grants']['total_usage_minutes'];
            $g = $row['time_usage_and_grants']['total_granted_minutes'];
            $rows[$i]['usage_bar_percent'] = $u > 0 ? (int) round(($u / $maxUsage) * 100) : 0;
            $rows[$i]['grants_bar_percent'] = $g > 0 ? (int) round(($g / $maxGranted) * 100) : 0;
        }

        return $rows;
    }
}

