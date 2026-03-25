<?php

namespace App\Services;

use App\Models\Device;
use App\Models\DeviceSession;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Build the per-child-device “time usage” payload used by the dashboard Chart.js graph.
 *
 * The core requirement is to count only the portion of each internet session that overlaps
 * each chart bucket (hour/day/month). This is important because a session can span across
 * bucket boundaries (for example: 01:30–03:10 spans two hourly buckets).
 *
 * For active (non-ended) sessions we apply the same “time is still granted” rules used by
 * the TIME USAGE card, so the graph and the card stay consistent.
 */
class UsageChartService
{
    public function __construct(
        private readonly TimeTrackingService $timeTrackingService
    ) {}

    /**
     * Build JSON-serializable payload for the dashboard time-usage chart.
     *
     * Output shape:
     * [
     *   'range' => 'daily'|'weekly'|'monthly'|'yearly',
     *   'unit' => 'minutes'|'hours',
     *   'labels' => string[],
     *   'series' => [
     *     ['device_id' => int, 'device_name' => string, 'values' => float[]],
     *     ...
     *   ]
     * ]
     */
    public function buildChartPayload(User $parent, string $range): array
    {
        $range = strtolower($range);
        $timezone = (string) (config('app.timezone') ?: 'UTC');

        $now = Carbon::now($timezone);
        $buckets = $this->buildBuckets($range, $now);
        $unit = $this->unitForRange($range);

        $devices = Device::query()
            ->where('user_id', $parent->id)
            ->orderBy('name')
            ->get(['id', 'name', 'status']);

        $labels = array_map(static fn(array $b) => $b['label'], $buckets);
        $deviceIndexById = $devices->keyBy('id');

        // No devices => still return labels so the frontend can render an empty chart.
        if ($devices->isEmpty()) {
            return [
                'range' => $range,
                'unit' => $unit,
                'labels' => $labels,
                'series' => [],
            ];
        }

        $deviceIds = $devices->pluck('id')->values();

        // Define the overall time window used to query sessions.
        $overallStart = $buckets[0]['start'];
        $overallEndExclusive = $buckets[count($buckets) - 1]['end'];

        $sessions = DeviceSession::query()
            ->whereIn('device_id', $deviceIds)
            // Session must start before the end of the whole window.
            ->where('started_at', '<', $overallEndExclusive)
            // Session must end after the start of the whole window, or still be active.
            ->where(function ($query) use ($overallStart) {
                $query->whereNull('ended_at')
                    ->orWhere('ended_at', '>', $overallStart);
            })
            ->get(['id', 'device_id', 'started_at', 'ended_at']);

        $sessionsByDeviceId = $sessions->groupBy('device_id');

        $series = [];
        foreach ($devices as $device) {
            $valuesSeconds = array_fill(0, count($buckets), 0.0);

            // Active-session handling:
            // - If the device is whitelisted, it never expires.
            // - Otherwise, count active session time only if time is still granted.
            $includeActiveSession = $device->isWhitelisted()
                || ! $this->timeTrackingService->hasTimeExpired($device);

            $deviceSessions = $sessionsByDeviceId->get($device->id, collect());

            foreach ($deviceSessions as $session) {
                $sessionStart = $session->started_at instanceof CarbonInterface
                    ? $session->started_at->copy()
                    : Carbon::parse((string) $session->started_at, $timezone);

                $isActive = empty($session->ended_at);
                $sessionEnd = $isActive
                    ? $now
                    : ($session->ended_at instanceof CarbonInterface
                        ? $session->ended_at->copy()
                        : Carbon::parse((string) $session->ended_at, $timezone));

                if ($isActive && ! $includeActiveSession) {
                    // Time expired or not granted: do not count active-session time further.
                    continue;
                }

                // For each bucket, count overlap seconds between [sessionStart, sessionEnd]
                // and [bucketStart, bucketEnd). bucketEnd is exclusive.
                foreach ($buckets as $i => $bucket) {
                    $bucketStart = $bucket['start'];
                    $bucketEndExclusive = $bucket['end'];

                    // Quick rejection: no overlap if session ends before bucket starts.
                    if ($sessionEnd->lte($bucketStart)) {
                        continue;
                    }

                    // Or if session starts after bucket ends.
                    if ($sessionStart->gte($bucketEndExclusive)) {
                        continue;
                    }

                    $segStart = $sessionStart->copy()->max($bucketStart);
                    $segEnd = $sessionEnd->copy()->min($bucketEndExclusive);

                    if ($segStart->lt($segEnd)) {
                        $valuesSeconds[$i] += (float) $segStart->diffInSeconds($segEnd);
                    }
                }
            }

            $series[] = [
                'device_id' => (int) $device->id,
                'device_name' => (string) $device->name,
                'values' => $this->convertSecondsArrayForUnit($valuesSeconds, $unit),
            ];
        }

        return [
            'range' => $range,
            'unit' => $unit,
            'labels' => $labels,
            'series' => $series,
        ];
    }

