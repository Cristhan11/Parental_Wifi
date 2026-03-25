<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\DeviceSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * DummyUsageChartDataSeeder
 *
 * Purpose:
 * - Populate `device_sessions` with ended sessions so the dashboard chart has
 *   visible non-zero values even before real network tracking produces data.
 *
 * Why this seeder exists:
 * - The dashboard graph counts overlap of each session with chart buckets.
 * - If there are no `device_sessions` rows, all chart series will be 0 (flat line),
 *   which looks broken during UI testing.
 *
 * What it does:
 * - Picks a parent user.
 * - Uses preferred device names (John, Peter, Test Device) if found; otherwise picks
 *   up to 3 child devices under that parent.
 * - Creates ended sessions for:
 *   - Daily: several hour buckets today
 *   - Weekly: one session per day for the last 7 days
 *   - Monthly: several sessions in the current month
 *   - Yearly: one session per month for the last 12 months
 *
 * Usage:
 *   php artisan db:seed --class=DummyUsageChartDataSeeder
 */
class DummyUsageChartDataSeeder extends Seeder
{
    /**
     * Preferred device names (used to make the legend match your screenshot).
     *
     * @var array<int, string>
     */
    private array $preferredDeviceNames = [
        'John',
        'Peter',
        'Test Device',
    ];

    public function run(): void
    {
        $timezone = (string) (config('app.timezone') ?: 'Asia/Manila');
        $now = Carbon::now($timezone);

        $parent = $this->pickParentUser($now);
        $devices = $this->pickDevices($parent);

        if ($devices->isEmpty()) {
            $this->command?->warn('No child devices found for dummy usage seeding.');
            return;
        }

        foreach ($devices as $device) {
            // Clean up prior dummy sessions so we don't “double count” and inflate
            // chart buckets. We only remove ended sessions with 0 bytes in the last
            // ~400 days, which matches the dummy seeder's own inserts.
            $this->cleanupPreviousDummySessions($device, $now);

            $this->seedDeviceDaily($device, $now);
            $this->seedDeviceWeekly($device, $now);
            $this->seedDeviceMonthly($device, $now);
            $this->seedDeviceYearly($device, $now);
        }
    }

    /**
     * Remove older dummy sessions so the chart doesn't get double-counted.
     *
     * Why:
     * - Dummy sessions are re-generated deterministically based on device identity,
     *   but their timestamps also change when you re-run the seeder on another day.
     * - Without cleanup, old dummy rows remain and can inflate bucket totals.
     */
    private function cleanupPreviousDummySessions(Device $device, Carbon $now): void
    {
        $cutoff = $now->copy()->subDays(400);

        DeviceSession::query()
            ->where('device_id', $device->id)
            ->whereNotNull('ended_at')
            ->where('total_bytes_sent', 0)
            ->where('total_bytes_received', 0)
            ->where('started_at', '>=', $cutoff)
            ->delete();
    }

    /**
     * Pick a parent user to seed under.
     *
     * Why:
     * - We seed sessions for child devices that belong to a specific parent,
     *   matching the dashboard authorization model.
     */
    private function pickParentUser(Carbon $now): ?User
    {
        // Prefer a parent that already has at least one device matching your common names.
        $parent = User::query()
            ->where('role', 'parent')
            ->whereHas('devices', function ($q) {
                $q->whereIn('name', $this->preferredDeviceNames);
            })
            ->withCount('devices')
            ->first();

        if ($parent) {
            return $parent;
        }

        // Fallback: any parent with devices.
        return User::query()
            ->where('role', 'parent')
            ->whereHas('devices')
            ->first();
    }

