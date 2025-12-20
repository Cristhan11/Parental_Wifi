<?php

namespace App\Models;

use Carbon\Carbon;
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

    /**
     * Get start_time as Carbon instance.
     * 
     * TIME columns return strings, so we convert to Carbon for easier manipulation.
     * This allows us to use ->format() in views and jobs.
     * 
     * @param mixed $value The raw value from database (string like "15:00:00" or null)
     * @return Carbon Carbon instance representing the time
     */
    public function getStartTimeAttribute($value)
    {
        if ($value instanceof Carbon) {
            return $value;
        }
        if (empty($value)) {
            return Carbon::createFromTime(0, 0, 0);
        }
        // TIME column returns string like "15:00:00", convert to Carbon
        // Handle both "H:i:s" and "H:i" formats
        try {
            return Carbon::createFromFormat('H:i:s', $value);
        } catch (\Exception $e) {
            return Carbon::createFromFormat('H:i', $value);
        }
    }

    /**
     * Get end_time as Carbon instance.
     * 
     * TIME columns return strings, so we convert to Carbon for easier manipulation.
     * This allows us to use ->format() in views and jobs.
     * 
     * @param mixed $value The raw value from database (string like "21:00:00" or null)
     * @return Carbon Carbon instance representing the time
     */
    public function getEndTimeAttribute($value)
    {
        if ($value instanceof Carbon) {
            return $value;
        }
        if (empty($value)) {
            return Carbon::createFromTime(0, 0, 0);
        }
        // TIME column returns string like "21:00:00", convert to Carbon
        // Handle both "H:i:s" and "H:i" formats
        try {
            return Carbon::createFromFormat('H:i:s', $value);
        } catch (\Exception $e) {
            return Carbon::createFromFormat('H:i', $value);
        }
    }
}