    /**
     * Daily shows minutes; longer ranges show hours.
     *
     * Why:
     * - Daily: parents think in "minutes per hour" (cap at 60).
     * - Weekly/Monthly/Yearly: parents think in "hours used" over longer windows.
     */
    private function unitForRange(string $range): string
    {
        return $range === 'daily' ? 'minutes' : 'hours';
    }

    /**
     * Convert per-bucket seconds into either minutes or hours.
     *
     * @param float[] $valuesSeconds
     * @return float[]
     */
    private function convertSecondsArrayForUnit(array $valuesSeconds, string $unit): array
    {
        if ($unit === 'hours') {
            // 2 decimals keeps hour values readable (e.g. 1.25h).
            return array_map(static fn(float $sec) => round($sec / 3600, 2), $valuesSeconds);
        }

        // Daily minutes can be 1 decimal for nicer-looking lines.
        return array_map(static fn(float $sec) => round($sec / 60, 1), $valuesSeconds);
    }

    /**
     * Build chart buckets for the given range.
     *
     * Each bucket is:
     * [
     *   'start' => Carbon,
     *   'end' => Carbon (exclusive),
     *   'label' => string
     * ]
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
     * Daily buckets: 24 hourly buckets for the current app timezone day.
     */
    private function buildDailyBuckets(Carbon $now): array
    {
        $start = $now->copy()->startOfDay();
        $buckets = [];

        for ($hour = 0; $hour < 24; $hour++) {
            $bucketStart = $start->copy()->addHours($hour);
            $bucketEndExclusive = $bucketStart->copy()->addHours(1);

            $buckets[] = [
                'start' => $bucketStart,
                'end' => $bucketEndExclusive,
                'label' => $bucketStart->format('H'),
            ];
        }

        return $buckets;
    }

    /**
     * Weekly buckets: current calendar week (Mon..Sun), one bucket per day.
     *
     * User-friendly label requirement:
     * - Always start at Monday
     * - Show the date at the top of Monday (two-line tick label)
     */
    private function buildWeeklyBuckets(Carbon $now): array
    {
        // Carbon's startOfWeek() uses the configured "week starts at" setting.
        // We explicitly anchor to Monday so the chart always starts with Mon.
        $startDay = $now->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $buckets = [];

        for ($i = 0; $i < 7; $i++) {
            $bucketStart = $startDay->copy()->addDays($i);
            $bucketEndExclusive = $bucketStart->copy()->addDay();

            $buckets[] = [
                'start' => $bucketStart,
                'end' => $bucketEndExclusive,
                // Chart.js supports multi-line labels by using arrays of strings.
                // User request: show the date for every day (Mon..Sun), not just Monday.
                // Example label: ["Mar 23", "Mon"]
                'label' => [$bucketStart->format('M d'), $bucketStart->format('D')],
            ];
        }

        return $buckets;
    }

    /**
     * Monthly buckets:
     * Current calendar month split into week buckets, with only the weeks that exist
     * inside the month (4 or 5 buckets in most cases).
     */
    private function buildMonthlyBuckets(Carbon $now): array
    {
        $monthStart = $now->copy()->startOfMonth();
        $nextMonthStart = $monthStart->copy()->addMonthNoOverflow();

        $buckets = [];

        $week = 1;
        $bucketStart = $monthStart->copy();
        while ($bucketStart->lt($nextMonthStart)) {
            $bucketEndExclusive = $bucketStart->copy()->addDays(7);
            if ($bucketEndExclusive->gt($nextMonthStart)) {
                $bucketEndExclusive = $nextMonthStart->copy();
            }

            $buckets[] = [
                'start' => $bucketStart->copy(),
                'end' => $bucketEndExclusive,
                'label' => 'Week ' . $week,
            ];

            $bucketStart = $bucketEndExclusive;
            $week++;
        }

        return $buckets;
    }

    /**
     * Yearly buckets: calendar year (Jan..Dec) with one bucket per month.
     *
     * User requirement: X-axis always starts with January.
     */
    private function buildYearlyBuckets(Carbon $now): array
    {
        $buckets = [];

        $yearStart = $now->copy()->startOfYear();
        for ($i = 0; $i < 12; $i++) {
            $bucketStart = $yearStart->copy()->addMonthsNoOverflow($i)->startOfMonth();
            $bucketEndExclusive = $bucketStart->copy()->addMonthNoOverflow();

            $buckets[] = [
                'start' => $bucketStart,
                'end' => $bucketEndExclusive,
                // User-friendly: Jan, Feb, Mar...
                'label' => $bucketStart->format('M'),
            ];
        }

        return $buckets;
    }
}
