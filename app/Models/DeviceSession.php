<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Device Session Model
 * 
 * Tracks active internet sessions for devices.
 * Used to monitor how long devices are online and calculate time usage.
 */
class DeviceSession extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'device_id',
        'started_at',
        'ended_at',
        'duration_seconds',
        'total_bytes_sent',
        'total_bytes_received',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    /**
     * Get the device this session belongs to.
     * 
     * Relationship: belongsTo - One session belongs to one device
     * 
     * Usage Example:
     * $session = DeviceSession::find(1);
     * $device = $session->device; // Gets the Device this session belongs to
     * echo "Session for device: {$device->name}";
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    /**
     * Check if this session is still active.
     * 
     * Returns true if ended_at is NULL (session hasn't ended yet)
     *
     * @return bool True if session is active, false if ended
     * 
     * Usage Example:
     * $session = DeviceSession::find(1);
     * if ($session->isActive()) {
     *     echo "Session is still active";
     *     echo "Started: {$session->started_at}";
     *     echo "Duration so far: " . $session->getDurationMinutes() . " minutes";
     * } else {
     *     echo "Session ended at: {$session->ended_at}";
     * }
     */
    public function isActive(): bool
    {
        return $this->ended_at === null;  // null means session hasn't ended
    }

    /**
     * Calculate and update the duration of this session.
     * 
     * Calculates duration_seconds from started_at and ended_at timestamps
     * Only works if both timestamps exist (session has ended)
     *
     * @return void No return value
     * 
     * Usage Example:
     * $session = DeviceSession::find(1);
     * $session->started_at = Carbon::parse('2024-01-15 10:00:00');
     * $session->ended_at = Carbon::parse('2024-01-15 10:30:00');
     * $session->calculateDuration();
     * // duration_seconds is now 1800 (30 minutes * 60 seconds)
     * 
     * // diffInSeconds() calculates difference between two Carbon dates
     */
    public function calculateDuration(): void
    {
        // Only calculate if session has both start and end times
        if ($this->ended_at && $this->started_at) {
            // diffInSeconds() returns difference in seconds
            $this->duration_seconds = $this->started_at->diffInSeconds($this->ended_at);
            $this->save();  // Save to database
        }
    }

    /**
     * Get duration in minutes.
     * 
     * Returns duration in minutes (as float for precision)
     * If session is active, calculates from start time to now
     *
     * @return float Duration in minutes (e.g., 30.5 for 30 minutes 30 seconds)
     * 
     * Usage Example:
     * $session = DeviceSession::find(1);
     * 
     * // If session ended
     * $session->duration_seconds = 1800; // 30 minutes
     * echo $session->getDurationMinutes(); // Output: 30.00
     * 
     * // If session is still active
     * $session->ended_at = null;
     * $session->started_at = Carbon::now()->subMinutes(45);
     * echo $session->getDurationMinutes(); // Output: 45.00 (calculated from now)
     */
    public function getDurationMinutes(): float
    {
        // If duration_seconds exists (session ended), convert to minutes
        if ($this->duration_seconds) {
            // round($number, 2) rounds to 2 decimal places
            return round($this->duration_seconds / 60, 2);
        }

        // If session is still active, calculate from start time to now
        if ($this->isActive()) {
            // diffInMinutes() calculates difference from started_at to now()
            return round($this->started_at->diffInMinutes(now()), 2);
        }

        return 0;  // No duration if session hasn't started
    }

    /**
     * Get total bandwidth used in a human-readable format.
     * 
     * Converts bytes to GB, MB, KB, or bytes format
     *
     * @return string Formatted bandwidth (e.g., "1.5 GB", "500 MB", "2.3 KB")
     * 
     * Usage Example:
     * $session = DeviceSession::find(1);
     * $session->total_bytes_sent = 52428800;      // 50 MB sent
     * $session->total_bytes_received = 104857600; // 100 MB received
     * echo $session->getTotalBandwidthFormatted(); // Output: "150.00 MB"
     */
    public function getTotalBandwidthFormatted(): string
    {
        $total = $this->total_bytes_sent + $this->total_bytes_received;
        
        // Same logic as BrowsingLog::getTotalBandwidthFormatted()
        if ($total >= 1073741824) {
            return number_format($total / 1073741824, 2) . ' GB';
        } elseif ($total >= 1048576) {
            return number_format($total / 1048576, 2) . ' MB';
        } elseif ($total >= 1024) {
            return number_format($total / 1024, 2) . ' KB';
        }
        
        return $total . ' bytes';
    }
}

