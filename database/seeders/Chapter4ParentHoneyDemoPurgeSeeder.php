<?php

namespace Database\Seeders;

use App\Models\AccessAttempt;
use App\Models\BrowsingLog;
use App\Models\Device;
use App\Models\ReportDispatchLog;
use App\Models\ReportingRecipient;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Removes rows created for Chapter 4 screenshots (see Chapter4ParentHoneyDemoSeeder).
 *
 * Run: php artisan db:seed --class=Chapter4ParentHoneyDemoPurgeSeeder
 *
 * Does not delete the Parent Honey user account—only demo device, logs, demo recipients, and demo dispatch history.
 */
class Chapter4ParentHoneyDemoPurgeSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->where('email', Chapter4ParentHoneyDemoSeeder::USER_EMAIL)->first();

        if (! $user) {
            $this->command->warn('No user with email ' . Chapter4ParentHoneyDemoSeeder::USER_EMAIL);

            return;
        }

        ReportingRecipient::query()
            ->where('user_id', $user->id)
            ->where(function ($q): void {
                $q->where('email', Chapter4ParentHoneyDemoSeeder::DEMO_RECIPIENT_EMAIL)
                    ->orWhere('label', 'like', '[CH4-DEMO]%');
            })
            ->delete();

        ReportDispatchLog::query()
            ->where('user_id', $user->id)
            ->where('subject', 'like', '[CH4-DEMO]%')
            ->delete();

        $device = Device::query()
            ->where('mac_address', Chapter4ParentHoneyDemoSeeder::DEMO_DEVICE_MAC)
            ->where('user_id', $user->id)
            ->first();

        if ($device) {
            BrowsingLog::query()->where('device_id', $device->id)->delete();
            AccessAttempt::withoutEvents(function () use ($device): void {
                AccessAttempt::query()->where('device_id', $device->id)->delete();
            });
            $device->delete();
        }

        $this->command->info('Chapter 4 demo data purged for ' . Chapter4ParentHoneyDemoSeeder::USER_EMAIL);
    }
}