    /**
     * Choose up to 3 child devices under the chosen parent.
     *
     * Why:
     * - The chart is multi-series (one line per device).
     * - Keeping this small (<= 3) avoids unreadable legends while testing.
     */
    private function pickDevices(?User $parent)
    {
        if (! $parent) {
            return collect();
        }

        $preferred = Device::query()
            ->where('user_id', $parent->id)
            ->whereIn('name', $this->preferredDeviceNames)
            ->get(['id', 'name', 'user_id']);

        if ($preferred->count() > 0) {
            return $preferred->take(3);
        }

        return Device::query()
            ->where('user_id', $parent->id)
            ->where('role', 'child')
            ->orderBy('id')
            ->take(3)
            ->get(['id', 'name', 'user_id']);
    }

    /**
     * Seed sessions for the Daily chart buckets.
     *
     * We create ended sessions in the current day across multiple hour buckets.
     * The chart uses overlap math, so a session like 01:15-03:45 counts in 01, 02, 03.
     */
    private function seedDeviceDaily(Device $device, Carbon $now): void
    {
        $dayStart = $now->copy()->startOfDay();

        // Daily chart “shape” requirements:
        // - Each device should look different (distinct hours/durations).
        // - Each hour bucket should stay <= 60 minutes so the Daily Y-axis cap makes sense.
        $profileIndex = $this->getProfileIndex($device);
        $hours = $this->getDailyHoursForProfile($profileIndex);
        $hash = $this->getDeviceHash($device);

        foreach ($hours as $idx => $hour) {
            $bucketStart = $dayStart->copy()->setTime($hour, 0, 0);

            // Never create “future” sessions (ended_at must be <= now).
            if ($bucketStart->gte($now)) {
                continue;
            }

            // Keep session fully inside the hour:
            // - start in the first ~25 minutes
            // - end within 55 minutes of the bucketStart
            $startMinute = 5 + (($hash + ($idx * 13)) % 20); // 5..24
            $durationMinutes = 20 + (($hash + ($idx * 17)) % 35); // 20..54
            if ($startMinute + $durationMinutes > 55) {
                $durationMinutes = 55 - $startMinute; // ensure <=55 minutes into the hour
            }

            $start = $bucketStart->copy()->addMinutes($startMinute);
            $end = $start->copy()->addMinutes($durationMinutes);

            if ($end->gte($now)) {
                // Still create a short ended session if we are mid-bucket.
                $end = $now->copy()->subMinutes(1);
            }

            if ($end->lte($start)) {
                continue;
            }

            $this->upsertSession($device->id, $start, $end);
        }
    }

    /**
     * Seed sessions for the Weekly chart buckets (last 7 calendar days).
     *
     * We create one ended session per day at a consistent time.
     */
    private function seedDeviceWeekly(Device $device, Carbon $now): void
    {
        $startDay = $now->copy()->startOfDay()->subDays(6);

        // Weekly shape requirements:
        // - Different devices should have different weekly “peaks”.
        // - Avoid overlapping today's DAILY-hour buckets so Daily Y-axis stays <= 60.
        $profileIndex = $this->getProfileIndex($device);
        $dailyHours = $this->getDailyHoursForProfile($profileIndex);
        $nonDailyHour = $this->getFirstNonDailyHour($dailyHours);
        $hash = $this->getDeviceHash($device);

        for ($i = 0; $i < 7; $i++) {
            $day = $startDay->copy()->addDays($i);

            $dayOfWeek = (int) $day->format('N'); // 1=Mon..7=Sun
            $isWeekend = $dayOfWeek >= 6;

            // Choose start hour. For "today", ensure it doesn't overlap daily hours.
            if ($i === 6) {
                $startHour = $nonDailyHour;
            } else {
                $startHourBase = match ($profileIndex) {
                    0 => 9,
                    1 => 14,
                    default => 11,
                };

                $startHour = ($startHourBase + (($hash + $i) % 3)) % 24;
            }

            $startMinute = 5 + (($hash + ($i * 7)) % 25); // 5..29

            $durationMinutes = match ($profileIndex) {
                0 => ($isWeekend ? 20 : 45) + (($hash + $i) % 10), // John: lower on weekend
                1 => ($isWeekend ? 45 : 25) + (($hash + $i) % 10), // Peter: higher on weekend
                default => 30 + (($hash + $i) % 25), // Test: steady
            };

            // Keep within a reasonable daily time window.
            $durationMinutes = min($durationMinutes, 75);
            if ($i === 6) {
                // Today is also used by the Daily chart; keep the weekly slice small
                // so the daily hour bucket doesn't exceed the 60-minute cap.
                $durationMinutes = min($durationMinutes, 45);
            }

            $start = $day->copy()->setTime($startHour, $startMinute, 0);
            $end = $start->copy()->addMinutes($durationMinutes);

            if ($end->gte($now) && $i === 6) {
                $end = $now->copy()->subMinutes(1);
            }

            if ($end->lte($start)) {
                continue;
            }

            $this->upsertSession($device->id, $start, $end);
        }
    }

