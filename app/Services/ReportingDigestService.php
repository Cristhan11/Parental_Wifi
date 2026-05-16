<?php

namespace App\Services;

use App\Models\AccessAttempt;
use App\Models\BrowsingLog;
use App\Models\Device;
use App\Models\DeviceTimeGrant;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Aggregates browsing/access data into the array shape consumed by digest mailables and Blade templates.
 *
 * Why: Keeps SQL and grouping rules in one place so {@see \App\Jobs\DispatchDigestReportJob} stays thin (send + log only).
 *
 * Inputs: parent {@see \App\Models\User}, digest window in parent’s timezone (converted to UTC for queries).
 * Outputs: payload keys like `violations_summary`, `devices[]`, `top_visited_domains` — see {@see resources/views/emails/reports/_digest-body.blade.php}.
 */
class ReportingDigestService
{
    public function __construct(
        private readonly BandwidthUsageService $bandwidthUsageService,
        private readonly DashboardTimeUsageService $dashboardTimeUsageService,
    ) {}

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
        // DB timestamps are stored in UTC — convert the digest window once, then reuse for all queries.
        $periodStartUtc = $periodStartLocal->clone()->setTimezone('UTC');
        $periodEndUtc = $periodEndLocal->clone()->setTimezone('UTC');

        // Monitored child devices only — stricter than raw dashboard query so parent/guest rows never appear in email.
        // Query Device directly — `forReportingEmails` is a model scope; `$parent->devices()` is a
        // HasMany relation and does not forward custom scopes on all Laravel versions (see BadMethodCallException).
        $deviceIds = Device::query()
            ->where('user_id', $parent->id)
            ->forReportingEmails()
            ->pluck('id');

        // AccessAttempt rows are created when the filtering layer records blocked/flagged attempts.
        $blockedCount = AccessAttempt::query()
            ->whereIn('device_id', $deviceIds)
            ->where('type', 'blocked_website')
            ->where('attempted_at', '>=', $periodStartUtc)
            ->where('attempted_at', '<=', $periodEndUtc)
            ->count();

        $flaggedCount = AccessAttempt::query()
            ->whereIn('device_id', $deviceIds)
            ->where('type', 'flagged_website')
            ->where('attempted_at', '>=', $periodStartUtc)
            ->where('attempted_at', '<=', $periodEndUtc)
            ->count();

        $topDomains = BrowsingLog::query()
            ->whereIn('device_id', $deviceIds)
            ->where('visited_at', '>=', $periodStartUtc)
            ->where('visited_at', '<=', $periodEndUtc)
            ->whereNotNull('domain')
            ->select('domain', DB::raw('SUM(COALESCE(visit_count, 1)) as visits'))
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
            ->where('granted_at', '>=', $periodStartUtc)
            ->where('granted_at', '<=', $periodEndUtc)
            ->selectRaw('COUNT(*) as grants_count, COALESCE(SUM(minutes_granted), 0) as total_granted_minutes')
            ->first();

        $digestDevices = Device::query()
            ->where('user_id', $parent->id)
            ->forReportingEmails()
            ->orderBy('name')
            ->get();

        // Same session overlap rules as the dashboard TIME USAGE card (local wall-clock window).
        $dashboardUsageSeconds = $this->dashboardTimeUsageService->sumUsageSecondsByDevice(
            $parent,
            Carbon::instance($periodStartLocal),
            Carbon::instance($periodEndLocal),
        );
        $usageByDeviceId = [];
        foreach ($digestDevices as $device) {
            $usageByDeviceId[(int) $device->id] = (float) ($dashboardUsageSeconds[(int) $device->id] ?? 0);
        }
        $usageSeconds = (int) round(array_sum($usageByDeviceId));

        $activeDeviceIds = collect([
            AccessAttempt::query()
                ->whereIn('device_id', $deviceIds)
                ->where('attempted_at', '>=', $periodStartUtc)
                ->where('attempted_at', '<=', $periodEndUtc)
                ->pluck('device_id')
                ->all(),
            BrowsingLog::query()
                ->whereIn('device_id', $deviceIds)
                ->where('visited_at', '>=', $periodStartUtc)
                ->where('visited_at', '<=', $periodEndUtc)
                ->pluck('device_id')
                ->all(),
            DeviceTimeGrant::query()
                ->whereIn('device_id', $deviceIds)
                ->where('granted_at', '>=', $periodStartUtc)
                ->where('granted_at', '<=', $periodEndUtc)
                ->pluck('device_id')
                ->all(),
            collect($usageByDeviceId)->filter(fn ($sec) => (float) $sec > 0)->keys()->all(),
        ])->flatten()->unique()->values();

