<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Device Schedule Model
 * 
 * Stores time-based internet access rules for devices.
 */
class DeviceSchedule extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'device_id',
        'day_of_week',
        'start_time',
        'end_time',
        'duration_limit_minutes',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the device this schedule belongs to.
     * 
     * Relationship: belongsTo - One schedule belongs to one device
     * 
     * Usage Example:
     * $schedule = DeviceSchedule::find(1);
     * $device = $schedule->device; // Gets the Device this schedule applies to
     * 
     * echo "Schedule for: {$device->name}";
     * echo "Day: {$schedule->day_of_week}"; // "monday", "tuesday", etc.
     * echo "Time: {$schedule->start_time} to {$schedule->end_time}";
     * echo "Daily limit: " . ($schedule->duration_limit_minutes ?? 'No limit') . " minutes";
     * 
     * // Check if schedule is currently active
     * if ($schedule->is_active && date('l') === ucfirst($schedule->day_of_week)) {
     *     echo "Schedule is active today";
     * }
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}

