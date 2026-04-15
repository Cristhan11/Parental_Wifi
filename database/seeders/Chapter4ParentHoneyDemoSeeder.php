<?php

namespace Database\Seeders;

use App\Models\AccessAttempt;
use App\Models\BrowsingLog;
use App\Models\Device;
use App\Models\ReportDispatchLog;
use App\Models\ReportingPreference;
use App\Models\ReportingRecipient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Demo rows for Chapter 4 screenshots: browsing logs, access attempts, reports UI.
 *
 * Parent account (created or updated): Parent Honey / 22ur0593@psu.edu.ph
 * Login after first create: email above, password `password` (only set on first insert).
 *
 * Run:  php artisan db:seed --class=Chapter4ParentHoneyDemoSeeder
 * Undo: php artisan db:seed --class=Chapter4ParentHoneyDemoPurgeSeeder
 *
 * To match a specific device MAC already in Accounts, change DEMO_DEVICE_MAC below before seeding.
 */
class Chapter4ParentHoneyDemoSeeder extends Seeder
{
    public const USER_EMAIL = '22ur0593@psu.edu.ph';

    public const USER_NAME = 'Parent Honey';

    /** Unique demo device; change to your table MAC if this row should be the screenshot device. */
    public const DEMO_DEVICE_MAC = 'C4:04:14:00:00:01';

    public const DEMO_RECIPIENT_EMAIL = 'ch4-demo-coparent@example.com';