        $usageMinutes = (int) round($usageSeconds / 60);
        $grantsCount = (int) ($grantsAggregate->grants_count ?? 0);
        $totalGrantedMinutes = (int) ($grantsAggregate->total_granted_minutes ?? 0);

        $devices = $this->buildPerDevicePayload(
            $parent,
            $this->toImmutableUtc($periodStartUtc),
            $this->toImmutableUtc($periodEndUtc),
            $usageByDeviceId
        );
        $bandwidth = $this->bandwidthUsageService->buildDigestBandwidthSummary(
            $parent,
            $periodStartUtc,
            $periodEndUtc
        );

        $devices = collect($devices)
            ->map(function (array $device) use ($bandwidth): array {
                $matched = collect($bandwidth['per_device'] ?? [])
                    ->firstWhere('device_id', (int) ($device['id'] ?? 0));

                $device['bandwidth'] = [
                    'bytes_total' => (int) ($matched['bytes_total'] ?? 0),
                    'bytes_total_formatted' => (string) ($matched['bytes_total_formatted'] ?? '0 GB (0 MB)'),
                ];

                return $device;
            })
            ->values()
            ->all();

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
            'bandwidth' => $bandwidth,
            'active_devices_count' => $activeDeviceIds->count(),
            'registered_devices_count' => Device::query()
                ->where('user_id', $parent->id)
                ->forReportingEmails()
                ->count(),
            'devices' => $devices,
            'has_activity' => ($blockedCount + $flaggedCount + $grantsCount + $usageMinutes + count($topDomains)) > 0,
        ];
    }

    /**
     * @param  array<int, float>  $usageSecondsByDeviceId
     * @return array<int, array<string, mixed>>
     */
    private function buildPerDevicePayload(
        User $parent,
        CarbonImmutable $periodStartUtc,
        CarbonImmutable $periodEndUtc,
        array $usageSecondsByDeviceId
    ): array {
        $devices = Device::query()
            ->where('user_id', $parent->id)
            ->forReportingEmails()
            ->orderBy('name')
            ->get();
        $rows = [];

        foreach ($devices as $device) {
            $did = (int) $device->id;

            $blocked = AccessAttempt::query()
                ->where('device_id', $did)
                ->where('type', 'blocked_website')
                ->where('attempted_at', '>=', $periodStartUtc)
                ->where('attempted_at', '<=', $periodEndUtc)
                ->count();

            $flagged = AccessAttempt::query()
                ->where('device_id', $did)
                ->where('type', 'flagged_website')
                ->where('attempted_at', '>=', $periodStartUtc)
                ->where('attempted_at', '<=', $periodEndUtc)
                ->count();

            $deviceTopDomains = BrowsingLog::query()
                ->where('device_id', $did)
                ->where('visited_at', '>=', $periodStartUtc)
                ->where('visited_at', '<=', $periodEndUtc)
                ->whereNotNull('domain')
                ->select('domain', DB::raw('SUM(COALESCE(visit_count, 1)) as visits'))
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
                ->where('granted_at', '>=', $periodStartUtc)
                ->where('granted_at', '<=', $periodEndUtc)
                ->selectRaw('COUNT(*) as grants_count, COALESCE(SUM(minutes_granted), 0) as total_granted_minutes')
                ->first();

            $usageSec = (int) round($usageSecondsByDeviceId[$did] ?? 0.0);

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

    private function toImmutableUtc(CarbonInterface|string|null $value): CarbonImmutable
    {
        if ($value === null) {
            throw new \InvalidArgumentException('Expected a non-null datetime.');
        }

        if ($value instanceof CarbonInterface) {
            return CarbonImmutable::createFromInterface($value)->utc();
        }

        return CarbonImmutable::parse((string) $value, 'UTC');
    }
}
