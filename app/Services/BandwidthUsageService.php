<?php

namespace App\Services;

use App\Models\BrowsingLog;
use App\Models\Device;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;

class BandwidthUsageService
{
    private const BITS_PER_BYTE = 8;

    /** SI gigabit = 10^9 bits (same unit as dashboard bandwidth chart). */
    private const GIGABIT = 1000000000;

    public function __construct(
        private readonly NetworkService $networkService
    ) {}

    public function buildChartPayload(User $parent, string $range, ?int $onlyDeviceId = null): array
    {
        $range = strtolower($range);
        $timezone = (string) (config('app.timezone') ?: 'UTC');
        $now = Carbon::now($timezone);

        $buckets = $this->buildBuckets($range, $now);
        $labels = array_map(static fn (array $bucket) => $bucket['label'], $buckets);

        $devicesQuery = Device::query()
            ->where('user_id', $parent->id)
            ->forDashboardTimeUsage()
            ->orderBy('name');

        if ($onlyDeviceId !== null) {
            $devicesQuery->where('id', $onlyDeviceId);
        }

        $devices = $devicesQuery->get(['id', 'name', 'mac_address']);

        if ($devices->isEmpty()) {
            return [
                'range' => $range,
                'unit' => 'gbit',
                'labels' => $labels,
                'series' => [],
            ];
        }

        $deviceIds = $devices->pluck('id')->values();
        $overallStart = $buckets[0]['start'];
        $overallEndExclusive = $buckets[count($buckets) - 1]['end'];

        $logs = BrowsingLog::query()
            ->whereIn('device_id', $deviceIds)
            ->whereBetween('visited_at', [$overallStart, $overallEndExclusive])
            ->get(['device_id', 'visited_at', 'bytes_sent', 'bytes_received']);

        $bucketStarts = array_map(
            static fn (array $bucket): CarbonInterface => $bucket['start'],
            $buckets
        );

        $bytesByDevice = [];
        foreach ($devices as $device) {
            $bytesByDevice[(int) $device->id] = array_fill(0, count($buckets), 0);
        }

        foreach ($logs as $log) {
            $deviceId = (int) $log->device_id;
            if (! isset($bytesByDevice[$deviceId])) {
                continue;
            }

            $visitedAt = $log->visited_at instanceof CarbonInterface
                ? $log->visited_at->copy()
                : Carbon::parse((string) $log->visited_at, $timezone);
            $bytes = (int) (($log->bytes_sent ?? 0) + ($log->bytes_received ?? 0));

            foreach ($buckets as $index => $bucket) {
                if ($visitedAt->gte($bucket['start']) && $visitedAt->lt($bucket['end'])) {
                    $bytesByDevice[$deviceId][$index] += $bytes;
                    break;
                }
            }
        }

        $allZero = collect($bytesByDevice)
            ->flatten()
            ->every(static fn (int $value) => $value === 0);

        if ($allZero) {
            $liveBytesByMac = $this->getLiveTrafficBytesByMac();
            if ($liveBytesByMac !== []) {
                $currentBucketIndex = $this->findCurrentBucketIndex($bucketStarts, $now);
                if ($currentBucketIndex !== null) {
                    foreach ($devices as $device) {
                        $normalizedMac = $this->normalizeMac((string) $device->mac_address);
                        if ($normalizedMac === null || ! isset($liveBytesByMac[$normalizedMac])) {
                            continue;
                        }

                        $bytesByDevice[(int) $device->id][$currentBucketIndex] = (int) $liveBytesByMac[$normalizedMac];
                    }
                }
            }
        }

        $series = [];
        foreach ($devices as $device) {
            $bytes = $bytesByDevice[(int) $device->id] ?? [];
            $series[] = [
                'device_id' => (int) $device->id,
                'device_name' => (string) $device->name,
                'values' => array_map(
                    static fn (int $value): float => round(($value * self::BITS_PER_BYTE) / self::GIGABIT, 4),
                    $bytes
                ),
            ];
        }

        return [
            'range' => $range,
            'unit' => 'gbit',
            'labels' => $labels,
            'series' => $series,
        ];
    }

    public function buildDigestBandwidthSummary(
        User $parent,
        CarbonInterface $periodStartUtc,
        CarbonInterface $periodEndUtc
    ): array {
        $devices = $parent->devices()->orderBy('name')->get(['id', 'name', 'mac_address']);
        $deviceIds = $devices->pluck('id')->values();

        $perDeviceBytes = [];
        foreach ($devices as $device) {
            $totalBytes = (int) BrowsingLog::query()
                ->where('device_id', (int) $device->id)
                ->whereBetween('visited_at', [$periodStartUtc, $periodEndUtc])
                ->selectRaw('COALESCE(SUM(bytes_sent + bytes_received), 0) as bytes_total')
                ->value('bytes_total');

            $perDeviceBytes[(int) $device->id] = $totalBytes;
        }

        $familyTotalBytes = array_sum($perDeviceBytes);
        $source = 'browsing_logs';

        if ($familyTotalBytes <= 0) {
            $liveBytesByMac = $this->getLiveTrafficBytesByMac();
            if ($liveBytesByMac !== []) {
                foreach ($devices as $device) {
                    $normalizedMac = $this->normalizeMac((string) $device->mac_address);
                    if ($normalizedMac === null || ! isset($liveBytesByMac[$normalizedMac])) {
                        continue;
                    }

                    $perDeviceBytes[(int) $device->id] = (int) $liveBytesByMac[$normalizedMac];
                }

                $familyTotalBytes = array_sum($perDeviceBytes);
                $source = 'live_traffic_fallback';
            }
        }

        $perDevice = [];
        foreach ($devices as $device) {
            $bytesTotal = (int) ($perDeviceBytes[(int) $device->id] ?? 0);
            $perDevice[] = [
                'device_id' => (int) $device->id,
                'device_name' => (string) $device->name,
                'bytes_total' => $bytesTotal,
                'bytes_total_formatted' => $this->formatGigabits($bytesTotal),
            ];
        }

        usort(
            $perDevice,
            static fn (array $a, array $b): int => $b['bytes_total'] <=> $a['bytes_total']
        );

        return [
            'source' => $source,
            'family_total_bytes' => $familyTotalBytes,
            'family_total_formatted' => $this->formatGigabits($familyTotalBytes),
            'top_bandwidth_devices' => array_slice($perDevice, 0, 5),
            'per_device' => $perDevice,
        ];
    }