    /**
     * Seed sessions for the Monthly chart buckets.
     *
     * The chart splits the current month by day buckets, so we create sessions
     * on several days in the current month (not every day, to keep the dataset small).
     */
    private function seedDeviceMonthly(Device $device, Carbon $now): void
    {
        $monthStart = $now->copy()->startOfMonth();
        $profileIndex = $this->getProfileIndex($device);
        $dailyHours = $this->getDailyHoursForProfile($profileIndex);
        $nonDailyHour = $this->getFirstNonDailyHour($dailyHours);
        $hash = $this->getDeviceHash($device);

        // Monthly chart groups the month into 4-5 "week buckets".
        // We create one ended session in each existing week bucket so the chart
        // is visible and each device has a distinct pattern.
        $nextMonthStart = $monthStart->copy()->addMonthNoOverflow();
        $weekStart = $monthStart->copy();
        $weekNumber = 1;

        $durationPattern = match ($profileIndex) {
            0 => [55, 35, 25, 45, 30], // John: peaks earlier
            1 => [20, 30, 60, 25, 40], // Peter: peaks mid-month
            default => [35, 35, 35, 35, 35], // Test device: steady
        };

        while ($weekStart->lt($nextMonthStart)) {
            $bucketEndExclusive = $weekStart->copy()->addDays(7);
            if ($bucketEndExclusive->gt($nextMonthStart)) {
                $bucketEndExclusive = $nextMonthStart->copy();
            }

            $daysInBucket = $weekStart->diffInDays($bucketEndExclusive);
            if ($daysInBucket <= 0) {
                break;
            }

            $offsetDay = min(2 + $profileIndex, $daysInBucket - 1);

            $start = $weekStart->copy()->addDays($offsetDay)->setTime(
                11 + ($profileIndex * 2),
                5 + (($hash + $weekNumber) % 20),
                0
            );

            // Avoid affecting today's daily hour buckets:
            // if the session lands on today and hour is part of today's daily buckets,
            // shift it to a non-daily hour.
            if ($start->isSameDay($now) && in_array((int) $start->hour, $dailyHours, true)) {
                $start = $start->copy()->setTime($nonDailyHour, (int) $start->minute, 0);
            }

            if ($start->gte($now)) {
                $weekStart = $bucketEndExclusive;
                $weekNumber++;
                continue;
            }

            $durationMinutes = $durationPattern[$weekNumber - 1] ?? 30;
            $durationMinutes += ($hash % 7); // small deterministic variation
            $durationMinutes = min($durationMinutes, 70);
            if ($start->isSameDay($now)) {
                // Daily chart cap compatibility.
                $durationMinutes = min($durationMinutes, 55);
            }

            $end = $start->copy()->addMinutes($durationMinutes);
            if ($end->gte($now)) {
                $end = $now->copy()->subMinutes(1);
            }

            if ($end->lte($start)) {
                $weekStart = $bucketEndExclusive;
                $weekNumber++;
                continue;
            }

            $this->upsertSession($device->id, $start, $end);

            $weekStart = $bucketEndExclusive;
            $weekNumber++;
        }
    }