    private const DEMO_SUBJECT_PREFIX = '[CH4-DEMO]';

    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => self::USER_EMAIL],
            [
                'name' => self::USER_NAME,
                'password' => Hash::make('password'),
                'role' => User::ROLE_PARENT,
                'email_verified_at' => now(),
                'approved_at' => now(),
                'rejected_at' => null,
            ]
        );

        $user->forceFill([
            'name' => self::USER_NAME,
            'email_verified_at' => $user->email_verified_at ?? now(),
            'approved_at' => $user->approved_at ?? now(),
            'rejected_at' => null,
        ])->save();

        $device = Device::updateOrCreate(
            ['mac_address' => self::DEMO_DEVICE_MAC],
            [
                'user_id' => $user->id,
                'name' => 'SUNNEY',
                'status' => 'active',
                'role' => 'child',
                'ip_address' => '192.168.4.31',
                'remaining_time_minutes' => 45,
                'total_time_allocated' => 120,
                'last_seen_at' => now()->subMinutes(12),
            ]
        );

        $device->browsingLogs()->delete();

        AccessAttempt::withoutEvents(function () use ($device): void {
            $device->accessAttempts()->delete();
        });

        $base = Carbon::now()->subDays(2)->setTime(9, 0, 0);

        $browsingRows = [
            ['url' => 'https://www.khanacademy.org/math', 'domain' => 'www.khanacademy.org', 'bytes_sent' => 8192, 'bytes_received' => 245_760, 'offset' => 0],
            ['url' => 'https://www.wikipedia.org/wiki/Solar_System', 'domain' => 'www.wikipedia.org', 'bytes_sent' => 4096, 'bytes_received' => 512_000, 'offset' => 25],
            ['url' => 'https://www.nasa.gov/', 'domain' => 'www.nasa.gov', 'bytes_sent' => 6144, 'bytes_received' => 890_000, 'offset' => 48],
            ['url' => 'https://www.britannica.com/science/photosynthesis', 'domain' => 'www.britannica.com', 'bytes_sent' => 3072, 'bytes_received' => 420_000, 'offset' => 70],
            ['url' => 'https://learning.google.com/', 'domain' => 'learning.google.com', 'bytes_sent' => 5120, 'bytes_received' => 180_000, 'offset' => 95],
            ['url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'domain' => 'www.youtube.com', 'bytes_sent' => 12_288, 'bytes_received' => 4_500_000, 'offset' => 130],
            ['url' => 'https://www.noisli.com/', 'domain' => 'www.noisli.com', 'bytes_sent' => 2048, 'bytes_received' => 96_000, 'offset' => 160],
            ['url' => 'https://www.codecademy.com/', 'domain' => 'www.codecademy.com', 'bytes_sent' => 7168, 'bytes_received' => 620_000, 'offset' => 190],
            ['url' => 'https://www.nationalgeographic.com/animals', 'domain' => 'www.nationalgeographic.com', 'bytes_sent' => 4096, 'bytes_received' => 1_100_000, 'offset' => 220],
            ['url' => 'https://www.weather.gov/', 'domain' => 'www.weather.gov', 'bytes_sent' => 2560, 'bytes_received' => 140_000, 'offset' => 250],
        ];

        foreach ($browsingRows as $row) {
            BrowsingLog::create([
                'device_id' => $device->id,
                'url' => $row['url'],
                'domain' => $row['domain'],
                'ip_address' => '142.250.' . random_int(10, 200) . '.' . random_int(1, 254),
                'user_agent' => 'Mozilla/5.0 (Linux; Android 13; Pixel6) AppleWebKit/537.36 Chrome/120.0.0.0 Mobile Safari/537.36',
                'bytes_sent' => $row['bytes_sent'],
                'bytes_received' => $row['bytes_received'],
                'visited_at' => (clone $base)->addMinutes((int) $row['offset']),
            ]);
        }

        AccessAttempt::withoutEvents(function () use ($device, $base): void {
            $attempts = [
                ['type' => 'blocked_website', 'url' => 'https://www.tiktok.com/', 'domain' => 'www.tiktok.com', 'offset' => 15],
                ['type' => 'flagged_website', 'url' => 'https://www.reddit.com/r/gaming', 'domain' => 'www.reddit.com', 'offset' => 40],
                ['type' => 'blocked_website', 'url' => 'https://www.instagram.com/', 'domain' => 'www.instagram.com', 'offset' => 62],
                ['type' => 'flagged_website', 'url' => 'https://discord.com/channels/@me', 'domain' => 'discord.com', 'offset' => 88],
                ['type' => 'blocked_website', 'url' => 'https://www.snapchat.com/', 'domain' => 'www.snapchat.com', 'offset' => 110],
                ['type' => 'flagged_website', 'url' => 'https://www.twitch.tv/', 'domain' => 'www.twitch.tv', 'offset' => 140],
                ['type' => 'blocked_website', 'url' => 'https://www.facebook.com/', 'domain' => 'www.facebook.com', 'offset' => 175],
            ];
            foreach ($attempts as $row) {
                AccessAttempt::create([
                    'device_id' => $device->id,
                    'type' => $row['type'],
                    'url' => $row['url'],
                    'domain' => $row['domain'],
                    'ip_address' => '151.101.' . random_int(1, 255) . '.' . random_int(1, 255),
                    'attempted_at' => (clone $base)->addMinutes((int) $row['offset']),
                ]);
            }
        });

        ReportingPreference::updateOrCreate(
            ['user_id' => $user->id],
            [
                'immediate_alerts_enabled' => true,
                'daily_digest_enabled' => true,
                'weekly_digest_enabled' => true,
                'monthly_digest_enabled' => true,
                'timezone' => 'Asia/Manila',
                'skip_empty_digests' => true,
            ]
        );

        ReportingRecipient::firstOrCreate(
            [
                'user_id' => $user->id,
                'email' => self::DEMO_RECIPIENT_EMAIL,
            ],
            [
                'label' => '[CH4-DEMO] Co-parent inbox',
                'is_enabled' => true,
            ]
        );

        ReportDispatchLog::query()
            ->where('user_id', $user->id)
            ->where('subject', 'like', self::DEMO_SUBJECT_PREFIX . '%')
            ->delete();

        $digestMeta = [
            'devices_active' => 1,
            'browsing_events' => 10,
            'blocked_attempts' => 4,
            'flagged_visits' => 3,
        ];

        $stamps = [
            ['days' => 0, 'hours' => 3, 'frequency' => 'daily', 'status' => 'sent', 'recipient' => self::USER_EMAIL],
            ['days' => 1, 'hours' => 10, 'frequency' => 'daily', 'status' => 'sent', 'recipient' => self::DEMO_RECIPIENT_EMAIL],
            ['days' => 3, 'hours' => 8, 'frequency' => 'weekly', 'status' => 'sent', 'recipient' => self::USER_EMAIL],
            ['days' => 7, 'hours' => 14, 'frequency' => 'weekly', 'status' => 'skipped', 'recipient' => null, 'error' => null],
            ['days' => 14, 'hours' => 9, 'frequency' => 'monthly', 'status' => 'failed', 'recipient' => self::DEMO_RECIPIENT_EMAIL, 'error' => 'SMTP connection timed out (demo row)'],
        ];

        foreach ($stamps as $i => $row) {
            $sentAt = now()->subDays($row['days'])->subHours($row['hours']);
            $log = new ReportDispatchLog([
                'user_id' => $user->id,
                'report_type' => 'digest',
                'frequency' => $row['frequency'],
                'recipient_email' => $row['recipient'],
                'subject' => self::DEMO_SUBJECT_PREFIX . ' ' . ucfirst($row['frequency']) . ' digest — Household summary',
                'period_start' => (clone $sentAt)->subDay(),
                'period_end' => $sentAt,
                'status' => $row['status'],
                'meta' => array_merge($digestMeta, ['demo_index' => $i]),
                'error_message' => $row['error'] ?? null,
                'sent_at' => $row['status'] === 'sent' ? $sentAt : null,
            ]);
            $log->created_at = $sentAt;
            $log->updated_at = $sentAt;
            $log->save();
        }

        $this->command->info('Chapter 4 demo data ready for ' . self::USER_EMAIL);
        $this->command->info('Device MAC: ' . self::DEMO_DEVICE_MAC . ' (change constant in seeder to match an existing row if needed)');
        $this->command->info('Purge: php artisan db:seed --class=Chapter4ParentHoneyDemoPurgeSeeder');
    }
}
