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
 *   - Daily: contrasting minutes per hour (light / heavy / medium child)
 *   - Weekly: large per-day totals on past days (today stays small vs daily buckets)
 *   - Monthly: strong per–week-bucket hour totals (spread across days in bucket)
 *   - Yearly: strong per-month hour totals (spread across days in month)
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

        // Daily chart: large visual spread between children, still ≤60 minutes per hour bucket.
        $profileIndex = $this->getProfileIndex($device);
        $hours = $this->getDailyHoursForProfile($profileIndex);
        $hash = $this->getDeviceHash($device);

        foreach ($hours as $idx => $hour) {
            $bucketStart = $dayStart->copy()->setTime($hour, 0, 0);

            if ($bucketStart->gte($now)) {
                continue;
            }

            $startMinute = match ($profileIndex) {
                0 => 12 + (($hash + ($idx * 13)) % 10), // 12..21 — light user, shorter sessions
                1 => 1 + (($hash + ($idx * 13)) % 6), // 1..6 — heavy user, room for long block in hour
                default => 5 + (($hash + ($idx * 13)) % 18), // 5..22
            };

            $durationMinutes = match ($profileIndex) {
                0 => 14 + (($hash + ($idx * 17)) % 12), // ~14–25 min
                1 => 52 + (($hash + ($idx * 17)) % 7), // ~52–58 min (near cap)
                default => 30 + (($hash + ($idx * 17)) % 16), // ~30–45 min
            };

            if ($startMinute + $durationMinutes > 59) {
                $durationMinutes = max(1, 59 - $startMinute);
            }

            $start = $bucketStart->copy()->addMinutes($startMinute);
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
     * Seed sessions for the Weekly chart buckets (last 7 calendar days).
     *
     * We create one ended session per day at a consistent time.
     */
    private function seedDeviceWeekly(Device $device, Carbon $now): void
    {
        $startDay = $now->copy()->startOfDay()->subDays(6);

        // Weekly buckets = one calendar day each. Past days get large contrasting totals;
        // today stays small and outside daily seed hours so Daily ≤60 min/hour still holds.
        $profileIndex = $this->getProfileIndex($device);
        $dailyHours = $this->getDailyHoursForProfile($profileIndex);
        $nonDailyHour = $this->getFirstNonDailyHour($dailyHours);
        $hash = $this->getDeviceHash($device);

        for ($i = 0; $i < 7; $i++) {
            $day = $startDay->copy()->addDays($i);
            $isToday = $day->isSameDay($now);

            if ($isToday) {
                $start = $day->copy()->setTime($nonDailyHour, 5 + (($hash + $i) % 20), 0);
                $durationMinutes = min(40, 25 + (($hash + $i) % 12));
                $end = $start->copy()->addMinutes($durationMinutes);
                if ($end->gte($now)) {
                    $end = $now->copy()->subMinutes(1);
                }
                if ($end->gt($start)) {
                    $this->upsertSession($device->id, $start, $end);
                }

                continue;
            }

            $dayOfWeek = (int) $day->format('N');
            $isWeekend = $dayOfWeek >= 6;

            // Target minutes for this calendar day (max 23h so we stay under 24h bucket cap).
            $targetMinutes = match ($profileIndex) {
                // Light: ~0.5–2.5 h / day
                0 => ($isWeekend ? 35 : 55) + (($hash + $i * 17) % 90),
                // Heavy: ~10–18 h / day on weekdays, slightly less on weekend
                1 => ($isWeekend ? 420 : 660) + (($hash + $i * 19) % 240),
                // Medium: ~4–8 h
                default => ($isWeekend ? 200 : 320) + (($hash + $i * 23) % 120),
            };

            $targetMinutes = min($targetMinutes, 23 * 60);

            $this->seedBlockMinutesOnPastDay($device->id, $day, $targetMinutes);
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

        // Month → week-sized buckets (≤7 days). Target total minutes per bucket with a big spread between children.
        $nextMonthStart = $monthStart->copy()->addMonthNoOverflow();
        $weekStart = $monthStart->copy();
        $weekNumber = 1;

        $targetMinutesPerWeek = match ($profileIndex) {
            // Light: ~2–5 h per bucket
            0 => [140, 100, 180, 120, 160],
            // Heavy: ~25–45 h per bucket (still under 168 h cap)
            1 => [2200, 2600, 1800, 2400, 2000],
            // Medium: ~10–18 h
            default => [720, 900, 600, 840, 780],
        };

        while ($weekStart->lt($nextMonthStart)) {
            $bucketEndExclusive = $weekStart->copy()->addDays(7);
            if ($bucketEndExclusive->gt($nextMonthStart)) {
                $bucketEndExclusive = $nextMonthStart->copy();
            }

            if ($weekStart->gte($bucketEndExclusive)) {
                break;
            }

            $target = $targetMinutesPerWeek[$weekNumber - 1] ?? 400;
            $target += ($hash + $weekNumber * 31) % 120;
            $target = min($target, 165 * 60);

            $this->seedMinutesAcrossDateRange(
                $device->id,
                $weekStart->copy(),
                $bucketEndExclusive->copy(),
                $now,
                $target,
                $dailyHours,
                $nonDailyHour
            );

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

        $yearStart = $now->copy()->startOfYear();
        $currentMonthStart = $now->copy()->startOfMonth();

        // Total hours per calendar month (spread across days in the month). Large gaps between profiles.
        $monthTargetHours = match ($profileIndex) {
            0 => [1.0, 1.5, 2.0, 1.0, 2.5, 1.0, 1.5, 2.0, 1.0, 1.5, 2.0, 1.0],
            1 => [18.0, 25.0, 32.0, 28.0, 45.0, 52.0, 48.0, 38.0, 30.0, 35.0, 22.0, 16.0],
            default => [7.0, 10.0, 8.5, 12.0, 9.0, 8.0, 11.0, 9.5, 8.0, 10.0, 7.5, 8.0],
        };

        for ($i = 0; $i < 12; $i++) {
            $monthStart = $yearStart->copy()->addMonthsNoOverflow($i)->startOfMonth();

            if ($monthStart->gt($currentMonthStart)) {
                continue;
            }

            $monthEndExclusive = $monthStart->copy()->addMonthNoOverflow();

            $hours = $monthTargetHours[$i] ?? 3.0;
            $hours += (($hash + $i * 17) % 50) / 10.0;
            $maxHoursInMonth = $monthStart->daysInMonth * 24;
            $hours = min($hours, $maxHoursInMonth - 0.1);

            $targetMinutes = (int) round($hours * 60);

            $this->seedMinutesAcrossDateRange(
                $device->id,
                $monthStart->copy(),
                $monthEndExclusive->copy(),
                $now,
                $targetMinutes,
                $dailyHours,
                $nonDailyHour
            );
        }
    }

    /**
     * Place up to $totalMinutes of usage on a single past calendar day (one or more contiguous blocks).
     */
    private function seedBlockMinutesOnPastDay(int $deviceId, Carbon $day, int $totalMinutes): void
    {
        $day = $day->copy()->startOfDay();
        $totalMinutes = min(max($totalMinutes, 0), 23 * 60 + 45);

        $remaining = $totalMinutes;
        $slot = 0;
        $baseHour = 6 + ($deviceId % 5);

        while ($remaining >= 10) {
            $hour = min(20, $baseHour + $slot * 3);
            $start = $day->copy()->setTime($hour, ($deviceId * 7 + $slot * 13) % 40, 0);
            $chunk = min($remaining, 9 * 60);

            $end = $start->copy()->addMinutes($chunk);
            if (! $end->isSameDay($start)) {
                $end = $day->copy()->setTime(23, 55, 0);
            }
            if ($end->lte($start)) {
                break;
            }

            $this->upsertSession($deviceId, $start, $end);
            $remaining -= (int) $start->diffInMinutes($end);
            $slot++;
            if ($slot > 10) {
                break;
            }
        }
    }

    /**
     * Spread $totalMinutes across each day in [rangeStart, rangeEndExclusive) that is not in the future.
     * Past days use {@see seedBlockMinutesOnPastDay}; “today” gets a short session at $nonDailyHour.
     */
    private function seedMinutesAcrossDateRange(
        int $deviceId,
        Carbon $rangeStart,
        Carbon $rangeEndExclusive,
        Carbon $now,
        int $totalMinutes,
        array $dailyHours,
        int $nonDailyHour
    ): void {
        if ($totalMinutes < 5) {
            return;
        }

        $days = [];
        $d = $rangeStart->copy()->startOfDay();
        while ($d->lt($rangeEndExclusive)) {
            if ($d->lte($now->copy()->startOfDay())) {
                $days[] = $d->copy();
            }
            $d->addDay();
        }

        $count = count($days);
        if ($count === 0) {
            return;
        }

        $per = intdiv($totalMinutes, $count);
        $rem = $totalMinutes % $count;

        foreach ($days as $idx => $day) {
            $mins = $per + ($idx < $rem ? 1 : 0);
            if ($mins < 5) {
                continue;
            }

            if ($day->isSameDay($now)) {
                $mins = min($mins, 45);
                $start = $day->copy()->setTime($nonDailyHour, 10 + ($deviceId % 18), 0);
                $end = $start->copy()->addMinutes($mins);
                if ($end->gte($now)) {
                    $end = $now->copy()->subMinute();
                }
                if ($end->gt($start)) {
                    $this->upsertSession($deviceId, $start, $end);
                }

                continue;
            }

            $this->seedBlockMinutesOnPastDay($deviceId, $day, $mins);
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
        return (int) (sprintf('%u', crc32($nameLower.'|'.$device->id)) % 3);
    }

    /**
     * Which hourly buckets should contain “main usage” for this device pattern.
     */
    private function getDailyHoursForProfile(int $profileIndex): array
    {
        return match ($profileIndex) {
            // Light: few hours, low minutes → flat low line
            0 => [8, 11, 14],
            // Heavy: many hours, near 60 min each → tall “comb”
            1 => [7, 8, 9, 10, 12, 13, 14, 15, 16, 17, 18, 19],
            // Medium
            default => [10, 12, 14, 16, 18, 20],
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
        return (int) (sprintf('%u', crc32(mb_strtolower(trim($device->name)).'|'.$device->id)) % 1000000);
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