    /**
     * Seed sessions for the Yearly chart buckets (last 12 months, one point per month).
     *
     * We create one ended session in each of the last 12 months. This ensures
     * the yearly chart isn't empty and matches the “one point per month” requirement.
     */
    private function seedDeviceYearly(Device $device, Carbon $now): void
    {
        $profileIndex = $this->getProfileIndex($device);
        $dailyHours = $this->getDailyHoursForProfile($profileIndex);
        $nonDailyHour = $this->getFirstNonDailyHour($dailyHours);
        $hash = $this->getDeviceHash($device);

        // Yearly chart requirement:
        // - X-axis always starts with January
        // - One bucket per month (calendar year)
        $yearStart = $now->copy()->startOfYear();
        $currentMonthStart = $now->copy()->startOfMonth();

        // Different devices have different peak months, making the chart easy to read.
        $durationPattern = match ($profileIndex) {
            0 => [20, 25, 30, 15, 40, 70, 65, 45, 35, 25, 30, 20], // John peaks mid-year
            1 => [40, 35, 20, 55, 60, 30, 25, 20, 50, 70, 60, 45], // Peter peaks around May/Oct
            default => [25, 30, 35, 40, 25, 30, 35, 40, 45, 30, 25, 20], // Test steady
        };

        for ($i = 0; $i < 12; $i++) {
            $monthStart = $yearStart->copy()->addMonthsNoOverflow($i)->startOfMonth();

            // Can't create ended sessions in the future.
            if ($monthStart->gt($currentMonthStart)) {
                continue;
            }

            $daysInMonth = $monthStart->daysInMonth;
            $day = min(10 + ($profileIndex * 2), $daysInMonth);

            // Avoid clashing with today's day-of-month in the current month.
            if ($monthStart->isSameMonth($now) && $day === $now->day) {
                $day = min($day + 1, $daysInMonth);
            }

            $startHour = min(12 + ($profileIndex * 2) + ($hash % 3), 21);
            $start = $monthStart->copy()->setDay($day)->setTime($startHour, 0, 0);

            // Avoid overlapping DAILY bucket hours if it happens to land on today.
            if ($start->isSameDay($now) && in_array((int) $start->hour, $dailyHours, true)) {
                $start = $start->copy()->setTime($nonDailyHour, 0, 0);
            }

            if ($start->gte($now)) {
                continue;
            }

            $durationMinutes = $durationPattern[$i] ?? 30;
            $durationMinutes += ($hash % 9); // deterministic variation
            $durationMinutes = min($durationMinutes, 90);
            if ($start->isSameDay($now)) {
                // Daily chart cap compatibility.
                $durationMinutes = min($durationMinutes, 55);
            }

            $end = $start->copy()->addMinutes($durationMinutes);
            if ($end->gte($now)) {
                $end = $now->copy()->subMinutes(1);
            }

            if ($end->lte($start)) {
                continue;
            }

            $this->upsertSession($device->id, $start, $end);
        }
    }

    /**
     * Map a device identity to one of the dummy "usage patterns".
     *
     * Why:
     * - Without this, all child devices get identical dummy sessions.
     * - That would make the chart lines overlap and look wrong.
     */
    private function getProfileIndex(Device $device): int
    {
        $nameLower = mb_strtolower(trim($device->name));

        foreach ($this->preferredDeviceNames as $idx => $preferred) {
            $preferredLower = mb_strtolower(trim($preferred));
            if ($nameLower === $preferredLower || str_contains($nameLower, $preferredLower)) {
                return (int) $idx;
            }
        }

        // Fallback: stable pseudo-random profile (0..2).
        return (int) (sprintf('%u', crc32($nameLower . '|' . $device->id)) % 3);
    }

