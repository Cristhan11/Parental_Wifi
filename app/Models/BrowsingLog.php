<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Browsing Log Model
 * 
 * Tracks all websites visited by child devices.
 */
class BrowsingLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'device_id',
        'url',
        'domain',
        'ip_address',
        'user_agent',
        'bytes_sent',
        'bytes_received',
        'visited_at',
        'visit_count',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'visited_at' => 'datetime',
        ];
    }

    /**
     * Get the device that visited this website.
     * 
     * Relationship: belongsTo - One browsing log belongs to one device
     * 
     * Usage Example:
     * $log = BrowsingLog::find(1);
     * $device = $log->device; // Gets the Device that visited this site
     * echo "Device '{$device->name}' visited '{$log->url}' at {$log->visited_at}";
     * echo "Bandwidth used: " . $log->getTotalBandwidthFormatted();
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    /**
     * Get total bandwidth used in a human-readable format.
     * 
     * Converts bytes to GB, MB, KB, or bytes format
     *
     * @return string Formatted bandwidth (e.g., "1.5 GB", "500 MB", "2.3 KB", "1024 bytes")
     * 
     * Usage Example:
     * $log = BrowsingLog::find(1);
     * $log->bytes_sent = 1048576;      // 1 MB sent
     * $log->bytes_received = 2097152;  // 2 MB received
     * echo $log->getTotalBandwidthFormatted(); // Output: "3.00 MB"
     * 
     * // Constants used:
     * // 1073741824 = 1 GB (1024 * 1024 * 1024)
     * // 1048576 = 1 MB (1024 * 1024)
     * // 1024 = 1 KB
     */
    public function getTotalBandwidthFormatted(): string
    {
        $total = $this->bytes_sent + $this->bytes_received;  // Total bytes
        
        // Convert to GB if >= 1 GB
        if ($total >= 1073741824) {
            // number_format($number, 2) formats with 2 decimal places
            return number_format($total / 1073741824, 2) . ' GB';
        } 
        // Convert to MB if >= 1 MB
        elseif ($total >= 1048576) {
            return number_format($total / 1048576, 2) . ' MB';
        } 
        // Convert to KB if >= 1 KB
        elseif ($total >= 1024) {
            return number_format($total / 1024, 2) . ' KB';
        }
        
        // If less than 1 KB, show as bytes
        return $total . ' bytes';
    }
}

