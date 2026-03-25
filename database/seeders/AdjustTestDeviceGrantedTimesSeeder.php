<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Sets different granted internet minutes for dashboard testing (e.g. Test Device vs Peter).
 *
 * Usage:
 *   php artisan db:seed --class=AdjustTestDeviceGrantedTimesSeeder
 *
 * Picks the first parent user who owns both device names; adjust $minutes map below as needed.
 */
class AdjustTestDeviceGrantedTimesSeeder extends Seeder
{
    /**
     * name => remaining_time_minutes (also sets total_time_allocated to the same value).
     *
     * @var array<string, int>
     */
    private array $minutes = [
        'Test Device' => 45,
        'Peter' => 12,
    ];

    public function run(): void
    {
        $parent = User::query()
            ->where('role', 'parent')
            ->whereHas('devices', fn ($q) => $q->where('name', 'Test Device'))
            ->whereHas('devices', fn ($q) => $q->where('name', 'Peter'))
            ->first();

        if (! $parent) {
            // Fallback: any user that has both names (e.g. custom role)
            $parent = User::query()
                ->whereHas('devices', fn ($q) => $q->where('name', 'Test Device'))
                ->whereHas('devices', fn ($q) => $q->where('name', 'Peter'))
                ->first();
        }

        if (! $parent) {
            $this->command?->error('No user found with both devices named "Test Device" and "Peter".');
            $this->command?->line('Create those devices first or edit this seeder to match your names.');

            return;
        }

        foreach ($this->minutes as $name => $mins) {
            $updated = Device::query()
                ->where('user_id', $parent->id)
                ->where('name', $name)
                ->update([
                    'remaining_time_minutes' => $mins,
                    'total_time_allocated' => $mins,
                ]);

            if ($updated) {
                $this->command?->info("Set [{$name}] to {$mins} min granted (user: {$parent->email}).");
            } else {
                $this->command?->warn("No device named [{$name}] for user {$parent->email}.");
            }
        }
    }
}