    /**
     * Which hourly buckets should contain “main usage” for this device pattern.
     */
    private function getDailyHoursForProfile(int $profileIndex): array
    {
        return match ($profileIndex) {
            0 => [0, 2, 4, 6, 8, 10, 12, 14], // John: mostly early
            1 => [1, 3, 5, 9, 11, 13, 15, 17], // Peter: staggered + mid
            default => [6, 7, 8, 14, 16, 18, 20, 22], // Test device: evening-heavy
        };
    }

    /**
     * Find the first hour (0..23) not present in today's daily bucket list.
     *
     * Why:
     * - Weekly/monthly/yearly dummy sessions should not “double count” into the
     *   same daily hour buckets, otherwise Daily could exceed the 60-minute cap.
     */
    private function getFirstNonDailyHour(array $dailyHours): int
    {
        $dailySet = array_fill(0, 24, false);
        foreach ($dailyHours as $h) {
            $dailySet[(int) $h] = true;
        }

        for ($hour = 0; $hour < 24; $hour++) {
            if (! $dailySet[$hour]) {
                return $hour;
            }
        }

        return 23;
    }

    /**
     * Stable hash used to vary dummy minutes without randomness.
     */
    private function getDeviceHash(Device $device): int
    {
        return (int) (sprintf('%u', crc32(mb_strtolower(trim($device->name)) . '|' . $device->id)) % 1000000);
    }

    /**
     * Derive deterministic variation numbers from the device identity.
     *
     * Why:
     * - We want dummy series to be different for each child device,
     *   but still repeatable (so the graph doesn't change randomly every run).
     */
    private function getVariation(Device $device): array
    {
        // Convert crc32 to unsigned so modulo math behaves consistently across PHP builds.
        $hash = (int) sprintf('%u', crc32(strtolower($device->name) . '|' . $device->id));

        return [
            'dailyHourRotation' => $hash % 8,
            'dailyMinuteOffset' => $hash % 25, // 0..24
            'dailyDurationBase' => 15 + ($hash % 35), // 15..49 minutes

            'weeklyStartHour' => 8 + ($hash % 5), // 8..12
            'weeklyStartMinute' => 5 + ($hash % 20), // 5..24
            'weeklyDurationBase' => 25 + ($hash % 40), // 25..64 minutes

            'monthlyDayRotation' => $hash % 5, // 0..4 (baseDaysToUse count)
            'monthlyStartHour' => 11 + ($hash % 4), // 11..14
            'monthlyStartMinute' => 0 + ($hash % 20), // 0..19
            'monthlyDurationBase' => 35 + ($hash % 40), // 35..74 minutes

            'yearlyDayOffset' => $hash % 21,
            'yearlyStartHour' => 9 + ($hash % 6), // 9..14
            'yearlyDurationBase' => 20 + ($hash % 60), // 20..79 minutes
        ];
    }

    /**
     * Rotate array items to create stable variety.
     */
    private function rotateArray(array $items, int $rotation): array
    {
        $count = count($items);
        if ($count === 0) {
            return $items;
        }

        $rotation = $rotation % $count;
        if ($rotation === 0) {
            return $items;
        }

        return array_merge(
            array_slice($items, $rotation),
            array_slice($items, 0, $rotation)
        );
    }

    /**
     * Upsert (update or create) a DeviceSession row deterministically.
     *
     * Why:
     * - Running the seeder multiple times should not create duplicate rows
     *   that would “double count” on the chart.
     */
    private function upsertSession(int $deviceId, Carbon $start, Carbon $end): void
    {
        $durationSeconds = $start->diffInSeconds($end);

        DeviceSession::query()->updateOrCreate(
            [
                'device_id' => $deviceId,
                'started_at' => $start,
                'ended_at' => $end,
            ],
            [
                'duration_seconds' => $durationSeconds,
                'total_bytes_sent' => 0,
                'total_bytes_received' => 0,
            ]
        );
    }
}