    private function formatGigabits(int $bytes): string
    {
        $gigabits = ($bytes * self::BITS_PER_BYTE) / self::GIGABIT;
        if ($gigabits <= 0) {
            return '0 Gbit';
        }

        $formatted = number_format($gigabits, 3, '.', '');
        $formatted = rtrim(rtrim($formatted, '0'), '.');

        return $formatted.' Gbit';
    }

    /**
     * @return array<string, int>
     */
    private function getLiveTrafficBytesByMac(): array
    {
        try {
            $stats = $this->networkService->getTrafficStats();
        } catch (\Throwable $exception) {
            Log::warning('Failed to read live traffic stats for bandwidth usage', [
                'message' => $exception->getMessage(),
            ]);

            return [];
        }

        $result = [];
        foreach ($stats as $stat) {
            $normalizedMac = $this->normalizeMac((string) ($stat['mac_address'] ?? ''));
            if ($normalizedMac === null) {
                continue;
            }

            $bytesSent = (int) ($stat['bytes_sent'] ?? 0);
            $bytesReceived = (int) ($stat['bytes_received'] ?? 0);
            $result[$normalizedMac] = max(0, $bytesSent + $bytesReceived);
        }

        return $result;
    }

    private function normalizeMac(string $mac): ?string
    {
        $normalized = strtoupper(str_replace('-', ':', trim($mac)));
        if ($normalized === '') {
            return null;
        }

        return $normalized;
    }

    /**
     * @param  array<int, CarbonInterface>  $bucketStarts
     */
    private function findCurrentBucketIndex(array $bucketStarts, CarbonInterface $now): ?int
    {
        foreach ($bucketStarts as $index => $bucketStart) {
            $next = $bucketStarts[$index + 1] ?? null;
            if ($next === null && $now->gte($bucketStart)) {
                return $index;
            }

            if ($next !== null && $now->gte($bucketStart) && $now->lt($next)) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @return array<int, array{start: Carbon, end: Carbon, label: string|array}>
     */
    private function buildBuckets(string $range, Carbon $now): array
    {
        return match ($range) {
            'daily' => $this->buildDailyBuckets($now),
            'weekly' => $this->buildWeeklyBuckets($now),
            'monthly' => $this->buildMonthlyBuckets($now),
            'yearly' => $this->buildYearlyBuckets($now),
            default => $this->buildYearlyBuckets($now),
        };
    }

    /**
     * @return array<int, array{start: Carbon, end: Carbon, label: string}>
     */
    private function buildDailyBuckets(Carbon $now): array
    {
        $start = $now->copy()->startOfDay();
        $buckets = [];

        for ($hour = 0; $hour < 24; $hour++) {
            $bucketStart = $start->copy()->addHours($hour);
            $buckets[] = [
                'start' => $bucketStart,
                'end' => $bucketStart->copy()->addHour(),
                'label' => $bucketStart->format('H'),
            ];
        }

        return $buckets;
    }

    /**
     * @return array<int, array{start: Carbon, end: Carbon, label: array}>
     */
    private function buildWeeklyBuckets(Carbon $now): array
    {
        $startDay = $now->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $buckets = [];

        for ($i = 0; $i < 7; $i++) {
            $bucketStart = $startDay->copy()->addDays($i);
            $buckets[] = [
                'start' => $bucketStart,
                'end' => $bucketStart->copy()->addDay(),
                'label' => [$bucketStart->format('M d'), $bucketStart->format('D')],
            ];
        }

        return $buckets;
    }

    /**
     * @return array<int, array{start: Carbon, end: Carbon, label: string}>
     */
    private function buildMonthlyBuckets(Carbon $now): array
    {
        $monthStart = $now->copy()->startOfMonth();
        $nextMonthStart = $monthStart->copy()->addMonthNoOverflow();
        $buckets = [];

        $week = 1;
        $bucketStart = $monthStart->copy();
        while ($bucketStart->lt($nextMonthStart)) {
            $bucketEnd = $bucketStart->copy()->addDays(7);
            if ($bucketEnd->gt($nextMonthStart)) {
                $bucketEnd = $nextMonthStart->copy();
            }

            $buckets[] = [
                'start' => $bucketStart->copy(),
                'end' => $bucketEnd,
                'label' => 'Week '.$week,
            ];

            $bucketStart = $bucketEnd;
            $week++;
        }

        return $buckets;
    }

    /**
     * @return array<int, array{start: Carbon, end: Carbon, label: string}>
     */
    private function buildYearlyBuckets(Carbon $now): array
    {
        $yearStart = $now->copy()->startOfYear();
        $buckets = [];

        for ($i = 0; $i < 12; $i++) {
            $bucketStart = $yearStart->copy()->addMonthsNoOverflow($i)->startOfMonth();
            $buckets[] = [
                'start' => $bucketStart,
                'end' => $bucketStart->copy()->addMonthNoOverflow(),
                'label' => $bucketStart->format('M'),
            ];
        }

        return $buckets;
    }
}
